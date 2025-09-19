<?php

namespace App\Http\Controllers;

use App\Models\MarksGrade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class MarksGradeController extends Controller
{
    /* -------------------------------
     * School-context helpers (P6)
     * ------------------------------- */
    protected function currentSchoolId(): ?int
    {
        $u = Auth::user();
        if (! $u) return null;
        if ($u->role === 'super_admin') {
            return (int) session('current_school_id');
        }
        return (int) $u->school_id;
    }

    protected function guardSchoolContext()
    {
        if (Auth::user()?->role === 'super_admin' && ! $this->currentSchoolId()) {
            return redirect()
                ->route('superadmin.schools.switch')
                ->with('error', 'Please select a school first.');
        }
        return null;
    }

    // GET admin/marks_grade/list
    public function list(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $data['getRecord'] = MarksGrade::with('creator')
            ->orderBy('percent_from', 'desc')
            ->paginate(10);

        $data['header_title'] = 'Marks Grade';

        return view('admin.marks_grade.list', $data);
    }

    // GET admin/marks_grade/add
    public function add()
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        return view('admin.marks_grade.add', [
            'header_title' => 'Add Marks Grade',
            'grade'        => null,
        ]);
    }

    // POST admin/marks_grade/add-grade
    public function addGrade(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;
        $schoolId = $this->currentSchoolId(); // used for validation scopes

        $data = $request->validate([
            'grade_name'   => [
                'required', 'string', 'max:50',
                Rule::unique('marks_grades', 'grade_name')
                    ->where(fn($q) => $q->where('school_id', $schoolId)->whereNull('deleted_at')),
            ],
            'percent_from' => ['required', 'integer', 'min:0', 'max:100'],
            'percent_to'   => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        if ((int) $data['percent_from'] > (int) $data['percent_to']) {
            return back()->withErrors(['percent_from' => 'Percent From must be less than or equal to Percent To.'])
                         ->withInput();
        }

        // Prevent overlapping ranges within the same school
        $overlap = MarksGrade::where(function ($w) use ($data) {
                $w->where('percent_to', '>=', (int) $data['percent_from'])
                  ->where('percent_from', '<=', (int) $data['percent_to']);
            })
            ->exists();

        if ($overlap) {
            return back()->withErrors([
                'percent_from' => 'This range overlaps an existing grade for this school.',
                'percent_to'   => 'This range overlaps an existing grade for this school.',
            ])->withInput();
        }

        $data['created_by'] = Auth::id();
        // school_id will auto-fill via BelongsToSchool::creating
        MarksGrade::create($data);

        return redirect()->route('admin.marks-grade.list')->with('success', 'Marks grade created.');
    }

    // GET admin/marks_grade/edit/{id}
    public function editGrade($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $grade = MarksGrade::findOrFail($id);

        return view('admin.marks_grade.add', [
            'header_title' => 'Edit Marks Grade',
            'grade'        => $grade,
        ]);
    }

    // POST admin/marks_grade/update/{id}
    public function updateGrade(Request $request, $id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;
        $schoolId = $this->currentSchoolId();

        $grade = MarksGrade::findOrFail($id);

        $data = $request->validate([
            'grade_name'   => [
                'required', 'string', 'max:50',
                Rule::unique('marks_grades', 'grade_name')
                    ->ignore($grade->id)
                    ->where(fn($q) => $q->where('school_id', $schoolId)->whereNull('deleted_at')),
            ],
            'percent_from' => ['required', 'integer', 'min:0', 'max:100'],
            'percent_to'   => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        if ((int) $data['percent_from'] > (int) $data['percent_to']) {
            return back()->withErrors(['percent_from' => 'Percent From must be less than or equal to Percent To.'])
                         ->withInput();
        }

        // Overlap check within same school, excluding this grade
        $overlap = MarksGrade::where('id', '!=', $grade->id)
            ->where(function ($w) use ($data) {
                $w->where('percent_to', '>=', (int) $data['percent_from'])
                  ->where('percent_from', '<=', (int) $data['percent_to']);
            })
            ->exists();

        if ($overlap) {
            return back()->withErrors([
                'percent_from' => 'This range overlaps an existing grade for this school.',
                'percent_to'   => 'This range overlaps an existing grade for this school.',
            ])->withInput();
        }

        $grade->update($data);

        return redirect()->route('admin.marks-grade.list')->with('success', 'Marks grade updated.');
    }

    // POST admin/marks_grade/delete
    public function deleteGrade(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $request->validate(['id' => ['required', 'integer', 'exists:marks_grades,id']]);

        $grade = MarksGrade::findOrFail($request->id);
        $grade->delete();

        return redirect()->route('admin.marks-grade.list')->with('success', 'Marks grade deleted.');
    }
}
