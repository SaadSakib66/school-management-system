<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    //

    public function list()
    {
        $data['getRecord'] = User::getStudents();
        $data['header_title'] = 'Student List';
        return view('admin.student.list', $data);
    }

    public function add()
    {
        $data['getClass'] = ClassModel::getClass();
        $data['header_title'] = 'Add Student';
        return view('admin.student.add', $data);
    }

    public function insert(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'mobile_number' => 'required|min:11',
            'password' => 'required|string|min:6',
            'class_id' => 'required|exists:classes,id',
            'role' => 'required',
            'status' => 'nullable|in:0,1', 
            'date_of_birth' => 'nullable|date',
            'admission_date' => 'nullable|date',
        ]);

        $student = new User();
        $student->name = trim($request->name);
        $student->last_name = trim($request->last_name);
        $student->admission_number = trim($request->admission_number);
        $student->roll_number = trim($request->roll_number);
        $student->class_id = $request->class_id;
        $student->gender = $request->gender;
        $student->date_of_birth = $request->date_of_birth ? date('Y-m-d', strtotime($request->date_of_birth)) : null;
        $student->religion = $request->religion;
        $student->mobile_number = $request->mobile_number;
        $student->admission_date = $request->admission_date ? date('Y-m-d', strtotime($request->admission_date)) : null;
        $student->blood_group = $request->blood_group;
        $student->height = $request->height;
        $student->weight = $request->weight;
        $student->status = $request->status ?? 1; // Default Active
        $student->email = trim($request->email);
        $student->password = Hash::make(trim($request->password));

        if ($request->hasFile('student_photo')) {
            $path = $request->file('student_photo')->store('student', 'public');
            $student->student_photo = $path;
        }

        $student->role = 'student'; // Hardcode role since this form is only for students
        $student->save();

        return redirect()->route('admin.student.list')->with('success', 'Student added successfully.');
    }

    public function edit($id)
    {
        $data['user'] = User::findOrFail($id);
        $data['getClass'] = ClassModel::getClass();
        $data['header_title'] = 'Edit Student';
        return view('admin.student.add', $data);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'mobile_number' => 'required|min:11',
            'class_id' => 'required|exists:classes,id',
            'role' => 'required',
            'status' => 'nullable|in:0,1',
            'date_of_birth' => 'nullable|date',
            'admission_date' => 'nullable|date',
            'password' => 'nullable|string|min:6', // only if new password entered
        ]);

        $student = User::findOrFail($id);

        $student->name = trim($request->name);
        $student->last_name = trim($request->last_name);
        $student->admission_number = trim($request->admission_number);
        $student->roll_number = trim($request->roll_number);
        $student->class_id = $request->class_id;
        $student->gender = $request->gender;
        $student->date_of_birth = $request->date_of_birth ? date('Y-m-d', strtotime($request->date_of_birth)) : $student->date_of_birth;
        $student->religion = $request->religion;
        $student->mobile_number = $request->mobile_number;
        $student->admission_date = $request->admission_date ? date('Y-m-d', strtotime($request->admission_date)) : $student->admission_date;
        $student->blood_group = $request->blood_group;
        $student->height = $request->height;
        $student->weight = $request->weight;
        $student->status = $request->status ?? $student->status;
        $student->email = trim($request->email);

        // Only update password if provided
        if (!empty($request->password)) {
            $student->password = Hash::make(trim($request->password));
        }

        // Handle photo upload
        if ($request->hasFile('student_photo')) {
            // delete old photo if exists
            if ($student->student_photo && Storage::disk('public')->exists($student->student_photo)) {
                Storage::disk('public')->delete($student->student_photo);
            }

            $path = $request->file('student_photo')->store('student', 'public');
            $student->student_photo = $path;
        }

        $student->role = 'student'; // hardcoded, since this form is only for students
        $student->save();

        return redirect()->route('admin.student.list')->with('success', 'Student updated successfully.');
    }


    public function delete(Request $request)
    {
        // Logic to delete a student
        $student = User::findOrFail($request->id);
        $student->delete();

        return redirect()->route('admin.student.list')->with('success', 'Student deleted successfully.');
    }
}
