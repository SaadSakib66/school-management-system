<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    //
    public function list()
    {
        $data['getRecord'] = User::getTeachers();
        $data['header_title'] = 'Teacher List';
        return view('admin.teacher.list', $data);
    }

    public function add()
    {
        $data['header_title'] = 'Add Teacher';
        return view('admin.teacher.add', $data);
    }

    public function insert(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'mobile_number' => 'required|min:11',
            'password' => 'required|string|min:6',
            'role' => 'required|in:teacher',
            'status' => 'nullable|in:0,1',
            'address' => 'nullable|string|max:255',
        ]);

        $teacher = new User();
        $teacher->name = trim($request->name);
        $teacher->last_name = trim($request->last_name);
        $teacher->gender = $request->gender;
        $teacher->email = trim($request->email);
        $teacher->mobile_number = $request->mobile_number;
        $teacher->address = $request->address;
        $teacher->password = Hash::make($request->password);
        $teacher->role = $request->role;
        $teacher->status = $request->status ?? 1; // Default Active
        $teacher->address = trim($request->address);

        if ($request->hasFile('teacher_photo')) {
            $path = $request->file('teacher_photo')->store('teacher', 'public');
            $teacher->teacher_photo = $path;
        }
        $teacher->save();
        return redirect()->route('admin.teacher.list')->with('success', 'Teacher added successfully.');
    }

    public function edit($id)
    {
        $data['user'] = User::findOrFail($id);
        $data['header_title'] = 'Edit Teacher';
        return view('admin.teacher.add', $data);
    }

    public function update(Request $request, $id)
    {
        $teacher = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $teacher->id, // ignore current teacher's email
            'mobile_number' => 'required|min:11',
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:teacher',
            'status' => 'nullable|in:0,1',
            'address' => 'nullable|string|max:255',
        ]);

        // Update fields
        $teacher->name = trim($request->name);
        $teacher->last_name = trim($request->last_name);
        $teacher->gender = $request->gender;
        $teacher->email = trim($request->email);
        $teacher->mobile_number = $request->mobile_number;
        $teacher->address = trim($request->address);
        $teacher->status = $request->status ?? 1;

        // Only update password if provided and different
        if (!empty($request->password)) {
            if (!Hash::check($request->password, $teacher->password)) {
                // Password is different, so update
                $teacher->password = Hash::make($request->password);
            }
        }

        // Handle teacher photo
        if ($request->hasFile('teacher_photo')) {
            // Delete old photo if exists
            if ($teacher->teacher_photo && Storage::disk('public')->exists($teacher->teacher_photo)) {
                Storage::disk('public')->delete($teacher->teacher_photo);
            }

            $path = $request->file('teacher_photo')->store('teacher', 'public');
            $teacher->teacher_photo = $path;
        }

        $teacher->save();

        return redirect()->route('admin.teacher.list')->with('success', 'Teacher updated successfully.');
    }

    public function delete(Request $request)
    {
        $id = $request->input('id');
        $teacher = User::findOrFail($id);

        // Delete teacher photo if exists
        if ($teacher->teacher_photo && Storage::disk('public')->exists($teacher->teacher_photo)) {
            Storage::disk('public')->delete($teacher->teacher_photo);
        }

        $teacher->delete();

        return redirect()->route('admin.teacher.list')->with('success', 'teacher deleted successfully.');
    }

    
}
