<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User; // users table (role = student)
use App\Models\FeeStructure;
use App\Models\StudentFeeInvoice;
use App\Models\FeePayment;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class FeeInvoiceController extends Controller
{
    /** Month map used for selects and display */
    private const MONTHS = [
        1  => 'January',
        2  => 'February',
        3  => 'March',
        4  => 'April',
        5  => 'May',
        6  => 'June',
        7  => 'July',
        8  => 'August',
        9  => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    private function currentSchoolId(): ?int
    {
        return session('current_school_id') ?: (Auth::user()?->school_id);
    }

    public function index(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $q = StudentFeeInvoice::where('school_id', $schoolId)
            ->with(['student', 'class'])
            ->orderByDesc('academic_year')
            ->orderByDesc('month');

        if ($request->filled('class_id')) $q->where('class_id', $request->class_id);
        if ($request->filled('status'))   $q->where('status', $request->status);
        if ($request->filled('month'))    $q->where('month', (int) $request->month);
        if ($request->filled('year'))     $q->where('academic_year', $request->year);
        if ($request->filled('student')) {
            $name = $request->student;
            $q->whereHas('student', function ($s) use ($name) {
                $s->where(function ($w) use ($name) {
                    $w->where('name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%")
                    ->orWhereRaw("CONCAT(COALESCE(name,''),' ',COALESCE(last_name,'')) LIKE ?", ["%{$name}%"]);
                });
            });
        }

        if ($request->filled('email')) {
            $email = $request->email;
            $q->whereHas('student', fn($s) => $s->where('email', 'like', "%{$email}%"));
        }


        $invoices = $q->paginate(25);

        $classes = ClassModel::where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $months = self::MONTHS;

        return view('admin.fees.invoices.index', compact('invoices', 'classes', 'months'));
    }

    public function generateForm()
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $classes = ClassModel::where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $months = self::MONTHS;

        return view('admin.fees.invoices.generate', compact('classes', 'months'));
    }

    public function generate(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $request->validate([
            // point to real table "classes"
            'class_id'      => 'required|exists:classes,id',
            'academic_year' => 'required|string',
            'month'         => 'required|integer|min:1|max:12',
            'due_date'      => 'nullable|date',
        ], [
            'due_date.date' => 'The due date must be a valid calendar date.',
        ]);

        // Ensure fee structure exists for this class+year+school
        $structure = FeeStructure::where([
            'school_id'     => $schoolId,
            'class_id'      => $request->class_id,
            'academic_year' => $request->academic_year,
        ])->first();

        if (!$structure) {
            return back()->with('error', 'No fee structure found for this class and academic year.');
        }

        // Pull students for this class and school (role=student)
        $studentsQuery = User::query()
            ->where('school_id', $schoolId)
            ->where('role', 'student')
            ->where('class_id', $request->class_id);

        // Only filter soft-deleted if the column exists
        if (Schema::hasColumn('users', 'deleted_at')) {
            $studentsQuery->whereNull('deleted_at');
        }

        $students = $studentsQuery->get(['id', 'class_id']);

        if ($students->isEmpty()) {
            return back()->with('error', 'No students found for this class in the current school.');
        }

        // Create invoices idempotently and report stats
        $created = 0;
        $existing = 0;

        DB::transaction(function () use ($students, $structure, $request, $schoolId, &$created, &$existing) {
            foreach ($students as $st) {
                $invoice = StudentFeeInvoice::firstOrCreate(
                    [
                        'school_id'     => $schoolId,
                        'student_id'    => $st->id,
                        'academic_year' => $request->academic_year,
                        'month'         => (int) $request->month,
                    ],
                    [
                        'class_id'     => $st->class_id,
                        'due_date'     => $request->due_date,
                        'amount'       => $structure->monthly_fee, // monthly derived from annual
                        'discount'     => 0,
                        'fine'         => 0,
                        'status'       => 'unpaid',
                        'generated_by' => Auth::id(),
                        'notes'        => null,
                    ]
                );

                if ($invoice->wasRecentlyCreated) {
                    $created++;
                } else {
                    $existing++;
                }
            }
        });

        return redirect()
            ->route('admin.fees.invoices.index', [
                'class_id' => $request->class_id,
                'year'     => $request->academic_year,
                'month'    => $request->month,
            ])
            ->with('success', "Invoices generated. Created: {$created}, Already existed: {$existing}.");
    }

    public function show($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $invoice = StudentFeeInvoice::where('school_id', $schoolId)
            ->with(['student', 'payments'])
            ->findOrFail($id);

        return view('admin.fees.invoices.show', compact('invoice'));
    }

    public function storePayment(Request $request, $invoiceId)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $invoice = StudentFeeInvoice::where('school_id', $schoolId)->findOrFail($invoiceId);

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

            // Recalculate invoice status
            $invoice->load('payments');
            $total = $invoice->amount - $invoice->discount + $invoice->fine;
            $paid  = (float) $invoice->payments->sum('amount');
            $due   = $total - $paid;

            $invoice->status = $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
            $invoice->save();
        });

        return back()->with('success', 'Payment recorded.');
    }

    public function deletePayment($paymentId)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $payment = FeePayment::where('school_id', $schoolId)
            ->with('invoice')
            ->findOrFail($paymentId);

        $invoice = $payment->invoice;

        DB::transaction(function () use ($payment, $invoice) {
            $payment->delete();

            // Recalculate invoice status
            $invoice->load('payments');
            $total = $invoice->amount - $invoice->discount + $invoice->fine;
            $paid  = (float) $invoice->payments->sum('amount');
            $due   = $total - $paid;

            $invoice->status = $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
            $invoice->save();
        });

        return back()->with('success', 'Payment deleted and invoice updated.');
    }


    public function pdf($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error','Please select a school first.');

        $invoice = StudentFeeInvoice::where('school_id', $schoolId)
            ->with(['student','class','payments'])
            ->findOrFail($id);

        $months = self::MONTHS;

        $pdf = PDF::loadView('admin.fees.invoices.pdf.invoice', compact('invoice','months'))
            ->setPaper('a4', 'portrait');

        $file = sprintf('invoice-%s-%s-%02d.pdf', $invoice->student?->id, $invoice->academic_year, (int)$invoice->month);
        return $pdf->stream($file); // opens in a new tab (no auto-download)
    }



}
