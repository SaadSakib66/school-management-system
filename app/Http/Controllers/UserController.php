<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{

    public function myAccount()
    {
        $data['getClass'] = ClassModel::getClass();
        $data['header_title'] = 'My Profile';
        $data['user'] = Auth::user();

        if ($data['user']->role === 'teacher') {
            return view('teacher.account', $data);

        } elseif ($data['user']->role === 'student') {
            // Load class + subjects for the logged-in student
            $data['user']->loadMissing('class.subjects');
            return view('student.account', $data);

        } elseif ($data['user']->role === 'parent') {
            // eager-load class to avoid N+1
            $data['assignedStudents'] = $data['user']->children()
                ->with(['class' => function ($q)
                {
                    $q->with('subjects'); // nested eager-load
                }])
                ->paginate(10);
                
            return view('parent.account', $data);

        } elseif ($data['user']->role === 'admin') {
            return view('admin.account', $data);

        }

        abort(403, 'Unauthorized action.');
    }


    public function editMyAccount()
    {
        $data['header_title'] = 'Edit Profile';
        $data['user'] = Auth::user();

        if ($data['user']->role === 'teacher') {
            return view('teacher.edit', $data);
        } elseif ($data['user']->role === 'student') {
            return view('student.edit', $data);
        } elseif ($data['user']->role === 'parent') {
            return view('parent.edit', $data);
        } elseif ($data['user']->role === 'admin') {
            return view('admin.edit', $data);
        }

        abort(403, 'Unauthorized action.');
    }


    public function updateMyAccount(Request $request)
    {
        $teacher = Auth::user(); // no $id needed

        $request->validate([
            'name'          => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $teacher->id,
            'mobile_number' => 'required|min:11',
            'password'      => 'nullable|string|min:6',
            'address'       => 'nullable|string|max:255',
            'gender'        => 'required|in:male,female,other',
            // (optional) ignore role/status for self-edit
        ]);

        $teacher->name          = trim($request->name);
        $teacher->last_name     = trim($request->last_name);
        $teacher->gender        = $request->gender;
        $teacher->email         = trim($request->email);
        $teacher->mobile_number = $request->mobile_number;
        $teacher->address       = trim($request->address);

        if ($request->filled('password') && !Hash::check($request->password, $teacher->password)) {
            $teacher->password = Hash::make($request->password);
        }

        if ($request->hasFile('teacher_photo')) {
            if ($teacher->teacher_photo && Storage::disk('public')->exists($teacher->teacher_photo)) {
                Storage::disk('public')->delete($teacher->teacher_photo);
            }
            $teacher->teacher_photo = $request->file('teacher_photo')->store('teacher', 'public');
        }

        $teacher->save();

        return redirect()->route('teacher.account')->with('success', 'Profile updated successfully.');
    }

    public function updateMyAccountStudent(Request $request)
    {
        $student = Auth::user();

        $request->validate([
            'name'           => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'gender'         => 'required|in:male,female,other',
            'date_of_birth'  => 'required|date',
            'religion'       => 'nullable|string|max:255',
            'mobile_number'  => 'required|string|max:20',
            'email'          => 'required|email|unique:users,email,' . $student->id,
            'password'       => 'nullable|string|min:6',
            'student_photo'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'blood_group'    => 'nullable|string|max:10',
            'height'         => 'nullable|string|max:10',
            'weight'         => 'nullable|string|max:10',
            'address'        => 'nullable|string|max:255',
        ]);

        // Update basic info
        $student->name          = trim($request->name);
        $student->last_name     = trim($request->last_name);
        $student->gender        = $request->gender;
        $student->date_of_birth = $request->date_of_birth;
        $student->religion      = $request->religion;
        $student->mobile_number = $request->mobile_number;
        $student->email         = $request->email;
        $student->blood_group   = $request->blood_group;
        $student->height        = $request->height;
        $student->weight        = $request->weight;
        $student->address       = $request->address;

        // Update password only if provided
        if (!empty($request->password)) {
            $student->password = bcrypt($request->password);
        }

        // Handle student photo upload
        if ($request->hasFile('student_photo')) {
            // Delete old photo if exists
            if (!empty($student->student_photo) && \Storage::exists($student->student_photo)) {
                \Storage::delete($student->student_photo);
            }

            $path = $request->file('student_photo')->store('student_photos', 'public');
            $student->student_photo = $path;
        }

        $student->save();

        return redirect()
            ->route('student.account')
            ->with('success', 'Profile updated successfully.');
    }

    public function updateMyAccountParent(Request $request)
    {
        $parent = Auth::user();

        // Optional: ensure only parents hit this endpoint
        if ($parent->role !== 'parent') {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'gender'        => 'required|in:male,female,other',
            'mobile_number' => 'required|string|max:20',
            'occupation'    => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $parent->id,
            'password'      => 'nullable|string|min:6',
            'address'       => 'nullable|string|max:255',
            'parent_photo'  => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        // Basic fields
        $parent->name          = trim($validated['name']);
        $parent->last_name     = trim($validated['last_name']);
        $parent->gender        = $validated['gender'];
        $parent->mobile_number = trim($validated['mobile_number']);
        $parent->occupation    = trim($validated['occupation']);
        $parent->email         = strtolower($validated['email']);
        $parent->address       = $validated['address'] ?? null;

        // Password (only if provided)
        if (!empty($validated['password'])) {
            $parent->password = bcrypt($validated['password']);
        }

        // Photo upload
        if ($request->hasFile('parent_photo')) {
            // Delete old photo if exists on public disk
            if (!empty($parent->parent_photo) && \Storage::disk('public')->exists($parent->parent_photo)) {
                \Storage::disk('public')->delete($parent->parent_photo);
            }

            $path = $request->file('parent_photo')->store('parent_photos', 'public');
            $parent->parent_photo = $path;
        }

        $parent->save();

        return redirect()
            ->route('parent.account')
            ->with('success', 'Profile updated successfully.');
    }

    public function updateMyAccountAdmin(Request $request)
    {
        $admin = Auth::user();

        // Optional: ensure only parents hit this endpoint
        if ($admin->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $admin->id,
            'password'      => 'nullable|string|min:6',
            'admin_photo'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $admin->name          = trim($request->name);
        $admin->last_name     = trim($request->last_name);
        $admin->gender        = $request->gender;
        $admin->email         = trim($request->email);
        $admin->mobile_number = $request->mobile_number;
        $admin->address       = trim($request->address);

        // Password (only if provided)
        if (!empty($validated['password'])) {
            $admin->password = bcrypt($validated['password']);
        }

        // Photo upload
        if ($request->hasFile('admin_photo')) {
            // Delete old photo if exists on public disk
            if (!empty($admin->admin_photo) && \Storage::disk('public')->exists($admin->admin_photo)) {
                \Storage::disk('public')->delete($admin->admin_photo);
            }

            $path = $request->file('admin_photo')->store('admin_photos', 'public');
            $admin->admin_photo = $path;
        }

        $admin->save();

        return redirect()->route('admin.account')->with('success', 'Profile updated successfully.');
    }






















    // public function changePassword(){
    //     $data['header_title'] = 'Change Password';
    //     return view('admin.change_password', $data);
    // }

    // public function updatePassword(Request $request)
    // {
    //     $request->validate([
    //         'current_password' => 'required',
    //         'new_password' => 'required|min:6|confirmed',
    //     ]);

    //     $user = Auth::user();

    //     if (!Hash::check($request->current_password, $user->password)) {
    //         return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect.']);
    //     }

    //     $user->password = Hash::make($request->new_password);
    //     $user->save();

    //     // Redirect based on role
    //     if ($user->role == 'admin') {
    //         return redirect()->route('admin.dashboard')->with('success', 'Password updated successfully.');
    //     }
    //     elseif ($user->role == 'teacher') {
    //         return redirect()->route('teacher.dashboard')->with('success', 'Password updated successfully.');
    //     }
    //     elseif ($user->role == 'student') {
    //         return redirect()->route('student.dashboard')->with('success', 'Password updated successfully.');
    //     }
    //     elseif ($user->role == 'parent') {
    //         return redirect()->route('parent.dashboard')->with('success', 'Password updated successfully.');
    //     }

    //     // Fallback in case role is unexpected
    //     Auth::logout();
    //     return redirect()->route('admin.login')->with('success', 'Password updated successfully. Please log in again.');
    // }





}
