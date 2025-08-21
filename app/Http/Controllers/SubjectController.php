<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    public function subjectList(Request $request)
    {
        $data['getRecord'] = Subject::getRecord();
        $data['header_title'] = 'Subject List';
        return view('admin.subject.list', $data);
    }

    public function add()
    {
        $data['header_title'] = 'Subject Add';
        return view('admin.subject.add', $data);
    }

    public function subjectAdd(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ]);

        $subject = new Subject();
        $subject->name = $request->name;
        $subject->type = $request->type;
        $subject->status = $request->status;
        $subject->created_by = Auth::id();
        $subject->save();

        return redirect()->route('admin.subject.list')->with('success', 'Subject added successfully.');
    }

    public function subjectEdit($id)
    {
        $subject = Subject::findOrFail($id);
        $data['subject'] = $subject;
        $data['header_title'] = 'Edit Subject';
        return view('admin.subject.add', $data); // reuse the same form
    }

    public function subjectUpdate(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ]);

        $subject->name = $request->name;
        $subject->type = $request->type;
        $subject->status = $request->status;
        $subject->save();

        return redirect()->route('admin.subject.list')->with('success', 'Subject updated successfully.');
    }

    public function subjectDelete(Request $request)
    {
        $subject = Subject::findOrFail($request->id);
        $subject->delete();

        return redirect()->route('admin.subject.list')->with('success', 'Subject deleted successfully.');
    }
}
