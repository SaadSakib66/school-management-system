<?php

namespace App\Http\Controllers;

use App\Models\MarksGrade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class MarksGradeController extends Controller
{
    // GET admin/marks_grade/list
    public function list(Request $request)
    {
        $data['getRecord'] = MarksGrade::with('creator')
            ->orderBy('percent_from', 'desc')
            ->paginate(10);

        $data['header_title'] = 'Marks Grade';

        return view('admin.marks_grade.list', $data);
    }

    // GET admin/marks_grade/add
    public function add()
    {
        return view('admin.marks_grade.add', [
            'header_title' => 'Add Marks Grade',
            'grade'        => null,
        ]);
    }

    // POST admin/marks_grade/add-grade
    public function addGrade(Request $request)
    {
        $data = $request->validate([
            'grade_name'    => ['required', 'string', 'max:50',
                                Rule::unique('marks_grades', 'grade_name')->whereNull('deleted_at')],
            'percent_from'  => ['required', 'integer', 'min:0', 'max:100'],
            'percent_to'    => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        if ((int)$data['percent_from'] > (int)$data['percent_to']) {
            return back()->withErrors(['percent_from' => 'Percent From must be less than or equal to Percent To.'])
                         ->withInput();
        }

        $data['created_by'] = Auth::id();
        MarksGrade::create($data);

        return redirect()->route('admin.marks-grade.list')->with('success', 'Marks grade created.');
    }

    // GET admin/marks_grade/edit/{id}
    public function editGrade($id)
    {
        $grade = MarksGrade::findOrFail($id);

        return view('admin.marks_grade.add', [
            'header_title' => 'Edit Marks Grade',
            'grade'        => $grade,
        ]);
    }

    // POST admin/marks_grade/update/{id}
    public function updateGrade(Request $request, $id)
    {
        $grade = MarksGrade::findOrFail($id);

        $data = $request->validate([
            'grade_name'    => ['required', 'string', 'max:50',
                                Rule::unique('marks_grades', 'grade_name')->ignore($grade->id)->whereNull('deleted_at')],
            'percent_from'  => ['required', 'integer', 'min:0', 'max:100'],
            'percent_to'    => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        if ((int)$data['percent_from'] > (int)$data['percent_to']) {
            return back()->withErrors(['percent_from' => 'Percent From must be less than or equal to Percent To.'])
                         ->withInput();
        }

        $grade->update($data);

        return redirect()->route('admin.marks-grade.list')->with('success', 'Marks grade updated.');
    }

    // POST admin/marks_grade/delete
    public function deleteGrade(Request $request)
    {
        $request->validate(['id' => ['required', 'integer', 'exists:marks_grades,id']]);

        $grade = MarksGrade::findOrFail($request->id);
        $grade->delete();

        return redirect()->route('admin.marks-grade.list')->with('success', 'Marks grade deleted.');
    }
}
