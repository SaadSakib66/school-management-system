<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    /* -----------------------------
     * Resolve / guard school context
     * ----------------------------- */
    protected function currentSchoolId(): ?int
    {
        $u = Auth::user();
        if (! $u) return null;

        if ($u->role === 'super_admin') {
            return session('current_school_id') ? (int) session('current_school_id') : null;
        }
        return $u->school_id ? (int) $u->school_id : null;
    }

    protected function guardSchoolContext()
    {
        if (Auth::user()?->role === 'super_admin' && ! $this->currentSchoolId()) {
            return redirect()
                ->route('superadmin.schools.switch')
                ->with('error', 'Please select a school first.');
        }
        return null;
    }

    /* -----------------------------
     * ADMIN: Students CRUD
     * ----------------------------- */

    public function list()
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $data['getRecord'] = User::query()
            ->where('role', 'student')
            ->ofSchool() // <- strictly current school
            ->orderBy('name')->orderBy('last_name')
            ->paginate(20);

        $data['header_title'] = 'Student List';
        return view('admin.student.list', $data);
    }

    public function add()
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        // Only classes for this school
        $data['getClass'] = ClassModel::query()
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id','name']);

        $data['header_title'] = 'Add Student';
        return view('admin.student.add', $data);
    }

    public function insert(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();
        if (! $schoolId) {
            return back()->with('error', 'No school context.')->withInput();
        }

        $request->validate([
            'name'            => ['required','string','max:255'],
            'last_name'       => ['nullable','string','max:255'],
            'email'           => [
                'required','email',
                Rule::unique('users','email')
                    ->where(fn($q)=>$q->where('school_id', $schoolId)->whereNull('deleted_at')),
            ],
            'mobile_number'   => ['required','string','min:10','max:20'],
            'password'        => ['required','string','min:6'],

            // class must belong to this school
            'class_id'        => [
                'required','integer',
                Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id', $schoolId)),
            ],

            'status'          => ['nullable','in:0,1'],
            'gender'          => ['nullable', Rule::in(['male','female','other'])],
            'date_of_birth'   => ['nullable','date'],
            'admission_date'  => ['nullable','date'],
            'student_photo'   => ['nullable','file','mimes:jpg,jpeg,png','max:5120'],

            // optional extra fields
            'admission_number'=> ['nullable','string','max:100'],
            'roll_number'     => ['nullable','string','max:100'],
            'religion'        => ['nullable','string','max:100'],
            'blood_group'     => ['nullable','string','max:10'],
            'height'          => ['nullable','string','max:50'],
            'weight'          => ['nullable','string','max:50'],
        ]);

        $student = new User();
        $student->school_id        = $schoolId;
        $student->name             = trim($request->name);
        $student->last_name        = trim((string) $request->last_name);
        $student->admission_number = trim((string) $request->admission_number);
        $student->roll_number      = trim((string) $request->roll_number);
        $student->class_id         = (int) $request->class_id;
        $student->gender           = $request->gender ?: null;
        $student->date_of_birth    = $request->date_of_birth ?: null;
        $student->religion         = $request->religion ?: null;
        $student->mobile_number    = trim($request->mobile_number);
        $student->admission_date   = $request->admission_date ?: null;
        $student->blood_group      = $request->blood_group ?: null;
        $student->height           = $request->height ?: null;
        $student->weight           = $request->weight ?: null;
        $student->status           = (int) ($request->status ?? 1);
        $student->email            = strtolower(trim($request->email));
        $student->password         = Hash::make(trim($request->password));
        $student->role             = 'student';

        if ($request->hasFile('student_photo')) {
            $student->student_photo = $request->file('student_photo')->store('student', 'public');
        }

        $student->save();

        return redirect()->route('admin.student.list')->with('success', 'Student added successfully.');
    }

    public function edit($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $data['user'] = User::query()
            ->where('role','student')
            ->ofSchool($schoolId)
            ->findOrFail($id);

        $data['getClass'] = ClassModel::query()
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get(['id','name']);

        $data['header_title'] = 'Edit Student';
        return view('admin.student.add', $data);
    }

    public function update(Request $request, $id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $student = User::query()
            ->where('role','student')
            ->ofSchool($schoolId)
            ->findOrFail($id);

        $request->validate([
            'name'            => ['required','string','max:255'],
            'last_name'       => ['nullable','string','max:255'],
            'email'           => [
                'required','email',
                Rule::unique('users','email')
                    ->ignore($student->id)
                    ->where(fn($q)=>$q->where('school_id', $schoolId)->whereNull('deleted_at')),
            ],
            'mobile_number'   => ['required','string','min:10','max:20'],
            'password'        => ['nullable','string','min:6'],

            'class_id'        => [
                'required','integer',
                Rule::exists('classes','id')->where(fn($q)=>$q->where('school_id', $schoolId)),
            ],

            'status'          => ['nullable','in:0,1'],
            'gender'          => ['nullable', Rule::in(['male','female','other'])],
            'date_of_birth'   => ['nullable','date'],
            'admission_date'  => ['nullable','date'],
            'student_photo'   => ['nullable','file','mimes:jpg,jpeg,png','max:5120'],

            'admission_number'=> ['nullable','string','max:100'],
            'roll_number'     => ['nullable','string','max:100'],
            'religion'        => ['nullable','string','max:100'],
            'blood_group'     => ['nullable','string','max:10'],
            'height'          => ['nullable','string','max:50'],
            'weight'          => ['nullable','string','max:50'],
        ]);

        $student->name             = trim($request->name);
        $student->last_name        = trim((string) $request->last_name);
        $student->admission_number = trim((string) $request->admission_number);
        $student->roll_number      = trim((string) $request->roll_number);
        $student->class_id         = (int) $request->class_id;
        $student->gender           = $request->gender ?: null;
        $student->date_of_birth    = $request->date_of_birth ?: $student->date_of_birth;
        $student->religion         = $request->religion ?: null;
        $student->mobile_number    = trim($request->mobile_number);
        $student->admission_date   = $request->admission_date ?: $student->admission_date;
        $student->blood_group      = $request->blood_group ?: null;
        $student->height           = $request->height ?: null;
        $student->weight           = $request->weight ?: null;
        $student->status           = (int) ($request->status ?? $student->status);
        $student->email            = strtolower(trim($request->email));
        $student->role             = 'student';

        if (!empty($request->password)) {
            $student->password = Hash::make(trim($request->password));
        }

        if ($request->hasFile('student_photo')) {
            if ($student->student_photo && Storage::disk('public')->exists($student->student_photo)) {
                Storage::disk('public')->delete($student->student_photo);
            }
            $student->student_photo = $request->file('student_photo')->store('student','public');
        }

        $student->save();

        return redirect()->route('admin.student.list')->with('success', 'Student updated successfully.');
    }

    public function delete(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $request->validate([
            'id' => ['required','integer','exists:users,id'],
        ]);

        $schoolId = $this->currentSchoolId();

        $student = User::query()
            ->where('role','student')
            ->ofSchool($schoolId)
            ->findOrFail((int) $request->id);

        if ($student->student_photo && Storage::disk('public')->exists($student->student_photo)) {
            Storage::disk('public')->delete($student->student_photo);
        }

        $student->delete();

        return redirect()->route('admin.student.list')->with('success', 'Student deleted successfully.');
    }
}
