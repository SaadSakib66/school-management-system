<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentFeeInvoice;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

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

        // We’ll compute total_paid using a correlated subquery (same as you had)
        $rows = StudentFeeInvoice::select([
                'class_id', 'academic_year', 'month',
                DB::raw('COUNT(*) as total_invoices'),
                DB::raw('SUM(amount - discount + fine) as total_billed'),
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

        $rows = StudentFeeInvoice::select([
                'class_id', 'academic_year', 'month',
                DB::raw('COUNT(*) as total_invoices'),
                DB::raw('SUM(amount - discount + fine) as total_billed'),
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

        $pdf = PDF::loadView('admin.fees.reports.pdf.class_monthly', [
            'rows'   => $rows,
            'months' => $months,
            'filters'=> $request->only(['academic_year','month','class_id']),
        ])->setPaper('a4', 'portrait');

        $name = 'class-monthly-report.pdf';
        return $pdf->stream($name);
    }

    /** Statement for a single student (list) */
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
            'academic_year' => 'nullable|string', // optional: if set, limit to that year
        ]);

        $invoices = StudentFeeInvoice::where('school_id', $schoolId)
            ->where('student_id', $request->student_id)
            ->when($request->academic_year, fn($q) => $q->where('academic_year', $request->academic_year))
            ->with(['payments','student','class'])
            ->orderBy('academic_year')
            ->orderBy('month')
            ->get();

        $months = self::MONTHS;

        return view('admin.fees.reports.student_statement', compact('invoices', 'months'));
    }

    /** Student statement PDF (single year OR all years) */
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

        $invoices = StudentFeeInvoice::where('school_id', $schoolId)
            ->where('student_id', $request->student_id)
            ->when($request->academic_year, fn($q) => $q->where('academic_year', $request->academic_year))
            ->with(['payments','student','class'])
            ->orderBy('academic_year')
            ->orderBy('month')
            ->get();

        $months = self::MONTHS;

        $pdf = PDF::loadView('admin.fees.reports.pdf.student_statement', [
            'invoices' => $invoices,
            'months'   => $months,
            'filters'  => $request->only(['student_id','academic_year']),
        ])->setPaper('a4', 'portrait');

        $name = 'student-statement.pdf';
        return $pdf->download($name);
    }
}
