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
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

    protected function upsertParent(array $data, int $schoolId, ?string $defaultRelationship = null): ?User
    {
        $email = strtolower(trim($data['email'] ?? ''));
        if ($email === '') return null;

        $parent = User::query()
            ->where('role', 'parent')
            ->where('school_id', $schoolId)
            ->where('email', $email)
            ->first();

        $name       = trim($data['name']       ?? '');
        $last_name  = trim($data['last_name']  ?? '');
        $gender     = $data['gender']          ?? null;
        $mobile     = trim($data['mobile']     ?? '');
        $occupation = trim($data['occupation'] ?? '');
        $address    = trim($data['address']    ?? '');
        $status     = isset($data['status']) ? (int)$data['status'] : 1;
        $password   = $data['password'] ?? null;
        $nid        = trim((string)($data['nid_or_birthcertificate_no'] ?? ''));
        $photoFile  = $data['parent_photo_file'] ?? null;

        if (! $parent) {
            $parent                 = new User();
            $parent->school_id      = $schoolId;
            $parent->role           = 'parent';
            $parent->status         = $status;
            $parent->email          = $email;
            $parent->name           = $name !== '' ? $name : 'Parent';
            $parent->last_name      = $last_name ?: null;
            $parent->gender         = in_array($gender, ['male','female','other']) ? $gender : null;
            $parent->mobile_number  = $mobile ?: null;
            $parent->occupation     = $occupation ?: null;
            $parent->address        = $address ?: null;
            $parent->nid_or_birthcertificate_no = $nid ?: null;
            $parent->password       = Hash::make(trim($password ?: Str::random(10)));
            if ($photoFile) $parent->parent_photo = $photoFile->store('parent', 'public');
            $parent->save();
        } else {
            $changed = false;
            if ($name      !== '' && $name      !== $parent->name)                          { $parent->name = $name; $changed = true; }
            if ($last_name !== '' && $last_name !== (string)$parent->last_name)             { $parent->last_name = $last_name; $changed = true; }
            if ($gender && in_array($gender, ['male','female','other']) && $gender !== $parent->gender) { $parent->gender = $gender; $changed = true; }
            if ($mobile    !== '' && $mobile    !== (string)$parent->mobile_number)         { $parent->mobile_number = $mobile; $changed = true; }
            if ($occupation!== '' && $occupation!== (string)$parent->occupation)            { $parent->occupation = $occupation; $changed = true; }
            if ($address   !== '' && $address   !== (string)$parent->address)               { $parent->address = $address; $changed = true; }
            if (isset($data['status']) && (int)$data['status'] !== (int)$parent->status)    { $parent->status = (int)$data['status']; $changed = true; }
            if ($password)                                                                  { $parent->password = Hash::make(trim($password)); $changed = true; }
            if ($nid !== '' && $nid !== (string)$parent->nid_or_birthcertificate_no)        { $parent->nid_or_birthcertificate_no = $nid; $changed = true; }
            if ($photoFile) {
                if ($parent->parent_photo && Storage::disk('public')->exists($parent->parent_photo)) {
                    Storage::disk('public')->delete($parent->parent_photo);
                }
                $parent->parent_photo = $photoFile->store('parent','public');
                $changed = true;
            }
            if ($changed) $parent->save();
        }

        return $parent;
    }

    protected function attachGuardian(int $schoolId, int $studentId, int $parentId, ?string $relationship = null, bool $isPrimary = false): void
    {
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
     * FAST, SAFE NUMBER GENERATION
     * ----------------------------- */

    private const STUD_SEQ_WIDTH = 4; // YY + 0001

    /** Preview next code quickly (no locks) for UI */
    protected function previewNextCode(int $schoolId, int $classId, string $yy, string $which): string
    {
        $row = DB::table('student_number_sequences')
            ->where('school_id', $schoolId)
            ->where('class_id', $classId)
            ->where('yy', $yy)
            ->first();

        $last = 0;
        if ($row) {
            $last = $which === 'admission' ? (int)$row->last_admission_tail : (int)$row->last_roll_tail;
        }
        $next = $last + 1;
        return $yy . str_pad((string)$next, self::STUD_SEQ_WIDTH, '0', STR_PAD_LEFT);
    }

    /**
     * Reserve and return next code atomically for a single counter
     * $which: 'admission' or 'roll'
     */
    protected function reserveNextCode(int $schoolId, int $classId, string $yy, string $which): string
    {
        return DB::transaction(function() use ($schoolId, $classId, $yy, $which) {
            $row = DB::table('student_number_sequences')
                ->where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->where('yy', $yy)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                DB::table('student_number_sequences')->insert([
                    'school_id' => $schoolId,
                    'class_id'  => $classId,
                    'yy'        => $yy,
                    'last_admission_tail' => 0,
                    'last_roll_tail'      => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $row = DB::table('student_number_sequences')
                    ->where('school_id', $schoolId)
                    ->where('class_id', $classId)
                    ->where('yy', $yy)
                    ->lockForUpdate()
                    ->first();
            }

            $lastAdmission = (int)$row->last_admission_tail;
            $lastRoll      = (int)$row->last_roll_tail;

            if ($which === 'admission') {
                $next = $lastAdmission + 1;
                DB::table('student_number_sequences')
                    ->where('id', $row->id)
                    ->update([
                        'last_admission_tail' => $next,
                        'updated_at' => now(),
                    ]);
                return $yy . str_pad((string)$next, self::STUD_SEQ_WIDTH, '0', STR_PAD_LEFT);
            } else {
                $next = $lastRoll + 1;
                DB::table('student_number_sequences')
                    ->where('id', $row->id)
                    ->update([
                        'last_roll_tail' => $next,
                        'updated_at' => now(),
                    ]);
                return $yy . str_pad((string)$next, self::STUD_SEQ_WIDTH, '0', STR_PAD_LEFT);
            }
        }, 5);
    }

    /** Normalize manual input to ensure YY prefix */
    protected function normalizeWithYY(?string $input, string $yy): ?string
    {
        if ($input === null) return null;
        $code = trim($input);
        if ($code === '') return '';
        if (!Str::startsWith($code, $yy)) $code = $yy . preg_replace('/^\D*/', '', $code);
        return $code;
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
            // 👇 show newest first
            ->orderByDesc('id');

        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->name.'%')
                ->orWhere('last_name', 'like', '%'.$request->name.'%');
            });
        }
        if ($request->filled('email'))   $query->where('email', 'like', '%'.$request->email.'%');
        if ($request->filled('mobile'))  $query->where('mobile_number', 'like', '%'.$request->mobile.'%');
        if ($request->filled('gender'))  $query->where('gender', $request->gender);
        if ($request->filled('status') || $request->status === '0') $query->where('status', (int)$request->status);
        if ($request->filled('class_id'))    $query->where('class_id', (int)$request->class_id);
        if ($request->filled('roll_number')) $query->where('roll_number', 'like', '%'.$request->roll_number.'%');

        $data['getRecord'] = $query->paginate(20)->appends($request->all());

        $data['getClass'] = ClassModel::query()
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')->get(['id','name']);

        $data['header_title'] = 'Student List';
        return view('admin.student.list', $data);
    }

    protected function resolvePrimaryParentViaPivot(int $schoolId, int $studentId): ?\App\Models\User
    {
        $row = DB::table('student_guardians as sg')
            ->join('users as u', function ($j) {
                $j->on('u.id', '=', 'sg.parent_id')
                  ->where('u.role', 'parent');
            })
            ->where('sg.school_id', $schoolId)
            ->where('sg.student_id', $studentId)
            ->select('u.*', 'sg.relationship', 'sg.is_primary')
            ->orderByDesc('sg.is_primary')
            ->orderByRaw("FIELD(LOWER(COALESCE(sg.relationship,'')), 'mother','father')")
            ->orderByDesc('u.id')
            ->first();

        if (! $row) return null;

        $parent = new \App\Models\User();
        foreach ((array)$row as $k => $v) { $parent->{$k} = $v; }
        return $parent;
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

        // ---- Logo to data URI (same logic as before) ----
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

        // ---- EIIN: prefer your real column name (eiin_num) ----
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
                'eiin'    => $eiin, // ← will use eiin_num
                'address' => $school->address ?? $school->full_address ?? null,
                'website' => $website,
            ],
        ];
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

        // ---- Fetch BOTH parents from the pivot ----
        $rows = DB::table('student_guardians as sg')
            ->join('users as u', function($j){
                $j->on('u.id','=','sg.parent_id')->where('u.role','parent');
            })
            ->where('sg.school_id', $schoolId)
            ->where('sg.student_id', $student->id)
            ->select('u.*', 'sg.relationship', 'sg.is_primary')
            ->get();

        $mother = null; $father = null;
        foreach ($rows as $r) {
            $u = new \App\Models\User();
            foreach ((array)$r as $k => $v) { $u->{$k} = $v; }
            $rel = strtolower(trim((string)($r->relationship ?? '')));
            if (!$mother && ($rel === 'mother' || strtolower((string)$r->gender) === 'female')) $mother = $u;
            if (!$father && ($rel === 'father' || strtolower((string)$r->gender) === 'male'))   $father = $u;
        }

        // Legacy single parent fallback, if needed
        if (!$mother && !$father && $student->parent_id) {
            $p = User::query()
                ->where('id',$student->parent_id)
                ->where('role','parent')
                ->ofSchool($schoolId)
                ->first();
            if ($p) {
                $rel = strtolower((string)($p->relationship ?? ''));
                if ($rel === 'mother' || strtolower((string)$p->gender) === 'female') $mother = $p;
                else $father = $p;
            }
        }

        // ---- Helper to embed public images as data URIs ----
        $embedFromPublic = function (?string $val, array $dirs) {
            if (!$val) return null;
            $normalized = ltrim(str_replace(['public/','storage/'], '', $val), '/');
            $candidates = [$normalized, 'student/'.basename($normalized), 'parent/'.basename($normalized)];
            foreach ($dirs as $d) { $candidates[] = trim($d,'/').'/'.basename($normalized); }

            $path = null;
            foreach ($candidates as $cand) {
                if (Storage::disk('public')->exists($cand)) { $path = $cand; break; }
            }
            if (!$path) return null;

            $bin  = Storage::disk('public')->get($path);
            $mime = 'image/jpeg';
            if (class_exists(\finfo::class)) {
                $fi = new \finfo(FILEINFO_MIME_TYPE);
                $detected = $fi->buffer($bin);
                if ($detected) $mime = $detected;
            } else {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp','bmp'=>'image/bmp','svg'=>'image/svg+xml'];
                if (isset($map[$ext])) $mime = $map[$ext];
            }
            return 'data:'.$mime.';base64,'.base64_encode($bin);
        };

        $studentPhoto = $embedFromPublic($student->student_photo, ['student','student_photos']);
        $motherPhoto  = $embedFromPublic($mother->parent_photo ?? null, ['parent','parent_photos']);
        $fatherPhoto  = $embedFromPublic($father->parent_photo ?? null, ['parent','parent_photos']);

        $header = $this->schoolHeaderData();

        $data = [
            'user'         => $student,
            'studentPhoto' => $studentPhoto,

            // pass BOTH parents and their photos
            'mother'       => $mother,
            'father'       => $father,
            'motherPhoto'  => $motherPhoto,
            'fatherPhoto'  => $fatherPhoto,
        ] + $header;

        $fileName = Str::slug(trim(($student->name ?? '') . ' ' . ($student->last_name ?? '')) ?: 'student') . '.pdf';
        $pdf = Pdf::loadView('pdf.student_profile', $data)->setPaper('A4', 'portrait');
        return $pdf->stream($fileName, ['Attachment' => false]);
    }



    /** FAST preview for the form (no locks) */
    public function nextCodes(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;
        $schoolId = $this->currentSchoolId();

        $request->validate([
            'class_id'       => ['required','integer', Rule::exists('classes','id')
                                    ->where(fn($q)=>$q->where('school_id', $schoolId))],
            'admission_date' => ['nullable','date'],
        ]);

        $classId = (int) $request->class_id;
        $year    = $request->admission_date ? \Carbon\Carbon::parse($request->admission_date)->year : now()->year;
        $yy      = substr((string)$year, -2);

        $nextAdmission = $this->previewNextCode($schoolId, $classId, $yy, 'admission');
        $nextRoll      = $this->previewNextCode($schoolId, $classId, $yy, 'roll');

        return response()->json([
            'admission_number' => $nextAdmission,
            'roll_number'      => $nextRoll,
            'yy'               => $yy,
        ]);
    }

    public function add()
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        $data['getClass'] = ClassModel::query()
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id','name']);

        // Default date so we can compute a YY preview quickly
        $data['defaultAdmissionDate'] = now()->toDateString();

        $data['header_title'] = 'Add Student';
        return view('admin.student.add', $data);
    }

    public function insert(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();
        if (! $schoolId) return back()->with('error', 'No school context.')->withInput();

        $classId = (int) $request->input('class_id');
        $year    = $request->admission_date ? Carbon::parse($request->admission_date)->year : now()->year;
        $yy      = substr((string)$year, -2);

        // Normalize any manual input to ensure YY prefix (but don't reserve yet)
        $manualAdm  = $this->normalizeWithYY($request->input('admission_number'), $yy);
        $manualRoll = $this->normalizeWithYY($request->input('roll_number'), $yy);

        // First validate all non-number fields; allow numbers to be nullable here
        $request->validate([
            'name'            => ['required','string','max:255'],
            'last_name'       => ['nullable','string','max:255'],
            'email'           => [
                'required','email',
                Rule::unique('users','email')->where(fn($q)=>$q->where('school_id', $schoolId)->whereNull('deleted_at')),
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
            'nid_or_birthcertificate_no' => ['nullable','string','max:32'],
            // numbers validated after we assign or reserve them
        ]);

        // Reserve numbers (only for the ones left blank) and then run number rules
        $finalAdmission = ($manualAdm === '' || $manualAdm === null)
            ? $this->reserveNextCode($schoolId, $classId, $yy, 'admission')
            : $manualAdm;

        $finalRoll = ($manualRoll === '' || $manualRoll === null)
            ? $this->reserveNextCode($schoolId, $classId, $yy, 'roll')
            : $manualRoll;

        // Merge and run the number-specific validation
        $request->merge(['admission_number' => $finalAdmission, 'roll_number' => $finalRoll]);

        $request->validate([
            'admission_number'=> [
                'required','string','max:100',"regex:/^{$yy}[0-9]+$/",
                Rule::unique('users','admission_number')->where(function($q) use ($schoolId, $request){
                    return $q->where('school_id',$schoolId)->where('class_id',(int)$request->class_id);
                }),
            ],
            'roll_number'     => [
                'required','string','max:100',"regex:/^{$yy}[0-9]+$/",
                Rule::unique('users','roll_number')->where(function($q) use ($schoolId, $request){
                    return $q->where('school_id',$schoolId)->where('class_id',(int)$request->class_id);
                }),
            ],
        ]);

        // Create student
        $student = new User();
        $student->school_id        = $schoolId;
        $student->name             = trim($request->name);
        $student->last_name        = trim((string) $request->last_name);
        $student->admission_number = $finalAdmission;
        $student->roll_number      = $finalRoll;
        $student->class_id         = (int) $request->class_id;
        $student->gender           = $request->gender ?: null;
        $student->date_of_birth    = $request->date_of_birth ?: null;
        $student->religion         = $request->religion ?: null;
        $student->mobile_number    = trim($request->mobile_number);
        $student->admission_date   = $request->admission_date ?: null;
        $student->blood_group      = $request->blood_group ?: null;
        $student->height           = $request->height ?: null;
        $student->weight           = $request->weight ?: null;
        $student->nid_or_birthcertificate_no = $request->nid_or_birthcertificate_no ?: null;
        $student->status           = (int) ($request->status ?? 1);
        $student->email            = strtolower(trim($request->email));
        $student->password         = Hash::make(trim($request->password));
        $student->role             = 'student';

        if ($request->hasFile('student_photo')) {
            $student->student_photo = $request->file('student_photo')->store('student', 'public');
        }
        $student->save();

        // Optional Parents
        if ($request->has('mother')) {
            $payload = $request->input('mother', []);
            $payload['parent_photo_file'] = $request->file('mother.parent_photo');
            $mother = $this->upsertParent($payload, $schoolId, 'mother');
            if ($mother) {
                $this->attachGuardian($schoolId, $student->id, $mother->id,
                    $request->input('mother.relationship', 'mother'),
                    (bool)$request->boolean('mother.is_primary')
                );
            }
        }

        if ($request->has('father')) {
            $payload = $request->input('father', []);
            $payload['parent_photo_file'] = $request->file('father.parent_photo');
            $father = $this->upsertParent($payload, $schoolId, 'father');
            if ($father) {
                $this->attachGuardian($schoolId, $student->id, $father->id,
                    $request->input('father.relationship', 'father'),
                    (bool)$request->boolean('father.is_primary')
                );
            }
        }

        return redirect()->route('admin.student.list')->with('success', 'Student added successfully with parent linkage.');
    }

    protected function getStudentParents(int $schoolId, int $studentId): array
    {
        $rows = DB::table('student_guardians as sg')
            ->join('users as u', function($j){ $j->on('u.id','=','sg.parent_id')->where('u.role','parent'); })
            ->where('sg.school_id', $schoolId)
            ->where('sg.student_id', $studentId)
            ->select('u.*', 'sg.relationship', 'sg.is_primary')
            ->get();

        $mother = null; $father = null;
        foreach ($rows as $r) {
            $u = new \App\Models\User();
            foreach ((array) $r as $k => $v) { $u->{$k} = $v; }
            $rel = strtolower(trim((string)($r->relationship ?? '')));
            if (!$mother && ($rel === 'mother' || strtolower((string)$r->gender) === 'female')) $mother = $u;
            if (!$father && ($rel === 'father' || strtolower((string)$r->gender) === 'male'))   $father = $u;
        }

        // Legacy fallback
        $student = \App\Models\User::find($studentId);
        if ((!$mother || !$father) && $student && $student->parent_id) {
            $p = \App\Models\User::query()
                ->where('id', $student->parent_id)
                ->where('role', 'parent')
                ->where('school_id', $schoolId)
                ->first();
            if ($p) {
                $rel = strtolower((string)($p->relationship ?? ''));
                if (!$mother && ($rel === 'mother' || strtolower((string)$p->gender) === 'female')) $mother = $p;
                elseif (!$father && ($rel === 'father' || strtolower((string)$p->gender) === 'male')) $father = $p;
            }
        }

        return ['mother' => $mother, 'father' => $father];
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

        $parents = $this->getStudentParents($schoolId, $data['user']->id);
        $data['mother'] = $parents['mother'];
        $data['father'] = $parents['father'];

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

        $baseYear = $request->admission_date
            ? Carbon::parse($request->admission_date)->year
            : ($student->admission_date ? Carbon::parse($student->admission_date)->year : now()->year);
        $yy      = substr((string)$baseYear, -2);
        $classId = (int) ($request->input('class_id') ?? $student->class_id);

        // Normalize manual values to YY prefix; we DO NOT auto-increment on update
        $genAdmission = $this->normalizeWithYY($request->input('admission_number') ?: $student->admission_number, $yy);
        $genRoll      = $this->normalizeWithYY($request->input('roll_number')      ?: $student->roll_number,      $yy);
        $request->merge(['admission_number' => $genAdmission, 'roll_number' => $genRoll]);

        $request->validate([
            'name'            => ['required','string','max:255'],
            'last_name'       => ['nullable','string','max:255'],
            'email'           => [
                'required','email',
                Rule::unique('users','email')->ignore($student->id)->where(fn($q)=>$q->where('school_id', $schoolId)->whereNull('deleted_at')),
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
            'nid_or_birthcertificate_no' => ['nullable','string','max:32'],
            'admission_number'=> [
                'required','string','max:100',"regex:/^{$yy}[0-9]+$/",
                Rule::unique('users','admission_number')->ignore($student->id)->where(function($q) use ($schoolId, $request){
                    return $q->where('school_id',$schoolId)->where('class_id',(int)$request->class_id);
                }),
            ],
            'roll_number'     => [
                'required','string','max:100',"regex:/^{$yy}[0-9]+$/",
                Rule::unique('users','roll_number')->ignore($student->id)->where(function($q) use ($schoolId, $request){
                    return $q->where('school_id',$schoolId)->where('class_id',(int)$request->class_id);
                }),
            ],
        ]);

        $student->name             = trim($request->name);
        $student->last_name        = trim((string) $request->last_name);
        $student->admission_number = $request->admission_number;
        $student->roll_number      = $request->roll_number;
        $student->class_id         = (int) $request->class_id;
        $student->gender           = $request->gender ?: null;
        $student->date_of_birth    = $request->date_of_birth ?: $student->date_of_birth;
        $student->religion         = $request->religion ?: null;
        $student->mobile_number    = trim($request->mobile_number);
        $student->admission_date   = $request->admission_date ?: $student->admission_date;
        $student->blood_group      = $request->blood_group ?: null;
        $student->height           = $request->height ?: null;
        $student->weight           = $request->weight ?: null;
        $student->nid_or_birthcertificate_no = $request->nid_or_birthcertificate_no ?: $student->nid_or_birthcertificate_no;
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

        if ($request->has('mother')) {
            $payload = $request->input('mother', []);
            $payload['parent_photo_file'] = $request->file('mother.parent_photo');
            $mother = $this->upsertParent($payload, $schoolId, 'mother');
            if ($mother) {
                $this->attachGuardian($schoolId, $student->id, $mother->id,
                    $request->input('mother.relationship', 'mother'),
                    (bool)$request->boolean('mother.is_primary')
                );
            }
        }
        if ($request->has('father')) {
            $payload = $request->input('father', []);
            $payload['parent_photo_file'] = $request->file('father.parent_photo');
            $father = $this->upsertParent($payload, $schoolId, 'father');
            if ($father) {
                $this->attachGuardian($schoolId, $student->id, $father->id,
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

        $request->validate(['id' => ['required','integer','exists:users,id']]);

        $schoolId = $this->currentSchoolId();

        $student = User::query()
            ->where('role','student')
            ->ofSchool($schoolId)
            ->findOrFail((int) $request->id);

        if ($student->student_photo && Storage::disk('public')->exists($student->student_photo)) {
            Storage::disk('public')->delete($student->student_photo);
        }

        DB::table('student_guardians')
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->delete();

        $student->delete();

        return redirect()->route('admin.student.list')->with('success', 'Student deleted successfully.');
    }
}
