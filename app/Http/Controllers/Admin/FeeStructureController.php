<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\ClassModel;
use App\Models\FeeComponent;
use App\Models\FeeTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeeStructureController extends Controller
{
    /** Resolve school id from session or logged-in user */
    private function currentSchoolId(): ?int
    {
        return session('current_school_id') ?: (Auth::user()?->school_id);
    }

    /** List */
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

    /** Create form */
    public function create()
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $classes = ClassModel::where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $components = FeeComponent::where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $terms = FeeTerm::where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('start_date')
            ->get();

        return view('admin.fees.structures.create', compact('classes', 'components', 'terms'));
    }

    /** Store */
    public function store(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $data = $request->validate([
            'class_id'       => 'required|exists:classes,id',
            'academic_year'  => 'required|string',
            'annual_fee'     => 'required|numeric|min:0',
            'effective_from' => 'nullable|date',
            'effective_to'   => 'nullable|date|after_or_equal:effective_from',

            // components[]
            'components'                          => 'array',
            'components.*.component_id'           => 'required|exists:fee_components,id',
            'components.*.calc_type'              => 'nullable|in:fixed,percent_of_base',
            'components.*.include_in_monthly'     => 'nullable|boolean',
            'components.*.bill_month'             => 'nullable|integer|min:1|max:12',
            'components.*.fee_term_id'            => 'nullable|exists:fee_terms,id',
        ]);

        $monthly = round($data['annual_fee'] / 12, 2);

        DB::transaction(function () use ($schoolId, $data, $monthly) {
            $structure = FeeStructure::create([
                'school_id'      => $schoolId,
                'class_id'       => $data['class_id'],
                'academic_year'  => $data['academic_year'],
                'annual_fee'     => round($data['annual_fee'], 2),
                'monthly_fee'    => $monthly,
                'effective_from' => $data['effective_from'] ?? null,
                'effective_to'   => $data['effective_to'] ?? null,
            ]);

            // Pivot sync payload
            $syncData = [];
            foreach (($data['components'] ?? []) as $row) {
                $syncData[$row['component_id']] = [
                    'calc_type_override' => $row['calc_type'] ?? null,
                    'include_in_monthly' => !empty($row['include_in_monthly']),
                    'bill_month'         => $row['bill_month'] ?? null,
                    'fee_term_id'        => $row['fee_term_id'] ?? null,
                ];
            }

            if ($syncData) {
                $structure->components()->sync($syncData);
            }
        });

        return redirect()->route('admin.fees.structures.index')
            ->with('success', 'Fee structure saved.');
    }

    /** Edit form */
    public function edit($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $structure = FeeStructure::where('school_id', $schoolId)
            ->with('components')
            ->findOrFail($id);

        $classes = ClassModel::where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $components = FeeComponent::where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $terms = FeeTerm::where('school_id', $schoolId)
            ->where('status', 1)
            ->orderBy('start_date')
            ->get();

        $annualFee = $structure->annual_fee !== null
            ? round($structure->annual_fee, 2)
            : round(($structure->monthly_fee ?? 0) * 12, 2);

        return view('admin.fees.structures.edit', compact('structure', 'classes', 'components', 'terms', 'annualFee'));
    }

    /** Update */
    public function update(Request $request, $id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $structure = FeeStructure::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'class_id'       => 'required|exists:classes,id',
            'academic_year'  => 'required|string',
            'annual_fee'     => 'required|numeric|min:0',
            'effective_from' => 'nullable|date',
            'effective_to'   => 'nullable|date|after_or_equal:effective_from',

            'components'                          => 'array',
            'components.*.component_id'           => 'required|exists:fee_components,id',
            'components.*.calc_type'              => 'nullable|in:fixed,percent_of_base',
            'components.*.include_in_monthly'     => 'nullable|boolean',
            'components.*.bill_month'             => 'nullable|integer|min:1|max:12',
            'components.*.fee_term_id'            => 'nullable|exists:fee_terms,id',
        ]);

        DB::transaction(function () use ($structure, $data) {
            $structure->update([
                'class_id'       => $data['class_id'],
                'academic_year'  => $data['academic_year'],
                'annual_fee'     => round($data['annual_fee'], 2),
                'monthly_fee'    => round($data['annual_fee'] / 12, 2),
                'effective_from' => $data['effective_from'] ?? null,
                'effective_to'   => $data['effective_to'] ?? null,
            ]);

            $syncData = [];
            foreach (($data['components'] ?? []) as $row) {
                $syncData[$row['component_id']] = [
                    'calc_type_override' => $row['calc_type'] ?? null,
                    'include_in_monthly' => !empty($row['include_in_monthly']),
                    'bill_month'         => $row['bill_month'] ?? null,
                    'fee_term_id'        => $row['fee_term_id'] ?? null,
                ];
            }

            $structure->components()->sync($syncData);
        });

        return redirect()->route('admin.fees.structures.index')
            ->with('success', 'Fee structure updated.');
    }

    /** Delete */
    public function destroy($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $structure = FeeStructure::where('school_id', $schoolId)->findOrFail($id);
        $structure->delete();

        return back()->with('success', 'Fee structure deleted.');
    }
}
