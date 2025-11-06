<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeeTermController extends Controller
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

        $q = FeeTerm::where('school_id', $schoolId);

        // Optional filters
        if ($request->filled('year')) {
            $q->where('academic_year', 'like', '%'.trim($request->year).'%');
        }
        if ($request->filled('name')) {
            $q->where('name', 'like', '%'.trim($request->name).'%');
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status === '1' ? 1 : 0);
        }

        $terms = $q->orderBy('start_date')->paginate(20)->withQueryString();

        return view('admin.fees.terms.index', compact('terms'));
    }

    /** Create form */
    public function create()
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        return view('admin.fees.terms.create');
    }

    /** Store */
    public function store(Request $request)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $data = $request->validate([
            'academic_year' => 'required|string|max:255',
            'name'          => 'required|string|max:255',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'status'        => 'nullable|boolean',
        ]);

        FeeTerm::create([
            'school_id'     => $schoolId,
            'academic_year' => $data['academic_year'],
            'name'          => $data['name'],
            'start_date'    => $data['start_date'],
            'end_date'      => $data['end_date'],
            'status'        => !empty($data['status']),
        ]);

        return redirect()->route('admin.fees.terms.index')->with('success', 'Term created.');
    }

    /** Edit form */
    public function edit($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $term = FeeTerm::where('school_id', $schoolId)->findOrFail($id);

        return view('admin.fees.terms.edit', compact('term'));
    }

    /** Update */
    public function update(Request $request, $id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $term = FeeTerm::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'academic_year' => 'required|string|max:255',
            'name'          => 'required|string|max:255',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'status'        => 'nullable|boolean',
        ]);

        $term->update([
            'academic_year' => $data['academic_year'],
            'name'          => $data['name'],
            'start_date'    => $data['start_date'],
            'end_date'      => $data['end_date'],
            'status'        => !empty($data['status']),
        ]);

        return redirect()->route('admin.fees.terms.index')->with('success', 'Term updated.');
    }

    /** Delete (soft) */
    public function destroy($id)
    {
        $schoolId = $this->currentSchoolId();
        if (!$schoolId) return back()->with('error', 'Please select a school first.');

        $term = FeeTerm::where('school_id', $schoolId)->findOrFail($id);
        $term->delete();

        return back()->with('success', 'Term deleted.');
    }
}
