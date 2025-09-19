<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /** Quick helper: only admin or super admin may toggle their own status */
    private function canChangeOwnStatus(): bool
    {
        $u = Auth::user();
        return $u && in_array($u->role, ['admin', 'super_admin'], true);
    }

    /**
     * Show "My Account" for the logged-in user by role.
     * - super_admin WITHOUT school context → superadmin.account
     * - super_admin WITH school context   → admin.account (acting-as)
     */
    public function myAccount()
    {
        $user = Auth::user();
        $data['header_title']   = 'My Profile';
        $data['user']           = $user;
        $data['canChangeStatus']= $this->canChangeOwnStatus();   // ⬅ pass flag to view
        $data['getClass']       = ClassModel::getClass();

        switch ($user->role) {
            case 'teacher':
                return view('teacher.account', $data);

            case 'student':
                $data['user']->loadMissing('class.subjects');
                return view('student.account', $data);

            case 'parent':
                $data['assignedStudents'] = $data['user']->children()
                    ->with(['class' => function ($q) { $q->with('subjects'); }])
                    ->paginate(10);
                return view('parent.account', $data);

            case 'admin':
                return view('admin.account', $data);

            case 'super_admin':
                if (session()->has('current_school_id')) {
                    return view('admin.account', $data);
                }
                return view('superadmin.account', $data);
        }

        abort(403, 'Unauthorized action.');
    }

    /**
     * Show edit page for "My Account" by role.
     * Mirrors myAccount() routing behavior.
     */
    public function editMyAccount()
    {
        $user = Auth::user();
        $data['header_title']   = 'Edit Profile';
        $data['user']           = $user;
        $data['canChangeStatus']= $this->canChangeOwnStatus();   // ⬅ pass flag to edit view

        switch ($user->role) {
            case 'teacher':
                return view('teacher.edit', $data);

            case 'student':
                return view('student.edit', $data);

            case 'parent':
                return view('parent.edit', $data);

            case 'admin':
                return view('admin.edit', $data);

            case 'super_admin':
                if (session()->has('current_school_id')) {
                    return view('admin.edit', $data);
                }
                return view('superadmin.edit', $data);
        }

        abort(403, 'Unauthorized action.');
    }

    /** ---------------- Teacher self-update (unchanged re: status) ---------------- */
    public function updateMyAccount(Request $request)
    {
        $teacher = Auth::user();
        if ($teacher->role !== 'teacher') abort(403, 'Unauthorized.');

        $request->validate([
            'name'          => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => [
                'required','email',
                Rule::unique('users', 'email')
                    ->where(fn($q) => $q->where('school_id', $teacher->school_id))
                    ->ignore($teacher->id),
            ],
            'mobile_number' => 'required|min:11',
            'password'      => 'nullable|string|min:6',
            'address'       => 'nullable|string|max:255',
            'gender'        => 'required|in:male,female,other',
            // no 'status' here on purpose
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

    /** ---------------- Student self-update (unchanged re: status) ---------------- */
    public function updateMyAccountStudent(Request $request)
    {
        $student = Auth::user();
        if ($student->role !== 'student') abort(403, 'Unauthorized.');

        $request->validate([
            'name'           => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'gender'         => 'required|in:male,female,other',
            'date_of_birth'  => 'required|date',
            'religion'       => 'nullable|string|max:255',
            'mobile_number'  => 'required|string|max:20',
            'email'          => [
                'required','email',
                Rule::unique('users','email')
                    ->where(fn($q) => $q->where('school_id', $student->school_id))
                    ->ignore($student->id),
            ],
            'password'       => 'nullable|string|min:6',
            'student_photo'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'blood_group'    => 'nullable|string|max:10',
            'height'         => 'nullable|string|max:10',
            'weight'         => 'nullable|string|max:10',
            'address'        => 'nullable|string|max:255',
            // no 'status'
        ]);

        $student->name          = trim($request->name);
        $student->last_name     = trim($request->last_name);
        $student->gender        = $request->gender;
        $student->date_of_birth = $request->date_of_birth;
        $student->religion      = $request->religion;
        $student->mobile_number = $request->mobile_number;
        $student->email         = trim($request->email);
        $student->blood_group   = $request->blood_group;
        $student->height        = $request->height;
        $student->weight        = $request->weight;
        $student->address       = $request->address;

        if (!empty($request->password)) {
            $student->password = bcrypt($request->password);
        }

        if ($request->hasFile('student_photo')) {
            if (!empty($student->student_photo) && Storage::disk('public')->exists($student->student_photo)) {
                Storage::disk('public')->delete($student->student_photo);
            }
            $student->student_photo = $request->file('student_photo')->store('student_photos', 'public');
        }

        $student->save();
        return redirect()->route('student.account')->with('success', 'Profile updated successfully.');
    }

    /** ---------------- Parent self-update (unchanged re: status) ---------------- */
    public function updateMyAccountParent(Request $request)
    {
        $parent = Auth::user();
        if ($parent->role !== 'parent') abort(403, 'Unauthorized.');

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'gender'        => 'required|in:male,female,other',
            'mobile_number' => 'required|string|max:20',
            'occupation'    => 'required|string|max:255',
            'email'         => [
                'required','email',
                Rule::unique('users','email')
                    ->where(fn($q) => $q->where('school_id', $parent->school_id))
                    ->ignore($parent->id),
            ],
            'password'      => 'nullable|string|min:6',
            'address'       => 'nullable|string|max:255',
            'parent_photo'  => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            // no 'status'
        ]);

        $parent->name          = trim($validated['name']);
        $parent->last_name     = trim($validated['last_name']);
        $parent->gender        = $validated['gender'];
        $parent->mobile_number = trim($validated['mobile_number']);
        $parent->occupation    = trim($validated['occupation']);
        $parent->email         = strtolower($validated['email']);
        $parent->address       = $validated['address'] ?? null;

        if (!empty($validated['password'])) {
            $parent->password = bcrypt($validated['password']);
        }

        if ($request->hasFile('parent_photo')) {
            if (!empty($parent->parent_photo) && Storage::disk('public')->exists($parent->parent_photo)) {
                Storage::disk('public')->delete($parent->parent_photo);
            }
            $parent->parent_photo = $request->file('parent_photo')->store('parent_photos', 'public');
        }

        $parent->save();
        return redirect()->route('parent.account')->with('success', 'Profile updated successfully.');
    }

    /** ---------------- Admin (or acting super_admin) self-update — can change status ---------------- */
    public function updateMyAccountAdmin(Request $request)
    {
        $admin = Auth::user();
        if (!in_array($admin->role, ['admin', 'super_admin'])) abort(403, 'Unauthorized.');

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => [
                'required','email',
                Rule::unique('users','email')
                    ->when($admin->role === 'admin' || session()->has('current_school_id'), function ($r) use ($admin) {
                        return $r->where('school_id', $admin->school_id);
                    })
                    ->ignore($admin->id),
            ],
            'password'    => 'nullable|string|min:6',
            'admin_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'last_name'   => 'nullable|string|max:255',
            'gender'      => 'nullable|in:male,female,other',
            'mobile_number' => 'nullable|string|max:20',
            'address'     => 'nullable|string|max:255',
            'status'      => 'nullable|in:0,1',          // ⬅ allow status here
        ]);

        $admin->name          = trim($request->name);
        $admin->last_name     = trim($request->last_name ?? '');
        $admin->gender        = $request->gender ?? $admin->gender;
        $admin->email         = trim($request->email);
        $admin->mobile_number = $request->mobile_number ?? $admin->mobile_number;
        $admin->address       = trim($request->address ?? '');

        if (!empty($validated['password'])) {
            $admin->password = bcrypt($validated['password']);
        }

        if (array_key_exists('status', $validated)) {     // ⬅ update if provided
            $admin->status = (int) $validated['status'];
        }

        if ($request->hasFile('admin_photo')) {
            if (!empty($admin->admin_photo) && Storage::disk('public')->exists($admin->admin_photo)) {
                Storage::disk('public')->delete($admin->admin_photo);
            }
            $admin->admin_photo = $request->file('admin_photo')->store('admin_photos', 'public');
        }

        $admin->save();

        if ($admin->role === 'admin' || session()->has('current_school_id')) {
            return redirect()->route('admin.account')->with('success', 'Profile updated successfully.');
        }

        return redirect()->route('superadmin.account')->with('success', 'Profile updated successfully.');
    }

    /** ---------------- Super Admin self-update — can change status ---------------- */
    public function updateMyAccountSuperAdmin(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'super_admin') abort(403, 'Unauthorized.');

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'password'    => 'nullable|string|min:6',
            'admin_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'last_name'   => 'nullable|string|max:255',
            'gender'      => 'nullable|in:male,female,other',
            'mobile_number' => 'nullable|string|max:20',
            'address'     => 'nullable|string|max:255',
            'status'      => 'nullable|in:0,1',          // ⬅ allow status here
        ]);

        $user->name          = trim($validated['name']);
        $user->last_name     = trim($request->last_name ?? '');
        $user->gender        = $request->gender ?? $user->gender;
        $user->email         = trim($validated['email']);
        $user->mobile_number = $request->mobile_number ?? $user->mobile_number;
        $user->address       = trim($request->address ?? '');

        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        if (array_key_exists('status', $validated)) {     // ⬅ update if provided
            $user->status = (int) $validated['status'];
        }

        if ($request->hasFile('admin_photo')) {
            if (!empty($user->admin_photo) && Storage::disk('public')->exists($user->admin_photo)) {
                Storage::disk('public')->delete($user->admin_photo);
            }
            $user->admin_photo = $request->file('admin_photo')->store('superadmin_photos', 'public');
        }

        $user->save();

        if (session()->has('current_school_id')) {
            return redirect()->route('admin.account')->with('success', 'Profile updated successfully.');
        }

        return redirect()->route('superadmin.account')->with('success', 'Profile updated successfully.');
    }
}
