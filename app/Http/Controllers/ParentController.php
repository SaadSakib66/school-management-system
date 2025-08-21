<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ParentController extends Controller
{
    public function list()
    {
        $data['getRecord'] = User::getParents();
        $data['header_title'] = 'Parent List';
        return view('admin.parent.list', $data);
    }

    public function add()
    {
        $data['header_title'] = 'Add Parent';
        return view('admin.parent.add', $data);
    }

    public function insert(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'mobile_number' => 'required|min:11',
            'password' => 'required|string|min:6',
            'role' => 'required|in:parent',
            'status' => 'nullable|in:0,1',
            'address' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
        ]);

        $parent = new User();
        $parent->name = trim($request->name);
        $parent->last_name = trim($request->last_name);
        $parent->gender = $request->gender;
        $parent->email = trim($request->email);
        $parent->mobile_number = $request->mobile_number;
        $parent->occupation = $request->occupation;
        $parent->address = $request->address;
        $parent->password = Hash::make($request->password);
        $parent->role = $request->role;
        $parent->status = $request->status ?? 1; // Default Active
        $parent->address = trim($request->address);

        if ($request->hasFile('parent_photo')) {
            $path = $request->file('parent_photo')->store('parent', 'public');
            $parent->parent_photo = $path;
        }
        $parent->save();
        return redirect()->route('admin.parent.list')->with('success', 'Parent added successfully.');
    }

    public function edit($id)
    {
        $data['user'] = User::findOrFail($id);
        $data['header_title'] = 'Edit Parent';
        return view('admin.parent.add', $data);
    }

    public function update(Request $request, $id)
    {
        $parent = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $parent->id, // ignore current parent's email
            'mobile_number' => 'required|min:11',
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:parent',
            'status' => 'nullable|in:0,1',
            'address' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
        ]);

        // Update fields
        $parent->name = trim($request->name);
        $parent->last_name = trim($request->last_name);
        $parent->gender = $request->gender;
        $parent->email = trim($request->email);
        $parent->mobile_number = $request->mobile_number;
        $parent->occupation = $request->occupation;
        $parent->address = trim($request->address);
        $parent->status = $request->status ?? 1;

        // Only update password if provided
        if (!empty($request->password)) {
            $parent->password = Hash::make($request->password);
        }

        // Handle parent photo
        if ($request->hasFile('parent_photo')) {
            // Delete old photo if exists
            if ($parent->parent_photo && Storage::disk('public')->exists($parent->parent_photo)) {
                Storage::disk('public')->delete($parent->parent_photo);
            }

            $path = $request->file('parent_photo')->store('parent', 'public');
            $parent->parent_photo = $path;
        }

        $parent->save();

        return redirect()->route('admin.parent.list')->with('success', 'Parent updated successfully.');
    }

    public function delete(Request $request)
    {
        $id = $request->input('id');
        $parent = User::findOrFail($id);

        // Delete parent photo if exists
        if ($parent->parent_photo && Storage::disk('public')->exists($parent->parent_photo)) {
            Storage::disk('public')->delete($parent->parent_photo);
        }

        $parent->delete();

        return redirect()->route('admin.parent.list')->with('success', 'Parent deleted successfully.');
    }

    public function addMyStudent(Request $request, $id)
    {
        $parent = User::findOrFail($id);

        $data['parent_id'] = $id;
        $data['getRecord'] = User::getSearchStudents($request);
        $data['assignedStudents'] = $parent->children;
        $data['header_title'] = 'Parent Student List';

        return view('admin.parent.my_student', $data);
    }

    public function assignStudent(Request $request)
    {
        $student = User::where('role', 'student')->findOrFail($request->student_id);
        $student->parent_id = $request->parent_id;
        $student->save();

        return back()->with('success', 'Student assigned successfully!');
    }

    public function removeStudent(Request $request)
    {
        $student = User::where('role', 'student')->findOrFail($request->student_id);
        $student->parent_id = null;
        $student->save();

        return back()->with('success', 'Student removed successfully!');
    }





}
