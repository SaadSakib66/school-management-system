<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudentFeeInvoice;
use Illuminate\Support\Facades\Auth;

class MyFeesController extends Controller
{
    public function index()
    {
        $u = Auth::user(); // role = student

        $invoices = StudentFeeInvoice::where('school_id', $u->school_id)
            ->where('student_id', $u->id)
            ->with('payments')
            ->orderByDesc('academic_year')
            ->orderByDesc('month')
            ->paginate(20);

        return view('student.fees.index', compact('invoices'));
    }
}
