<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Auth;

class ClassController extends Controller
{
    public function classList(Request $request)
    {
        $data['getRecord'] = ClassModel::getRecord();
        $data['header_title'] = 'Class List';
        return view('admin.class.list', $data);
    }

    public function add()
    {
        $data['header_title'] = 'Class Add';
        return view('admin.class.add', $data);
    }

    public function classAdd(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $class = new ClassModel();
        $class->name = $request->name;
        $class->status = $request->status;
        $class->created_by = Auth::id();
        $class->save();

        return redirect()->route('admin.class.list')->with('success', 'Class added successfully.');
    }

    public function classEdit($id)
    {
        $class = ClassModel::findOrFail($id);
        $data['class'] = $class;
        $data['header_title'] = 'Edit Class';
        return view('admin.class.add', $data); // reuse the same form
    }

    public function classUpdate(Request $request, $id)
    {
        $class = ClassModel::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $class->name = $request->name;
        $class->status = $request->status; // Default to active if not provided
        $class->save();

        return redirect()->route('admin.class.list')->with('success', 'Class updated successfully.');
    }

    public function classDelete(Request $request)
    {
        $class = ClassModel::findOrFail($request->id);
        $class->delete();

        return redirect()->route('admin.class.list')->with('success', 'Class deleted successfully.');
    }
}
