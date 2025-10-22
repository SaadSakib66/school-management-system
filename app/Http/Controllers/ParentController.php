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
use Illuminate\Support\Facades\DB;

class ParentController extends Controller
{
    /* --------------------------------
     * School-context helpers (multi-school)
     * -------------------------------- */
    protected function currentSchoolId(): ?int
    {
        $u = Auth::user();
        if (!$u) return null;

        if ($u->role === 'super_admin') {
            return (int) session('current_school_id');
        }

        return (int) $u->school_id;
    }

    protected function guardSchoolContext()
    {
        if (Auth::user()?->role === 'super_admin' && !$this->currentSchoolId()) {
            return redirect()
                ->route('superadmin.schools.switch')
                ->with('error', 'Please select a school first.');
        }
        return null;
    }

    /* --------------------------------
     * ADMIN: Parents CRUD
     * -------------------------------- */
    public function list(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $query = User::query()
            ->where('role', 'parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            // 📌 newest on top
            ->orderByDesc('id');

        // 🔎 Filters
        if ($request->filled('name')) {
            $name = trim($request->name);
            $query->where(function ($q) use ($name) {
                $q->where('name', 'like', "%{$name}%")
                  ->orWhere('last_name', 'like', "%{$name}%");
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
        if ($request->filled('occupation')) {
            $query->where('occupation', 'like', '%'.$request->occupation.'%');
        }
        // 🆕 filter by NID / Birth Certificate
        if ($request->filled('nid_or_birthcertificate_no')) {
            $query->where('nid_or_birthcertificate_no', 'like', '%'.$request->nid_or_birthcertificate_no.'%');
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', (int) $request->status);
        }

        $parents = $query->paginate(20)->appends($request->all());

        // ⚡ Pull assigned student IDs for these parents (via pivot)
        $assignedByParent = collect();
        if ($parents->count()) {
            $rows = DB::table('student_guardians')
                ->select('parent_id', 'student_id')
                ->when($schoolId !== null, fn($q) => $q->where('school_id', $schoolId))
                ->whereIn('parent_id', $parents->pluck('id'))
                ->get();

            $assignedByParent = $rows->groupBy('parent_id')
                                     ->map(fn($g) => $g->pluck('student_id')->all());
        }

        return view('admin.parent.list', [
            'getRecord'        => $parents,
            'assignedByParent' => $assignedByParent,
            'header_title'     => 'Parent List',
        ]);
    }

    protected function schoolHeaderData(?int $forceSchoolId = null): array
    {
        // Resolve school id from several sources
        $schoolId =
            $forceSchoolId
            ?? (method_exists($this, 'currentSchoolId') ? $this->currentSchoolId() : null)
            ?? (Auth::check() ? Auth::user()->school_id : null)
            ?? session('school_id'); // optional extra session key you may use

        // As a last resort, pick the first school so the PDF doesn't explode
        if (!$schoolId) {
            $fallback = \App\Models\School::query()->orderBy('id')->value('id');
            if ($fallback) {
                $schoolId = (int)$fallback;
            } else {
                abort(403, 'No school context.');
            }
        }

        /** @var \App\Models\School $school */
        $school = \App\Models\School::findOrFail($schoolId);

        // ---- Logo to data URI ----
        $logoFile = $school->logo ?? $school->school_logo ?? $school->photo ?? null;
        $logoSrc  = null;

        if ($logoFile) {
            $normalized = ltrim(str_replace(['public/', 'storage/'], '', $logoFile), '/');
            $candidates = [$normalized, 'schools/'.basename($normalized), 'school_logos/'.basename($normalized)];
            foreach ($candidates as $path) {
                if (Storage::disk('public')->exists($path)) {
                    $bin  = Storage::disk('public')->get($path);
                    $mime = 'image/png';
                    if (class_exists(\finfo::class)) {
                        $fi  = new \finfo(FILEINFO_MIME_TYPE);
                        $det = $fi->buffer($bin);
                        if ($det) $mime = $det;
                    } else {
                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                        $map = [
                            'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif',
                            'webp'=>'image/webp','bmp'=>'image/bmp','svg'=>'image/svg+xml'
                        ];
                        if (isset($map[$ext])) $mime = $map[$ext];
                    }
                    $logoSrc = 'data:'.$mime.';base64,'.base64_encode($bin);
                    break;
                }
            }
        }

        // ---- EIIN ----
        $eiin = null;
        foreach (['eiin_num', 'eiin', 'eiin_code', 'eiin_no'] as $field) {
            if (isset($school->{$field})) {
                $val = trim((string)$school->{$field});
                if ($val !== '') { $eiin = $val; break; }
            }
        }

        $website = $school->website ?? $school->website_url ?? $school->domain ?? null;
        if (is_string($website)) $website = trim($website);

        return [
            'school'        => $school,
            'schoolLogoSrc' => $logoSrc,
            'schoolPrint'   => [
                'name'    => $school->name ?? $school->short_name ?? 'Unknown School',
                'eiin'    => $eiin,
                'address' => $school->address ?? $school->full_address ?? null,
                'website' => $website,
            ],
        ];
    }

    public function download($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $parent = User::query()
            ->where('role','parent')
            ->when($schoolId, fn($q)=>$q->where('school_id',$schoolId))
            ->findOrFail($id);

        // Children (students) of this parent via pivot (student_guardians)
        $children = User::query()
            ->where('role', 'student')
            ->where('school_id', $schoolId)
            ->whereIn('id', function ($q) use ($schoolId, $parent) {
                $q->from('student_guardians')
                  ->select('student_id')
                  ->when($schoolId !== null, fn($qq) => $qq->where('school_id', $schoolId))
                  ->where('parent_id', $parent->id);
            })
            ->with('class')
            ->orderBy('name')->orderBy('last_name')
            ->get();

        // Helper: try multiple folders on public disk and embed as base64
        $embedFromPublic = function (?string $val, array $dirs) {
            if (!$val) return null;
            $normalized = ltrim(str_replace(['public/','storage/'], '', $val), '/');
            $candidates = [$normalized];
            $basename   = basename($normalized);
            foreach ($dirs as $d) {
                $candidates[] = trim($d, '/') . '/' . $basename;
            }
            $path = null;
            foreach ($candidates as $cand) {
                if (Storage::disk('public')->exists($cand)) { $path = $cand; break; }
            }
            if (!$path) return null;

            $bin = Storage::disk('public')->get($path);
            $mime = 'image/jpeg';
            if (class_exists(\finfo::class)) {
                $fi = new \finfo(FILEINFO_MIME_TYPE);
                $det = $fi->buffer($bin);
                if ($det) $mime = $det;
            } else {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp','bmp'=>'image/bmp'];
                if (isset($map[$ext])) $mime = $map[$ext];
            }
            return 'data:'.$mime.';base64,'.base64_encode($bin);
        };

        // Parent photo: try public/storage/parent then public/storage/parent_photos
        $parentPhoto = $embedFromPublic($parent->parent_photo, ['parent', 'parent_photos']);
        $header = $this->schoolHeaderData();

        $data = [
            'parent'      => $parent,
            'children'    => $children,
            'parentPhoto' => $parentPhoto,
        ] + $header;

        $fileName = Str::slug(trim(($parent->name ?? '').' '.($parent->last_name ?? '')) ?: 'parent') . '.pdf';

        $pdf = Pdf::loadView('pdf.parent_profile', $data)->setPaper('A4','portrait');

        return $pdf->stream($fileName, ['Attachment' => false]);
    }

    public function add()
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        return view('admin.parent.add', [
            'header_title' => 'Add Parent',
        ]);
    }

    public function insert(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $request->validate([
            'name'           => ['required','string','max:255'],
            'last_name'      => ['nullable','string','max:255'],
            'gender'         => ['nullable', Rule::in(['male','female','other'])],
            'email'          => [
                'required','email',
                Rule::unique('users','email')->whereNull('deleted_at'),
            ],
            'mobile_number'  => ['required','string','min:10','max:20'],
            'password'       => ['required','string','min:6'],
            'role'           => ['required','in:parent'],
            'status'         => ['nullable','in:0,1'],
            'address'        => ['nullable','string','max:255'],
            'occupation'     => ['nullable','string','max:255'],
            'parent_photo'   => ['nullable','file','mimes:jpg,jpeg,png','max:5120'],
            // 🆕
            'nid_or_birthcertificate_no' => ['nullable','string','max:100'],
        ]);

        $parent = new User();
        $parent->school_id     = $schoolId;
        $parent->name          = trim($request->name);
        $parent->last_name     = trim((string) $request->last_name);
        $parent->gender        = $request->gender ?: null;
        $parent->email         = strtolower(trim($request->email));
        $parent->mobile_number = trim($request->mobile_number);
        $parent->occupation    = trim((string) $request->occupation) ?: null;
        $parent->address       = trim((string) $request->address) ?: null;
        $parent->password      = Hash::make($request->password);
        $parent->role          = 'parent';
        $parent->status        = (int) ($request->status ?? 1);
        // 🆕 save NID/BC no.
        $parent->nid_or_birthcertificate_no = trim((string) $request->nid_or_birthcertificate_no) ?: null;

        if ($request->hasFile('parent_photo')) {
            $path = $request->file('parent_photo')->store('parent', 'public');
            $parent->parent_photo = $path;
        }

        $parent->save();

        return redirect()->route('admin.parent.list')->with('success', 'Parent added successfully.');
    }

    public function edit($id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $data['user'] = User::where('role','parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $data['header_title'] = 'Edit Parent';
        return view('admin.parent.add', $data);
    }

    public function update(Request $request, $id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $parent = User::where('role','parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        $request->validate([
            'name'           => ['required','string','max:255'],
            'last_name'      => ['nullable','string','max:255'],
            'gender'         => ['nullable', Rule::in(['male','female','other'])],
            'email'          => [
                'required','email',
                Rule::unique('users','email')
                    ->ignore($parent->id)
                    ->whereNull('deleted_at'),
            ],
            'mobile_number'  => ['required','string','min:10','max:20'],
            'password'       => ['nullable','string','min:6'],
            'role'           => ['required','in:parent'],
            'status'         => ['nullable','in:0,1'],
            'address'        => ['nullable','string','max:255'],
            'occupation'     => ['nullable','string','max:255'],
            'parent_photo'   => ['nullable','file','mimes:jpg,jpeg,png','max:5120'],
            // 🆕
            'nid_or_birthcertificate_no' => ['nullable','string','max:100'],
        ]);

        $parent->name          = trim($request->name);
        $parent->last_name     = trim((string) $request->last_name);
        $parent->gender        = $request->gender ?: null;
        $parent->email         = strtolower(trim($request->email));
        $parent->mobile_number = trim($request->mobile_number);
        $parent->occupation    = trim((string) $request->occupation) ?: null;
        $parent->address       = trim((string) $request->address) ?: null;
        $parent->status        = (int) ($request->status ?? 1);
        $parent->role          = 'parent';
        // 🆕 update NID/BC no.
        $parent->nid_or_birthcertificate_no = trim((string) $request->nid_or_birthcertificate_no) ?: null;

        if (!empty($request->password)) {
            $parent->password = Hash::make($request->password);
        }

        if ($request->hasFile('parent_photo')) {
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
        if ($resp = $this->guardSchoolContext()) return $resp;

        $request->validate([
            'id' => ['required','integer','exists:users,id'],
        ]);

        $schoolId = $this->currentSchoolId();

        $parent = User::where('role','parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail((int) $request->id);

        // Optional: delete file
        if ($parent->parent_photo && Storage::disk('public')->exists($parent->parent_photo)) {
            Storage::disk('public')->delete($parent->parent_photo);
        }

        // Also clean pivot rows for this parent in this school
        DB::table('student_guardians')
            ->when($schoolId !== null, fn($q) => $q->where('school_id', $schoolId))
            ->where('parent_id', $parent->id)
            ->delete();

        $parent->delete();

        return redirect()->route('admin.parent.list')->with('success', 'Parent deleted successfully.');
    }

    /* --------------------------------
     * ADMIN: Link/unlink children via pivot
     * -------------------------------- */
    public function addMyStudent(Request $request, $id)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $parent = User::where('role','parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);

        // Search pool: only students in the same school, with filters
        $students = User::query()
            ->where('role', 'student')
            ->where('school_id', $schoolId)
            ->with('class')
            ->when($request->filled('name'), function ($q) use ($request) {
                $name = trim($request->name);
                $q->where(function ($qq) use ($name) {
                    $qq->where('name', 'like', "%{$name}%")
                       ->orWhere('last_name', 'like', "%{$name}%");
                });
            })
            ->when($request->filled('email'), fn($q) => $q->where('email', 'like', '%'.$request->email.'%'))
            ->when($request->filled('mobile'), fn($q) => $q->where('mobile_number', 'like', '%'.$request->mobile.'%'))
            ->orderBy('name')->orderBy('last_name')
            ->paginate(20)
            ->appends($request->all());

        // Already assigned students (to this parent) via pivot
        $assignedStudents = User::query()
            ->where('role', 'student')
            ->where('school_id', $schoolId)
            ->whereIn('id', function ($q) use ($schoolId, $parent) {
                $q->from('student_guardians')
                  ->select('student_id')
                  ->when($schoolId !== null, fn($qq) => $qq->where('school_id', $schoolId))
                  ->where('parent_id', $parent->id);
            })
            ->with('class')
            ->orderBy('name')->orderBy('last_name')
            ->get();

        $assignedIds = $assignedStudents->pluck('id')->all();

        return view('admin.parent.my_student', [
            'parent_id'        => $parent->id,
            'getRecord'        => $students,
            'assignedStudents' => $assignedStudents,
            'assignedIds'      => $assignedIds,
            'header_title'     => 'Parent Student List',
        ]);
    }

    public function assignStudent(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $request->validate([
            'parent_id'    => ['required','integer','exists:users,id'],
            'student_id'   => ['required','integer','exists:users,id'],
            'relationship' => ['nullable','string','max:20'], // e.g., mother, father, guardian
            'is_primary'   => ['nullable','in:0,1'],
        ]);

        $schoolId = $this->currentSchoolId();

        $parent = User::where('role','parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail((int) $request->parent_id);

        $student = User::where('role','student')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail((int) $request->student_id);

        // Prevent duplicates
        $exists = DB::table('student_guardians')
            ->when($schoolId !== null, fn($q) => $q->where('school_id', $schoolId))
            ->where('student_id', $student->id)
            ->where('parent_id', $parent->id)
            ->exists();

        if (!$exists) {
            DB::table('student_guardians')->insert([
                'school_id'    => $schoolId,
                'student_id'   => $student->id,
                'parent_id'    => $parent->id,
                'relationship' => $request->relationship ?: null,
                'is_primary'   => (int) ($request->is_primary ?? 0),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } else {
            // Optional: update relationship/is_primary if re-assigned
            DB::table('student_guardians')
                ->when($schoolId !== null, fn($q) => $q->where('school_id', $schoolId))
                ->where('student_id', $student->id)
                ->where('parent_id', $parent->id)
                ->update([
                    'relationship' => $request->relationship ?: null,
                    'is_primary'   => (int) ($request->is_primary ?? 0),
                    'updated_at'   => now(),
                ]);
        }

        return back()->with('success', 'Student assigned successfully!');
    }

    public function removeStudent(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $request->validate([
            'parent_id'  => ['required','integer','exists:users,id'],
            'student_id' => ['required','integer','exists:users,id'],
        ]);

        $schoolId = $this->currentSchoolId();

        $parent = User::where('role','parent')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail((int) $request->parent_id);

        $student = User::where('role','student')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->findOrFail((int) $request->student_id);

        // Remove only this specific link
        DB::table('student_guardians')
            ->when($schoolId !== null, fn($q) => $q->where('school_id', $schoolId))
            ->where('student_id', $student->id)
            ->where('parent_id', $parent->id)
            ->delete();

        return back()->with('success', 'Student removed successfully!');
    }
}
