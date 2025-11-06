<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FeeStructure;
use App\Models\StudentFeeInvoice;
use App\Models\FeePayment;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Storage;

class FeeInvoiceController extends Controller
{
    /** Month map used for selects and display */
    private const MONTHS = [
        1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
        7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December',
    ];

    private function currentSchoolId(): ?int
    {
        return session('current_school_id') ?: (Auth::user()?->school_id);
    }

    /**
     * Build a full breakdown with line items (base + applicable components + discount/fine).
     *
     * Returns:
     * [
     *   'items'    => [ ['label'=>'Monthly Tuition','amount'=>...], ... ],
     *   'subtotal' => number,   // base + components (discount/fine বাদে)
     *   'billed'   => number,   // subtotal - discount + fine
     *   'paid'     => number,
     *   'due'      => number
     * ]
     */
    private function buildInvoiceBreakdown(StudentFeeInvoice $invoice): array
    {
        $items = [];

        // Base tuition (invoice generate সময় সেট করা)
        $base = (float) $invoice->amount;
        $items[] = ['label' => 'Monthly Tuition', 'amount' => round($base, 2)];

        // Structure + components
        $structure = FeeStructure::where([
            'school_id'     => $invoice->school_id,
            'class_id'      => $invoice->class_id,
            'academic_year' => $invoice->academic_year,
        ])->with('components')->first();

        $componentsTotal = 0.0;

        if ($structure) {
            foreach ($structure->components as $comp) {
                $p = $comp->pivot; // fee_structure_component_items

                // প্রযোজ্য কি না (monthly include or one-time bill_month)
                $applicable =
                    (bool) $p->include_in_monthly ||
                    (!empty($p->bill_month) && (int)$p->bill_month === (int)$invoice->month);

                if (! $applicable) continue;

                $calcType = $p->calc_type_override ?: ($comp->calc_type ?: 'fixed');
                $val      = $p->amount_override;      // nullable
                $defVal   = $comp->default_amount;    // nullable

                if ($calcType === 'percent_of_base') {
                    $percent = is_numeric($val) ? (float)$val : ((float)$defVal ?: 0);
                    $amt = round($base * ($percent / 100), 2);
                } else {
                    $fixed = is_numeric($val) ? (float)$val : ((float)$defVal ?: 0);
                    $amt = round($fixed, 2);
                }

                $componentsTotal += $amt;
                $items[] = ['label' => $comp->name, 'amount' => $amt];
            }
        }

        // Subtotal (base + components)
        $subTotal = round($base + $componentsTotal, 2);

        // Discount (-) & Fine (+) — invoice থেকে
        $discount = (float) ($invoice->discount ?? 0);
        if ($discount != 0) {
            $items[] = ['label' => 'Discount', 'amount' => -round($discount, 2)];
        }

        $fine = (float) ($invoice->fine ?? 0);
        if ($fine != 0) {
            $items[] = ['label' => 'Fine', 'amount' => round($fine, 2)];
        }

        // Total billed
        $billed = round($subTotal - $discount + $fine, 2);

        // Payments
        $paid = (float) $invoice->payments->sum('amount');
        $due  = round(max(0, $billed - $paid), 2);

        // Helpful summary rows (views এ চাইলে দেখাও)
        $items[] = ['label' => 'Total Billed', 'amount' => $billed];
        $items[] = ['label' => 'Total Paid',   'amount' => $paid];
        $items[] = ['label' => 'Amount Due',   'amount' => $due];

        return [
            'items'    => $items,
            'subtotal' => $subTotal,
            'billed'   => $billed,
            'paid'     => $paid,
            'due'      => $due,
        ];
    }

    /** ---------- Index ---------- */
    public function index(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $q = StudentFeeInvoice::where('school_id', $schoolId)
            ->with(['student','class','payments'])
            ->orderByDesc('academic_year')
            ->orderByDesc('month');

        if ($request->filled('class_id')) $q->where('class_id', $request->class_id);
        if ($request->filled('status'))   $q->where('status', $request->status);
        if ($request->filled('month'))    $q->where('month', (int)$request->month);
        if ($request->filled('year'))     $q->where('academic_year', $request->year);

        // Student ID (exact)
        if ($request->filled('student_id')) {
            $q->where('student_id', trim($request->student_id));
        }

        // Mobile (like) on users.mobile_number
        if ($request->filled('mobile')) {
            $mobile = trim($request->mobile);
            $q->whereHas('student', fn($s) => $s->where('mobile_number', 'like', "%{$mobile}%"));
        }

        // (optional) email filter
        if ($request->filled('email')) {
            $email = $request->email;
            $q->whereHas('student', fn($s) => $s->where('email', 'like', "%{$email}%"));
        }

        $invoices = $q->paginate(25);

        // প্রতি রোতে breakdown attach + status normalize (শুধু status আপডেট)
        $collection = $invoices->getCollection()->map(function (StudentFeeInvoice $inv) {
            $br = $this->buildInvoiceBreakdown($inv);

            // status normalize
            $newStatus = $br['due'] <= 0 ? 'paid' : ($br['paid'] > 0 ? 'partial' : 'unpaid');
            if ($newStatus !== $inv->status) {
                StudentFeeInvoice::whereKey($inv->id)->update(['status' => $newStatus]);
                $inv->status = $newStatus; // reflect in memory
            }

            // নিরাপদ: relation হিসেবে attach (save করবে না)
            $inv->setRelation('computed', collect($br));

            return $inv;
        });
        $invoices->setCollection($collection);

        $classes = ClassModel::where('school_id', $schoolId)
            ->where('status', 1)->orderBy('name')->get();

        $months = self::MONTHS;

        return view('admin.fees.invoices.index', compact('invoices','classes','months'));
    }

    /** ---------- Generate form ---------- */
    public function generateForm()
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $classes = ClassModel::where('school_id', $schoolId)->where('status', 1)->orderBy('name')->get();
        $months  = self::MONTHS;

        return view('admin.fees.invoices.generate', compact('classes','months'));
    }

    /** ---------- Generate ---------- */
    public function generate(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $request->validate([
            'class_id'      => 'required|exists:classes,id',
            'academic_year' => 'required|string',
            'month'         => 'required|integer|min:1|max:12',
            'due_date'      => 'nullable|date',
        ], ['due_date.date' => 'The due date must be a valid calendar date.']);

        $structure = FeeStructure::where([
            'school_id'     => $schoolId,
            'class_id'      => $request->class_id,
            'academic_year' => $request->academic_year,
        ])->first();

        if (!$structure) {
            return back()->with('error', 'No fee structure found for this class and academic year.');
        }

        $studentsQuery = User::query()
            ->where('school_id', $schoolId)
            ->where('role', 'student')
            ->where('class_id', $request->class_id);

        if (Schema::hasColumn('users', 'deleted_at')) {
            $studentsQuery->whereNull('deleted_at');
        }

        $students = $studentsQuery->get(['id','class_id']);
        if ($students->isEmpty()) {
            return back()->with('error', 'No students found for this class in the current school.');
        }

        $created = 0; $existing = 0;

        DB::transaction(function () use ($students, $structure, $request, $schoolId, &$created, &$existing) {
            foreach ($students as $st) {
                $invoice = StudentFeeInvoice::firstOrCreate(
                    [
                        'school_id'     => $schoolId,
                        'student_id'    => $st->id,
                        'academic_year' => $request->academic_year,
                        'month'         => (int)$request->month,
                    ],
                    [
                        'class_id'     => $st->class_id,
                        'due_date'     => $request->due_date,
                        'amount'       => $structure->monthly_fee,
                        'discount'     => 0,
                        'fine'         => 0,
                        'status'       => 'unpaid',
                        'generated_by' => Auth::id(),
                        'notes'        => null,
                    ]
                );

                if ($invoice->wasRecentlyCreated) $created++; else $existing++;
            }
        });

        return redirect()->route('admin.fees.invoices.index', [
            'class_id' => $request->class_id,
            'year'     => $request->academic_year,
            'month'    => $request->month,
        ])->with('success', "Invoices generated. Created: {$created}, Already existed: {$existing}.");
    }

    /** ---------- Show ---------- */
    public function show($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $invoice = StudentFeeInvoice::where('school_id', $schoolId)
            ->with(['student','class','payments'])
            ->findOrFail($id);

        $breakdown = $this->buildInvoiceBreakdown($invoice);

        return view('admin.fees.invoices.show', compact('invoice','breakdown'));
    }

    /** ---------- Store Payment ---------- */
    public function storePayment(Request $request, $invoiceId)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $invoice = StudentFeeInvoice::where('school_id', $schoolId)
            ->with('payments')->findOrFail($invoiceId);

        $data = $request->validate([
            'amount'    => 'required|numeric|min:0.01',
            'paid_on'   => 'required|date',
            'method'    => 'required|in:cash,bank,mobile',
            'reference' => 'nullable|string|max:190',
            'remarks'   => 'nullable|string',
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $payment = new FeePayment(array_merge($data, [
                'school_id'   => $invoice->school_id,
                'invoice_id'  => $invoice->id,
                'student_id'  => $invoice->student_id,
                'received_by' => Auth::id(),
            ]));
            $payment->save();

            // Recalc status with components
            $invoice->load('payments');
            $br = $this->buildInvoiceBreakdown($invoice);

            $newStatus = $br['due'] <= 0 ? 'paid' : ($br['paid'] > 0 ? 'partial' : 'unpaid');
            if ($newStatus !== $invoice->status) {
                StudentFeeInvoice::whereKey($invoice->id)->update(['status' => $newStatus]);
            }
        });

        return back()->with('success', 'Payment recorded.');
    }

    /** ---------- Delete Payment ---------- */
    public function deletePayment($paymentId)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $payment = FeePayment::where('school_id', $schoolId)
            ->with('invoice.payments')
            ->findOrFail($paymentId);

        $invoice = $payment->invoice;

        DB::transaction(function () use ($payment, $invoice) {
            $payment->delete();

            $invoice->load('payments');
            $br = $this->buildInvoiceBreakdown($invoice);

            $newStatus = $br['due'] <= 0 ? 'paid' : ($br['paid'] > 0 ? 'partial' : 'unpaid');
            if ($newStatus !== $invoice->status) {
                StudentFeeInvoice::whereKey($invoice->id)->update(['status' => $newStatus]);
            }
        });

        return back()->with('success', 'Payment deleted and invoice updated.');
    }

    protected function schoolHeaderData(?int $forceSchoolId = null): array
    {
        // Resolve school id from several sources
        $schoolId =
            $forceSchoolId
            ?? (method_exists($this, 'currentSchoolId') ? $this->currentSchoolId() : null)
            ?? (Auth::check() ? Auth::user()->school_id : null)
            ?? session('school_id'); // optional extra session key you may use

        // As a last resort, pick the first school so the PDF doesn't explode
        if (!$schoolId) {
            $fallback = \App\Models\School::query()->orderBy('id')->value('id');
            if ($fallback) {
                $schoolId = (int)$fallback;
            } else {
                abort(403, 'No school context.');
            }
        }

        /** @var \App\Models\School $school */
        $school = \App\Models\School::findOrFail($schoolId);

        // ---- Logo to data URI (same logic as before) ----
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
                        $map = [
                            'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif',
                            'webp'=>'image/webp','bmp'=>'image/bmp','svg'=>'image/svg+xml'
                        ];
                        if (isset($map[$ext])) $mime = $map[$ext];
                    }
                    $logoSrc = 'data:'.$mime.';base64,'.base64_encode($bin);
                    break;
                }
            }
        }

        // ---- EIIN: prefer your real column name (eiin_num) ----
        $eiin = null;
        foreach (['eiin_num', 'eiin', 'eiin_code', 'eiin_no'] as $field) {
            if (isset($school->{$field})) {
                $val = trim((string)$school->{$field});
                if ($val !== '') { $eiin = $val; break; }
            }
        }

        $website = $school->website ?? $school->website_url ?? $school->domain ?? null;
        if (is_string($website)) $website = trim($website);

        return [
            'school'        => $school,
            'schoolLogoSrc' => $logoSrc,
            'schoolPrint'   => [
                'name'    => $school->name ?? $school->short_name ?? 'Unknown School',
                'eiin'    => $eiin, // ← will use eiin_num
                'address' => $school->address ?? $school->full_address ?? null,
                'website' => $website,
            ],
        ];
    }


    /** ---------- PDF ---------- */
    public function pdf($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $invoice = StudentFeeInvoice::where('school_id', $schoolId)
            ->with(['student','class','payments'])
            ->findOrFail($id);

        $months    = self::MONTHS;
        $breakdown = $this->buildInvoiceBreakdown($invoice);

        // ✅ school header data সংগ্রহ করে ভিউতে পাঠাই
        $header = $this->schoolHeaderData($schoolId);

        $viewData = array_merge(
            compact('invoice','months','breakdown'),
            $header // ['school','schoolLogoSrc','schoolPrint']
        );

        $pdf = PDF::loadView('admin.fees.invoices.pdf.invoice', $viewData)
            ->setPaper('a4', 'portrait');

        $file = sprintf('invoice-%s-%s-%02d.pdf', $invoice->student?->id, $invoice->academic_year, (int)$invoice->month);
        return $pdf->stream($file);
    }



}
