<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassSubject;
use App\Models\ClassModel;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;

class ClassSubjectController extends Controller
{
    public function assignSubjectList(Request $request)
    {
        $data['getRecord'] = ClassSubject::getRecord();
        $data['header_title'] = 'Assign Subject List';
        return view('admin.assign_subject.list', $data);
    }

    public function add()
    {
        $data['getClass'] = ClassModel::getClass();
        $data['getSubject'] = Subject::getSubject();
        $data['header_title'] = 'Assign Subject';
        return view('admin.assign_subject.add', $data);
    }

    public function assignSubjectAdd(Request $request)
    {
        $request->validate([
            'class_id'   => 'required|exists:classes,id',
            'subject_id' => 'required|array|min:1',
            'subject_id.*' => 'exists:subjects,id',
            'status'     => 'required|in:0,1',
        ]);

        foreach ($request->subject_id as $subject_id) {
            $countAlready = ClassSubject::countAlready($request->class_id, $subject_id);

            if ($countAlready) {
                $countAlready->status = $request->status;
                $countAlready->save();
            } else {
                ClassSubject::create([
                    'class_id'   => $request->class_id,
                    'subject_id' => $subject_id,
                    'status'     => $request->status,
                    'created_by' => Auth::id(),
                ]);
            }
        }

        return redirect()->route('admin.assign-subject.list')
            ->with('success', 'Subject(s) successfully assigned to class.');
    }

    public function assignSubjectEdit($id)
    {
        $assignSubject = ClassSubject::findOrFail($id);

        $data['getClass'] = ClassModel::getClass();
        $data['getSubject'] = Subject::getSubject();
        $data['selectedSubjects'] = ClassSubject::where('class_id', $assignSubject->class_id)
            ->pluck('subject_id')
            ->toArray();

        $data['assignSubject'] = $assignSubject;
        $data['header_title'] = 'Edit Assign Subject';

        return view('admin.assign_subject.add', $data);
    }

    public function assignSubjectUpdate(Request $request, $id)
    {
        $request->validate([
            'class_id'   => 'required|exists:classes,id',
            'subject_id' => 'required|array|min:1',
            'subject_id.*' => 'exists:subjects,id',
            'status'     => 'required|in:0,1',
        ]);

        $assignSubject = ClassSubject::findOrFail($id);

        // Delete old subjects for this class
        ClassSubject::where('class_id', $assignSubject->class_id)->delete();

        // Insert updated subjects
        foreach ($request->subject_id as $subject_id) {
            ClassSubject::create([
                'class_id'   => $request->class_id,
                'subject_id' => $subject_id,
                'status'     => $request->status,
                'created_by' => Auth::id(),
            ]);
        }

        return redirect()->route('admin.assign-subject.list')
            ->with('success', 'Assigned subjects updated successfully.');
    }

    public function assignSubjectDelete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:class_subjects,id',
        ]);

        $assignSubject = ClassSubject::findOrFail($request->id);
        $assignSubject->delete();

        return redirect()->route('admin.assign-subject.list')
            ->with('success', 'Subject deleted successfully from the class.');
    }

    public function singleEdit($id)
    {
        // Get the specific subject assignment
        $assignSubject = ClassSubject::with(['class', 'subject'])->findOrFail($id);

        $data['assignSubject'] = $assignSubject;
        $data['header_title'] = 'Edit Single Subject';

        return view('admin.assign_subject.edit_single', $data);
    }

    public function updateSingleEdit(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:0,1',
        ]);

        $assignSubject = ClassSubject::findOrFail($id);

        // Only update the status of this single subject assignment
        $assignSubject->status = $request->status;
        $assignSubject->save();

        return redirect()->route('admin.assign-subject.list')
            ->with('success', 'Subject status updated successfully.');
    }

    
}
