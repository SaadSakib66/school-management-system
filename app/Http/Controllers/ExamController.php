<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    //
    public function list()
    {
        $data['getRecord'] = Exam::getRecord();
        $data['header_title'] = 'Exam List';
        $data['exam'] = null;
        return view('admin.exam.list', $data);
    }

    public function add()
    {
        $data['header_title'] = 'Add Exam';
        return view('admin.exam.add', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'note' => ['nullable','string','max:1000'],
        ]);

        Exam::create([
            'name'       => $validated['name'],
            'note'       => $validated['note'] ?? null,
            'created_by' => Auth::id(),              // make sure exams.created_by exists
        ]);

        return redirect()->route('admin.exam.list')->with('success', 'Exam added successfully.');
    }

    public function edit(Exam $exam)
    {
        return view('admin.exam.add', [
            'header_title' => 'Edit Exam',
            'exam' => $exam,
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'note' => ['nullable','string','max:1000'],
        ]);

        $exam->update([
            'name' => $validated['name'],
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('admin.exam.list')->with('success', 'Exam updated successfully.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'id' => ['required','exists:exams,id'],
        ]);

        $exam = Exam::findOrFail($request->id);
        $exam->delete(); // soft delete

        return redirect()->route('admin.exam.list')->with('success', 'Exam deleted successfully.');
    }

    
}
