<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; // NEW

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

    /* -------------------------------------------------
     * Helpers: upsert parent and attach in pivot
     * ------------------------------------------------- */

    /**
     * Create or reuse a parent user in this school, by email.
     * Returns the User model or null if no usable data provided.
     */
    protected function upsertParent(array $data, int $schoolId, ?string $defaultRelationship = null): ?User
    {
        // Require at least an email to identify parent
        $email = strtolower(trim($data['email'] ?? ''));
        if ($email === '') {
            return null; // nothing to do
        }

        // Try to find existing parent by email in same school
        $parent = User::query()
            ->where('role', 'parent')
            ->where('school_id', $schoolId)
            ->where('email', $email)
            ->first();

        // Prepare fields
        $name       = trim($data['name']       ?? '');
        $last_name  = trim($data['last_name']  ?? '');
        $gender     = $data['gender']          ?? null;
        $mobile     = trim($data['mobile']     ?? '');
        $occupation = trim($data['occupation'] ?? '');
        $address    = trim($data['address']    ?? '');
        $status     = isset($data['status']) ? (int)$data['status'] : 1;
        $password   = $data['password'] ?? null;

        if (! $parent) {
            // Create a new parent
            $parent           = new User();
            $parent->school_id     = $schoolId;
            $parent->role          = 'parent';
            $parent->status        = $status;
            $parent->email         = $email;
            $parent->name          = $name !== '' ? $name : 'Parent';
            $parent->last_name     = $last_name;
            $parent->gender        = in_array($gender, ['male','female','other']) ? $gender : null;
            $parent->mobile_number = $mobile;
            $parent->occupation    = $occupation ?: null;
            $parent->address       = $address ?: null;
            $parent->password      = Hash::make(trim($password ?: Str::random(10)));
            $parent->save();
        } else {
            // Optionally update some fields if provided
            $changed = false;
            if ($name      !== '' && $name      !== $parent->name)          { $parent->name = $name; $changed = true; }
            if ($last_name !== '' && $last_name !== $parent->last_name)     { $parent->last_name = $last_name; $changed = true; }
            if ($gender && in_array($gender, ['male','female','other']) && $gender !== $parent->gender) { $parent->gender = $gender; $changed = true; }
            if ($mobile    !== '' && $mobile    !== $parent->mobile_number) { $parent->mobile_number = $mobile; $changed = true; }
            if ($occupation!== '' && $occupation!== (string)$parent->occupation) { $parent->occupation = $occupation; $changed = true; }
            if ($address   !== '' && $address   !== (string)$parent->address)    { $parent->address = $address; $changed = true; }
            if (isset($data['status']) && (int)$data['status'] !== (int)$parent->status) { $parent->status = (int)$data['status']; $changed = true; }
            if ($password) { $parent->password = Hash::make(trim($password)); $changed = true; }
            if ($changed) $parent->save();
        }

        return $parent;
    }

    /**
     * Attach a parent to a student in student_guardians pivot.
     */
    protected function attachGuardian(int $schoolId, int $studentId, int $parentId, ?string $relationship = null, bool $isPrimary = false): void
    {
        // Upsert-like behavior
        $exists = DB::table('student_guardians')
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('parent_id', $parentId)
            ->exists();

        $payload = [
            'relationship' => $relationship ?: null,
            'is_primary'   => $isPrimary ? 1 : 0,
            'updated_at'   => now(),
        ];

        if (! $exists) {
            DB::table('student_guardians')->insert(array_merge($payload, [
                'school_id'  => $schoolId,
                'student_id' => $studentId,
                'parent_id'  => $parentId,
                'created_at' => now(),
            ]));
        } else {
            DB::table('student_guardians')
                ->where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->where('parent_id', $parentId)
                ->update($payload);
        }
    }

    /* -----------------------------
     * ADMIN: Students CRUD
     * ----------------------------- */

    public function list(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $query = User::query()
            ->where('role', 'student')
            ->ofSchool($schoolId)
            ->with('class')
            ->orderBy('name')->orderBy('last_name');

        // 🔎 Filters
        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->name.'%')
                  ->orWhere('last_name', 'like', '%'.$request->name.'%');
            });
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->email.'%');
        }
        if ($request->filled('mobile')) {
            $query->where('mobile_number', 'like', '%'.$request->mobile.'%');
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', (int) $request->status);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', (int) $request->class_id);
        }

        $data['getRecord'] = $query->paginate(20)->appends($request->all());

        // for class filter dropdown
        $data['getClass'] = ClassModel::query()
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get(['id','name']);

        $data['header_title'] = 'Student List';
        return view('admin.student.list', $data);
    }

    public function download($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;
        $schoolId = $this->currentSchoolId();

        $student = User::query()
            ->where('role','student')
            ->ofSchool($schoolId)
            ->with('class')
            ->findOrFail($id);

        // Legacy single parent (kept for backward compatibility)
        $parent = null;
        if ($student->parent_id) {
            $parent = User::query()
                ->where('id', $student->parent_id)
                ->where('role', 'parent')
                ->ofSchool($schoolId)
                ->first();
        }

        $embedFromPublic = function (?string $val, array $dirs) {
            if (!$val) return null;
            $normalized = ltrim(str_replace(['public/', 'storage/'], '', $val), '/');
            $candidates = [$normalized];
            $basename = basename($normalized);
            foreach ($dirs as $d) { $candidates[] = trim($d, '/') . '/' . $basename; }
            $path = null;
            foreach ($candidates as $cand) {
                if (Storage::disk('public')->exists($cand)) { $path = $cand; break; }
            }
            if (!$path) return null;

            $bin = Storage::disk('public')->get($path);
            $mime = 'image/jpeg';
            if (class_exists(\finfo::class)) {
                $fi = new \finfo(FILEINFO_MIME_TYPE);
                $detected = $fi->buffer($bin);
                if ($detected) $mime = $detected;
            } else {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp','bmp'=>'image/bmp'];
                if (isset($map[$ext])) $mime = $map[$ext];
            }
            return 'data:' . $mime . ';base64,' . base64_encode($bin);
        };

        $studentPhoto = $embedFromPublic($student->student_photo, ['student', 'student_photos']);
        $parentPhoto  = $embedFromPublic($parent->parent_photo ?? null, ['parent', 'parent_photos']);

        $data = [
            'user'         => $student,
            'parent'       => $parent,
            'studentPhoto' => $studentPhoto,
            'parentPhoto'  => $parentPhoto,
        ];

        $fileName = Str::slug(trim(($student->name ?? '') . ' ' . ($student->last_name ?? '')) ?: 'student') . '.pdf';
        $pdf = Pdf::loadView('pdf.student_profile', $data)->setPaper('A4', 'portrait');
        return $pdf->stream($fileName, ['Attachment' => false]);
    }

    public function add()
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

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

            // Parent emails are optional but if provided must be valid
            'mother.email'    => ['nullable','email'],
            'father.email'    => ['nullable','email'],
        ]);

        // Create student
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

        // Handle Mother/Father (optional)
        // mother[...], father[...]
        if ($request->has('mother')) {
            $mother = $this->upsertParent($request->input('mother', []), $schoolId, 'mother');
            if ($mother) {
                $this->attachGuardian(
                    $schoolId,
                    $student->id,
                    $mother->id,
                    $request->input('mother.relationship', 'mother'),
                    (bool)$request->boolean('mother.is_primary')
                );
            }
        }

        if ($request->has('father')) {
            $father = $this->upsertParent($request->input('father', []), $schoolId, 'father');
            if ($father) {
                $this->attachGuardian(
                    $schoolId,
                    $student->id,
                    $father->id,
                    $request->input('father.relationship', 'father'),
                    (bool)$request->boolean('father.is_primary')
                );
            }
        }

        return redirect()->route('admin.student.list')->with('success', 'Student added successfully with parent linkage.');
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

            'mother.email'    => ['nullable','email'],
            'father.email'    => ['nullable','email'],
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

        // Re/attach parents if provided
        if ($request->has('mother')) {
            $mother = $this->upsertParent($request->input('mother', []), $schoolId, 'mother');
            if ($mother) {
                $this->attachGuardian(
                    $schoolId,
                    $student->id,
                    $mother->id,
                    $request->input('mother.relationship', 'mother'),
                    (bool)$request->boolean('mother.is_primary')
                );
            }
        }
        if ($request->has('father')) {
            $father = $this->upsertParent($request->input('father', []), $schoolId, 'father');
            if ($father) {
                $this->attachGuardian(
                    $schoolId,
                    $student->id,
                    $father->id,
                    $request->input('father.relationship', 'father'),
                    (bool)$request->boolean('father.is_primary')
                );
            }
        }

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

        // Optional: also remove pivot rows for this student in this school
        DB::table('student_guardians')
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->delete();

        $student->delete();

        return redirect()->route('admin.student.list')->with('success', 'Student deleted successfully.');
    }
}
