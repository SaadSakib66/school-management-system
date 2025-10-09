<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;


class TeacherController extends Controller
{
    /* -----------------------------
     * Helpers (multi-school)
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
     * Admin: Teacher CRUD
     * ----------------------------- */

public function list(Request $request)
{
    if ($resp = $this->guardSchoolContext()) return $resp;

    $query = User::query()
        ->where('role', 'teacher')
        ->ofSchool() // strictly current school via your scope
        ->orderBy('name');

    // 🔍 Filters
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

    $data['getRecord'] = $query->paginate(20)->appends($request->all());
    $data['header_title'] = 'Teacher List';

    return view('admin.teacher.list', $data);
}


public function download($id)
{
    if ($resp = $this->guardSchoolContext()) return $resp;

    $schoolId = $this->currentSchoolId();

    $teacher = User::query()
        ->where('role','teacher')
        ->ofSchool($schoolId)
        ->findOrFail($id);

    // ---------- normalize & embed teacher photo from public/storage/teacher ----------
    $photoSrc  = null;
    $photoVal  = $teacher->teacher_photo; // stored path or filename

    if ($photoVal) {
        // Strip any leading "public/" or "storage/"
        $normalized = ltrim(str_replace(['public/', 'storage/'], '', $photoVal), '/');

        // Ensure it starts with "teacher/"
        if (!Str::startsWith($normalized, 'teacher/')) {
            $normalized = 'teacher/' . $normalized;
        }

        if (Storage::disk('public')->exists($normalized)) {
            $bin = Storage::disk('public')->get($normalized);

            // Detect MIME safely (no ->mimeType() calls)
            $mime = 'image/jpeg';
            if (class_exists(\finfo::class)) {
                $fi = new \finfo(FILEINFO_MIME_TYPE);
                $detected = $fi->buffer($bin);
                if ($detected) $mime = $detected;
            } else {
                $ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
                $map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp','bmp'=>'image/bmp'];
                if (isset($map[$ext])) $mime = $map[$ext];
            }

            $photoSrc = 'data:'.$mime.';base64,'.base64_encode($bin);
        }
    }
    // -------------------------------------------------------------------------------

    $data = [
        'user'     => $teacher,
        'photoSrc' => $photoSrc,
    ];

    $fileName = Str::slug(trim($teacher->name.' '.$teacher->last_name) ?: 'teacher') . '.pdf';

    $pdf = Pdf::loadView('pdf.teacher_profile', $data)->setPaper('A4','portrait');

    // inline (open in new tab via target="_blank")
    return $pdf->stream($fileName, ['Attachment' => false]);
}


    public function add()
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $data['header_title'] = 'Add Teacher';
        return view('admin.teacher.add', $data);
    }

    public function insert(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();
        if (! $schoolId) {
            return back()->with('error', 'No school context.')->withInput();
        }

        $request->validate([
            'name'          => ['required','string','max:255'],
            'last_name'     => ['required','string','max:255'],
            'email'         => [
                'required','email',
                Rule::unique('users','email')
                    ->where(fn($q)=>$q->where('school_id', $schoolId)
                                       ->whereNull('deleted_at')),
            ],
            'mobile_number' => ['required','string','min:11','max:20'],
            'password'      => ['required','string','min:6'],
            'role'          => ['required','in:teacher'],
            'status'        => ['nullable','in:0,1'],
            'address'       => ['nullable','string','max:255'],
            'gender'        => ['nullable','in:male,female,other'],
            'teacher_photo' => ['nullable','file','mimes:jpg,jpeg,png','max:5120'],
        ]);

        $teacher = new User();
        $teacher->school_id     = $schoolId;               // ensure pinned to current school
        $teacher->name          = trim($request->name);
        $teacher->last_name     = trim($request->last_name);
        $teacher->gender        = $request->gender ?: null;
        $teacher->email         = strtolower(trim($request->email));
        $teacher->mobile_number = trim($request->mobile_number);
        $teacher->address       = trim((string) $request->address);
        $teacher->password      = Hash::make($request->password);
        $teacher->role          = 'teacher';
        $teacher->status        = (int) ($request->status ?? 1);

        if ($request->hasFile('teacher_photo')) {
            $teacher->teacher_photo = $request->file('teacher_photo')->store('teacher', 'public');
        }

        $teacher->save();

        return redirect()->route('admin.teacher.list')->with('success', 'Teacher added successfully.');
    }

    public function edit($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $data['user'] = User::query()
            ->where('role','teacher')
            ->ofSchool($schoolId)
            ->findOrFail($id);

        $data['header_title'] = 'Edit Teacher';
        return view('admin.teacher.add', $data);
    }

    public function update(Request $request, $id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $teacher = User::query()
            ->where('role','teacher')
            ->ofSchool($schoolId)
            ->findOrFail($id);

        $request->validate([
            'name'          => ['required','string','max:255'],
            'last_name'     => ['required','string','max:255'],
            'email'         => [
                'required','email',
                Rule::unique('users','email')
                    ->ignore($teacher->id)
                    ->where(fn($q)=>$q->where('school_id', $schoolId)
                                       ->whereNull('deleted_at')),
            ],
            'mobile_number' => ['required','string','min:11','max:20'],
            'password'      => ['nullable','string','min:6'],
            'role'          => ['required','in:teacher'],
            'status'        => ['nullable','in:0,1'],
            'address'       => ['nullable','string','max:255'],
            'gender'        => ['nullable','in:male,female,other'],
            'teacher_photo' => ['nullable','file','mimes:jpg,jpeg,png','max:5120'],
        ]);

        $teacher->name          = trim($request->name);
        $teacher->last_name     = trim($request->last_name);
        $teacher->gender        = $request->gender ?: null;
        $teacher->email         = strtolower(trim($request->email));
        $teacher->mobile_number = trim($request->mobile_number);
        $teacher->address       = trim((string) $request->address);
        $teacher->status        = (int) ($request->status ?? $teacher->status);
        $teacher->role          = 'teacher';

        if (!empty($request->password)) {
            $teacher->password = Hash::make($request->password);
        }

        if ($request->hasFile('teacher_photo')) {
            if ($teacher->teacher_photo && Storage::disk('public')->exists($teacher->teacher_photo)) {
                Storage::disk('public')->delete($teacher->teacher_photo);
            }
            $teacher->teacher_photo = $request->file('teacher_photo')->store('teacher','public');
        }

        $teacher->save();

        return redirect()->route('admin.teacher.list')->with('success', 'Teacher updated successfully.');
    }

    public function delete(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $request->validate([
            'id' => ['required','integer','exists:users,id'],
        ]);

        $schoolId = $this->currentSchoolId();

        $teacher = User::query()
            ->where('role','teacher')
            ->ofSchool($schoolId)
            ->findOrFail((int) $request->id);

        if ($teacher->teacher_photo && Storage::disk('public')->exists($teacher->teacher_photo)) {
            Storage::disk('public')->delete($teacher->teacher_photo);
        }

        $teacher->delete();

        return redirect()->route('admin.teacher.list')->with('success', 'Teacher deleted successfully.');
    }
}
