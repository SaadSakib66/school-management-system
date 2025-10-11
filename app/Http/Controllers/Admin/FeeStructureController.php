<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeeStructureController extends Controller
{
    /** Resolve school id from session or logged-in user */
    private function currentSchoolId(): ?int
    {
        return session('current_school_id') ?: (Auth::user()?->school_id);
    }

    public function index(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $structures = FeeStructure::where('school_id', $schoolId)
            ->with('class')
            ->orderBy('academic_year', 'desc')
            ->paginate(20);

        return view('admin.fees.structures.index', compact('structures'));
    }

    public function create()
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $classes = ClassModel::where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return view('admin.fees.structures.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $data = $request->validate([
            'class_id'      => 'required|exists:classes,id',
            'academic_year'  => 'required|string',
            'annual_fee'     => 'required|numeric|min:0',
            'effective_from' => 'nullable|date',
            'effective_to'   => 'nullable|date|after_or_equal:effective_from',
        ]);

        $monthly = round($data['annual_fee'] / 12, 2);

        FeeStructure::create([
            'school_id'      => $schoolId,
            'class_id'       => $data['class_id'],
            'academic_year'  => $data['academic_year'],
            'annual_fee'     => round($data['annual_fee'], 2),
            'monthly_fee'    => $monthly,
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to'   => $data['effective_to'] ?? null,
        ]);

        return redirect()
            ->route('admin.fees.structures.index')
            ->with('success', 'Fee structure saved.');
    }

    public function edit($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $structure = FeeStructure::where('school_id', $schoolId)->findOrFail($id);

        $classes = ClassModel::where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        // Prefer stored annual_fee; fallback to monthly * 12 if null
        $annualFee = $structure->annual_fee !== null
            ? round($structure->annual_fee, 2)
            : round(($structure->monthly_fee ?? 0) * 12, 2);

        return view('admin.fees.structures.edit', compact('structure', 'classes', 'annualFee'));
    }

    public function update(Request $request, $id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $structure = FeeStructure::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'class_id'      => 'required|exists:classes,id',
            'academic_year'  => 'required|string',
            'annual_fee'     => 'required|numeric|min:0',
            'effective_from' => 'nullable|date',
            'effective_to'   => 'nullable|date|after_or_equal:effective_from',
        ]);

        $monthly = round($data['annual_fee'] / 12, 2);

        $structure->update([
            'class_id'       => $data['class_id'],
            'academic_year'  => $data['academic_year'],
            'annual_fee'     => round($data['annual_fee'], 2),
            'monthly_fee'    => $monthly,
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to'   => $data['effective_to'] ?? null,
        ]);

        return redirect()
            ->route('admin.fees.structures.index')
            ->with('success', 'Fee structure updated.');
    }

    public function destroy($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $structure = FeeStructure::where('school_id', $schoolId)->findOrFail($id);
        $structure->delete();

        return back()->with('success', 'Fee structure deleted.');
    }
}
