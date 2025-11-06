<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentFeeInvoice;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Storage;

class FeeReportController extends Controller
{
    /** Human names for months */
    private const MONTHS = [
        1=>'January', 2=>'February', 3=>'March', 4=>'April',
        5=>'May', 6=>'June', 7=>'July', 8=>'August',
        9=>'September', 10=>'October', 11=>'November', 12=>'December',
    ];

    private function currentSchoolId(): ?int
    {
        return session('current_school_id') ?: (Auth::user()?->school_id);
    }

    /* ---------- tiny helper: per-invoice components amount (SQL) ---------- */
    private function componentsSubquerySql(): string
    {
        // Correlated subquery; works per-row of student_fee_invoices
        return <<<SQL
COALESCE((
  SELECT SUM(
    CASE
      WHEN (fsci.include_in_monthly = 1 OR (fsci.bill_month IS NOT NULL AND fsci.bill_month = student_fee_invoices.month))
      THEN
        CASE
          WHEN COALESCE(fsci.calc_type_override, fc.calc_type) = 'percent_of_base'
            THEN student_fee_invoices.amount * (COALESCE(fsci.amount_override, fc.default_amount, 0) / 100)
          ELSE
            COALESCE(fsci.amount_override, fc.default_amount, 0)
        END
      ELSE 0
    END
  )
  FROM fee_structures fs
  JOIN fee_structure_component_items fsci ON fsci.fee_structure_id = fs.id
  JOIN fee_components fc ON fc.id = fsci.fee_component_id
  WHERE fs.school_id = student_fee_invoices.school_id
    AND fs.class_id = student_fee_invoices.class_id
    AND fs.academic_year = student_fee_invoices.academic_year
), 0)
SQL;
    }

    /* ---------- school header data (PDF) ---------- */
    protected function schoolHeaderData(?int $forceSchoolId = null): array
    {
        $schoolId =
            $forceSchoolId
            ?? $this->currentSchoolId()
            ?? (Auth::check() ? Auth::user()->school_id : null)
            ?? session('school_id');

        if (!$schoolId) {
            $fallback = \App\Models\School::query()->orderBy('id')->value('id');
            if ($fallback) $schoolId = (int)$fallback; else abort(403, 'No school context.');
        }

        /** @var \App\Models\School $school */
        $school = \App\Models\School::findOrFail($schoolId);

        $logoFile = $school->logo ?? $school->school_logo ?? $school->photo ?? null;
        $logoSrc  = null;

        if ($logoFile) {
            $normalized = ltrim(str_replace(['public/', 'storage/'], '', $logoFile), '/');
            $candidates = [$normalized, 'schools/'.basename($normalized), 'school_logos/'.basename($normalized)];
            foreach ($candidates as $path) {
                if (Storage::disk('public')->exists($path)) {
                    $bin  = Storage::disk('public')->get($path);
                    $mime = 'image/png';
                    if (class_exists(\finfo::class)) {
                        $fi  = new \finfo(FILEINFO_MIME_TYPE);
                        $det = $fi->buffer($bin);
                        if ($det) $mime = $det;
                    } else {
                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                        $map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp','bmp'=>'image/bmp','svg'=>'image/svg+xml'];
                        if (isset($map[$ext])) $mime = $map[$ext];
                    }
                    $logoSrc = 'data:'.$mime.';base64,'.base64_encode($bin);
                    break;
                }
            }
        }

        $eiin = null;
        foreach (['eiin_num','eiin','eiin_code','eiin_no'] as $f) {
            if (isset($school->{$f})) {
                $v = trim((string)$school->{$f});
                if ($v !== '') { $eiin = $v; break; }
            }
        }

        $website = $school->website ?? $school->website_url ?? $school->domain ?? null;
        if (is_string($website)) $website = trim($website);

        return [
            'school'        => $school,
            'schoolLogoSrc' => $logoSrc,
            'schoolPrint'   => [
                'name'    => $school->name ?? $school->short_name ?? 'Unknown School',
                'eiin'    => $eiin,
                'address' => $school->address ?? $school->full_address ?? null,
                'website' => $website,
            ],
        ];
    }

    /* ====================== CLASS MONTHLY ====================== */

    /** Summary by class and month (list) */
    public function classMonthly(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $request->validate([
            'academic_year' => 'nullable|string',
            'month'         => 'nullable|integer|min:1|max:12',
            'class_id'      => 'nullable|exists:classes,id',
        ]);

        $compSql = $this->componentsSubquerySql();

        $rows = StudentFeeInvoice::select([
                'class_id', 'academic_year', 'month',
                DB::raw('COUNT(*) as total_invoices'),
                // billed = base + components - discount + fine
                DB::raw("SUM((amount + ($compSql) - discount + fine)) as total_billed"),
                // total paid via correlated sum from fee_payments
                DB::raw('SUM((SELECT COALESCE(SUM(fp.amount),0)
                               FROM fee_payments fp
                               WHERE fp.invoice_id = student_fee_invoices.id
                               AND fp.deleted_at IS NULL)) as total_paid'),
            ])
            ->where('school_id', $schoolId)
            ->when($request->academic_year, fn ($q) => $q->where('academic_year', $request->academic_year))
            ->when($request->month, fn ($q) => $q->where('month', (int) $request->month))
            ->when($request->class_id, fn ($q) => $q->where('class_id', $request->class_id))
            ->groupBy('class_id', 'academic_year', 'month')
            ->with('class')
            ->orderBy('academic_year','desc')
            ->orderBy('month','desc')
            ->get();

        $classes = ClassModel::where('school_id', $schoolId)->where('status',1)->orderBy('name')->get();
        $months  = self::MONTHS;

        return view('admin.fees.reports.class_monthly', compact('rows', 'classes', 'months'));
    }

    /** Download PDF for class monthly (respects filters) */
    public function classMonthlyPdf(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $request->validate([
            'academic_year' => 'nullable|string',
            'month'         => 'nullable|integer|min:1|max:12',
            'class_id'      => 'nullable|exists:classes,id',
        ]);

        $compSql = $this->componentsSubquerySql();

        $rows = StudentFeeInvoice::select([
                'class_id', 'academic_year', 'month',
                DB::raw('COUNT(*) as total_invoices'),
                DB::raw("SUM((amount + ($compSql) - discount + fine)) as total_billed"),
                DB::raw('SUM((SELECT COALESCE(SUM(fp.amount),0)
                               FROM fee_payments fp
                               WHERE fp.invoice_id = student_fee_invoices.id
                               AND fp.deleted_at IS NULL)) as total_paid'),
            ])
            ->where('school_id', $schoolId)
            ->when($request->academic_year, fn ($q) => $q->where('academic_year', $request->academic_year))
            ->when($request->month, fn ($q) => $q->where('month', (int) $request->month))
            ->when($request->class_id, fn ($q) => $q->where('class_id', $request->class_id))
            ->groupBy('class_id', 'academic_year', 'month')
            ->with('class')
            ->orderBy('academic_year','desc')
            ->orderBy('month','desc')
            ->get();

        $months = self::MONTHS;
        $header = $this->schoolHeaderData($schoolId);

        $pdf = PDF::loadView('admin.fees.reports.pdf.class_monthly', [
            'rows'    => $rows,
            'months'  => $months,
            'filters' => $request->only(['academic_year','month','class_id']),
        ] + $header)->setPaper('a4', 'portrait');

        return $pdf->stream('class-monthly-report.pdf');
    }

    /* ====================== STUDENT MONTHLY SUMMARY ====================== */

    /** NEW: student monthly summary (group by student + month) */
    public function studentMonthly(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $request->validate([
            'academic_year' => 'nullable|string',
            'student_id'    => 'nullable|exists:users,id',
        ]);

        $compSql = $this->componentsSubquerySql();

        $rows = StudentFeeInvoice::select([
                'student_id', 'academic_year', 'month',
                DB::raw('COUNT(*) as total_invoices'),
                DB::raw("SUM((amount + ($compSql) - discount + fine)) as total_billed"),
                DB::raw('SUM((SELECT COALESCE(SUM(fp.amount),0)
                               FROM fee_payments fp
                               WHERE fp.invoice_id = student_fee_invoices.id
                               AND fp.deleted_at IS NULL)) as total_paid'),
            ])
            ->where('school_id', $schoolId)
            ->when($request->academic_year, fn ($q) => $q->where('academic_year', $request->academic_year))
            ->when($request->student_id, fn ($q) => $q->where('student_id', $request->student_id))
            ->groupBy('student_id', 'academic_year', 'month')
            ->with('student:id,name,last_name,email,mobile_number')
            ->orderBy('academic_year','desc')->orderBy('month','desc')
            ->get();

        $months = self::MONTHS;
        return view('admin.fees.reports.student_monthly', compact('rows','months'));
    }

    public function studentMonthlyPdf(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $request->validate([
            'academic_year' => 'nullable|string',
            'student_id'    => 'nullable|exists:users,id',
        ]);

        $compSql = $this->componentsSubquerySql();

        $rows = StudentFeeInvoice::select([
                'student_id', 'academic_year', 'month',
                DB::raw('COUNT(*) as total_invoices'),
                DB::raw("SUM((amount + ($compSql) - discount + fine)) as total_billed"),
                DB::raw('SUM((SELECT COALESCE(SUM(fp.amount),0)
                               FROM fee_payments fp
                               WHERE fp.invoice_id = student_fee_invoices.id
                               AND fp.deleted_at IS NULL)) as total_paid'),
            ])
            ->where('school_id', $schoolId)
            ->when($request->academic_year, fn ($q) => $q->where('academic_year', $request->academic_year))
            ->when($request->student_id, fn ($q) => $q->where('student_id', $request->student_id))
            ->groupBy('student_id', 'academic_year', 'month')
            ->with('student:id,name,last_name,email,mobile_number')
            ->orderBy('academic_year','desc')->orderBy('month','desc')
            ->get();

        $months = self::MONTHS;
        $header = $this->schoolHeaderData($schoolId);

        $pdf = PDF::loadView('admin.fees.reports.pdf.student_monthly', [
            'rows'    => $rows,
            'months'  => $months,
            'filters' => $request->only(['academic_year','student_id']),
        ] + $header)->setPaper('a4', 'portrait');

        return $pdf->stream('student-monthly-summary.pdf');
    }

    /* ====================== STUDENT STATEMENT (full-year monthly) ====================== */

    /** Statement for a single student (detail per month) */
    public function studentStatement(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $request->validate([
            'student_id'    => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q
                    ->where('school_id', $schoolId)
                    ->where('role', 'student')),
            ],
            'academic_year' => 'nullable|string',
        ]);

        $compSql = $this->componentsSubquerySql();

        // আমরা প্রতিটি ইনভয়েসে billed হিসাব করে আনছি (components সহ)
        $invoices = StudentFeeInvoice::select([
                '*',
                DB::raw("(amount + ($compSql) - discount + fine) AS billed_with_components")
            ])
            ->where('school_id', $schoolId)
            ->where('student_id', $request->student_id)
            ->when($request->academic_year, fn($q) => $q->where('academic_year', $request->academic_year))
            ->with(['payments','student','class'])
            ->orderBy('academic_year')->orderBy('month')
            ->get();

        $months = self::MONTHS;

        return view('admin.fees.reports.student_statement', compact('invoices', 'months'));
    }

    public function studentStatementPdf(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $request->validate([
            'student_id'    => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q
                    ->where('school_id', $schoolId)
                    ->where('role', 'student')),
            ],
            'academic_year' => 'nullable|string',
        ]);

        $compSql = $this->componentsSubquerySql();

        $invoices = StudentFeeInvoice::select([
                '*',
                DB::raw("(amount + ($compSql) - discount + fine) AS billed_with_components")
            ])
            ->where('school_id', $schoolId)
            ->where('student_id', $request->student_id)
            ->when($request->academic_year, fn($q) => $q->where('academic_year', $request->academic_year))
            ->with(['payments','student','class'])
            ->orderBy('academic_year')->orderBy('month')
            ->get();

        $months = self::MONTHS;
        $header = $this->schoolHeaderData($schoolId);

        $pdf = PDF::loadView('admin.fees.reports.pdf.student_statement', [
            'invoices' => $invoices,
            'months'   => $months,
            'filters'  => $request->only(['student_id','academic_year']),
        ] + $header)->setPaper('a4', 'portrait');

        return $pdf->stream('student-statement.pdf');
    }
}
