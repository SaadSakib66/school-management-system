<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\ClassSubject;
use App\Models\ClassModel;
use App\Models\Subject;

use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ClassSubjectController extends Controller
{
    /* =========================================================
     | Universal School Header data (logo/name/EIIN/address/web)
     | - Does NOT depend only on session; uses several fallbacks.
     | - Returns: ['school','schoolLogoSrc','schoolPrint'=>[...] ]
     * ========================================================= */

protected function currentSchoolId(): ?int
{
    $u = \Illuminate\Support\Facades\Auth::user();
    if (!$u) return null;

    // Super admin picks school via session
    if (($u->role ?? null) === 'super_admin') {
        // prefer current_school_id, fall back to school_id if you ever store it
        $sid = session('current_school_id') ?? session('school_id');
        return $sid ? (int) $sid : null;
    }

    // Regular roles are tied to one school
    return isset($u->school_id) ? (int) $u->school_id : null;
}

    protected function schoolHeaderData(?int $forceSchoolId = null): array
    {
        // Resolve school id from: explicit param → currentSchoolId() → auth user → common session key → first school
        $schoolId =
            $forceSchoolId
            ?? (method_exists($this, 'currentSchoolId') ? $this->currentSchoolId() : null)
            ?? (Auth::check() ? (int) Auth::user()->school_id : null)
            ?? session('current_school_id')
            ?? session('school_id');

        if (!$schoolId) {
            $fallback = \App\Models\School::query()->orderBy('id')->value('id');
            if ($fallback) {
                $schoolId = (int) $fallback;
            } else {
                abort(403, 'No school context.');
            }
        }

        /** @var \App\Models\School $school */
        $school = \App\Models\School::findOrFail($schoolId);

        // --- Logo -> data URI (tries a few common paths on the public disk) ---
        $logoFile = $school->logo ?? $school->school_logo ?? $school->photo ?? null;
        $schoolLogoSrc = null;

        if ($logoFile) {
            $normalized = ltrim(str_replace(['public/', 'storage/'], '', $logoFile), '/');
            $candidates = [$normalized, 'schools/' . basename($normalized), 'school_logos/' . basename($normalized)];

            foreach ($candidates as $path) {
                if (Storage::disk('public')->exists($path)) {
                    $bin = Storage::disk('public')->get($path);

                    // Mime
                    $mime = 'image/png';
                    if (class_exists(\finfo::class)) {
                        $fi  = new \finfo(FILEINFO_MIME_TYPE);
                        $det = $fi->buffer($bin);
                        if ($det) $mime = $det;
                    } else {
                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                        $map = [
                            'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
                            'gif'=>'image/gif','webp'=>'image/webp','bmp'=>'image/bmp','svg'=>'image/svg+xml'
                        ];
                        if (isset($map[$ext])) $mime = $map[$ext];
                    }

                    $schoolLogoSrc = 'data:' . $mime . ';base64,' . base64_encode($bin);
                    break;
                }
            }
        }

        // --- EIIN (your DB uses eiin_num) ---
        $eiin = null;
        foreach (['eiin_num', 'eiin', 'eiin_code', 'eiin_no'] as $field) {
            if (isset($school->{$field})) {
                $val = trim((string) $school->{$field});
                if ($val !== '') { $eiin = $val; break; }
            }
        }

        $website = $school->website ?? $school->website_url ?? $school->domain ?? null;
        if (is_string($website)) $website = trim($website);

        return [
            'school'        => $school,
            'schoolLogoSrc' => $schoolLogoSrc,
            'schoolPrint'   => [
                'name'    => $school->name ?? $school->short_name ?? 'Unknown School',
                'eiin'    => $eiin,
                'address' => $school->address ?? $school->full_address ?? null,
                'website' => $website,
            ],
        ];
    }

    /** ===================== LIST ===================== */
    public function assignSubjectList(Request $request)
    {
        $q = ClassSubject::query()
            ->leftJoin('classes as c', 'c.id', '=', 'class_subjects.class_id')
            ->leftJoin('subjects as s', 's.id', '=', 'class_subjects.subject_id')
            ->leftJoin('users as u', 'u.id', '=', 'class_subjects.created_by')
            ->select([
                'class_subjects.*',
                'class_subjects.class_id',
                'c.name as class_name',
                's.name as subject_name',
                'u.name as created_by_name',
            ])
            ->orderByDesc('class_subjects.id');

        if ($request->filled('class_id'))   $q->where('class_subjects.class_id', (int) $request->class_id);
        if ($request->filled('subject_id')) $q->where('class_subjects.subject_id', (int) $request->subject_id);
        if ($request->filled('status') && in_array((int)$request->status, [0,1], true)) {
            $q->where('class_subjects.status', (int)$request->status);
        }

        $data['getRecord']    = $q->paginate(15)->appends($request->except('page'));
        $data['header_title'] = 'Assign Subject List';
        $data['getClass']     = ClassModel::orderBy('name')->get(['id','name']);
        $data['getSubject']   = Subject::orderBy('name')->get(['id','name']);

        return view('admin.assign_subject.list', $data);
    }

    /** ===================== CREATE ===================== */
    public function add()
    {
        $data['getClass']     = ClassModel::orderBy('name')->get(['id','name']);
        $data['getSubject']   = Subject::orderBy('name')->get(['id','name']);
        $data['header_title'] = 'Assign Subject';
        return view('admin.assign_subject.add', $data);
    }

    /** ===================== STORE ===================== */
    public function assignSubjectAdd(Request $request)
    {
        $request->validate([
            'class_id'      => ['required','integer','exists:classes,id,deleted_at,NULL'],
            'subject_id'    => ['required','array','min:1'],
            'subject_id.*'  => ['integer','distinct','exists:subjects,id,deleted_at,NULL'],
            'status'        => ['required', Rule::in([0,1])],
        ]);

        $classId = (int) $request->class_id;
        $status  = (int) $request->status;
        $ids     = array_map('intval', $request->subject_id);

        DB::transaction(function () use ($classId, $status, $ids) {
            foreach ($ids as $sid) {
                $existing = ClassSubject::withTrashed()
                    ->where('class_id', $classId)
                    ->where('subject_id', $sid)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) $existing->restore();
                    $existing->status     = $status;
                    $existing->created_by = $existing->created_by ?? Auth::id();
                    $existing->save();
                } else {
                    ClassSubject::create([
                        'class_id'   => $classId,
                        'subject_id' => $sid,
                        'status'     => $status,
                        'created_by' => Auth::id(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.assign-subject.list')
            ->with('success', 'Subject(s) successfully assigned to class.');
    }

    /** ===================== EDIT SCREEN ===================== */
    public function assignSubjectEdit($id)
    {
        $assignSubject = ClassSubject::findOrFail($id);

        $data['getClass']   = ClassModel::orderBy('name')->get(['id','name']);
        $data['getSubject'] = Subject::orderBy('name')->get(['id','name']);
        $data['selectedSubjects'] = ClassSubject::where('class_id', $assignSubject->class_id)
            ->pluck('subject_id')->toArray();

        $data['assignSubject'] = $assignSubject;
        $data['header_title']  = 'Edit Assign Subject';

        return view('admin.assign_subject.add', $data);
    }

    /** ===================== UPDATE SET ===================== */
    public function assignSubjectUpdate(Request $request, $id)
    {
        $request->validate([
            'class_id'      => ['required','integer','exists:classes,id,deleted_at,NULL'],
            'subject_id'    => ['required','array','min:1'],
            'subject_id.*'  => ['integer','distinct','exists:subjects,id,deleted_at,NULL'],
            'status'        => ['required', Rule::in([0,1])],
        ]);

        ClassSubject::findOrFail($id);

        $classId = (int) $request->class_id;
        $status  = (int) $request->status;
        $ids     = array_map('intval', $request->subject_id);

        DB::transaction(function () use ($classId, $status, $ids) {
            ClassSubject::where('class_id', $classId)->delete();

            foreach ($ids as $sid) {
                $existing = ClassSubject::withTrashed()
                    ->where('class_id', $classId)
                    ->where('subject_id', $sid)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) $existing->restore();
                    $existing->status     = $status;
                    $existing->created_by = $existing->created_by ?? Auth::id();
                    $existing->save();
                } else {
                    ClassSubject::create([
                        'class_id'   => $classId,
                        'subject_id' => $sid,
                        'status'     => $status,
                        'created_by' => Auth::id(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.assign-subject.list')
            ->with('success', 'Assigned subjects updated successfully.');
    }

    /** ===================== DELETE (soft) ===================== */
    public function assignSubjectDelete(Request $request)
    {
        $request->validate([
            'id' => ['required','integer','exists:class_subjects,id'],
        ]);

        $assignSubject = ClassSubject::findOrFail((int) $request->id);
        $assignSubject->delete();

        return redirect()
            ->route('admin.assign-subject.list')
            ->with('success', 'Subject deleted successfully from the class.');
    }

    /** ===================== SINGLE ROW STATUS EDIT ===================== */
    public function singleEdit($id)
    {
        $assignSubject = ClassSubject::with(['class:id,name', 'subject:id,name'])->findOrFail($id);
        $data['assignSubject'] = $assignSubject;
        $data['header_title']  = 'Edit Single Subject';

        return view('admin.assign_subject.edit_single', $data);
    }

    public function updateSingleEdit(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', Rule::in([0,1])],
        ]);

        $assignSubject = ClassSubject::findOrFail($id);
        $assignSubject->status = (int) $request->status;
        $assignSubject->save();

        return redirect()
            ->route('admin.assign-subject.list')
            ->with('success', 'Subject status updated successfully.');
    }

    /** ===================== PDF DOWNLOAD ===================== */
    // public function download(Request $request)
    // {
    //     $q = ClassSubject::query()
    //         ->leftJoin('classes as c', 'c.id', '=', 'class_subjects.class_id')
    //         ->leftJoin('subjects as s', 's.id', '=', 'class_subjects.subject_id')
    //         ->leftJoin('users as u', 'u.id', '=', 'class_subjects.created_by')
    //         ->select([
    //             'class_subjects.*',
    //             'c.name as class_name',
    //             's.name as subject_name',
    //             'u.name as created_by_name',
    //         ])
    //         ->orderBy('c.name')
    //         ->orderBy('s.name');

    //     // Filters
    //     $classId   = $request->filled('class_id')   ? (int) $request->class_id   : null;
    //     $subjectId = $request->filled('subject_id') ? (int) $request->subject_id : null;

    //     $statusRaw = $request->input('status', null);
    //     $status    = null;
    //     if ($statusRaw !== null && $statusRaw !== '') {
    //         $statusMap = ['1'=>1,'0'=>0,'active'=>1,'inactive'=>0];
    //         $key = is_string($statusRaw) ? strtolower(trim($statusRaw)) : (string) $statusRaw;
    //         if (array_key_exists($key, $statusMap)) {
    //             $status = $statusMap[$key];
    //         } elseif (is_numeric($statusRaw)) {
    //             $status = (int) $statusRaw;
    //         }
    //     }

    //     if (!is_null($classId))   $q->where('class_subjects.class_id', $classId);
    //     if (!is_null($subjectId)) $q->where('class_subjects.subject_id', $subjectId);
    //     if (!is_null($status))    $q->where('class_subjects.status', $status);

    //     $records = $q->get();

    //     // Header data
    //     $header = $this->schoolHeaderData();

    //     // Data to view
    //     $data = [
    //         'records' => $records,
    //         'filters' => [
    //             'class_id'   => $classId,
    //             'subject_id' => $subjectId,
    //             'status'     => $status,
    //         ],
    //     ] + $header;

    //     // Filename
    //     $fileName = 'assign-subjects';
    //     if (!is_null($classId)) {
    //         $class = ClassModel::find($classId);
    //         if ($class) $fileName .= '-' . Str::slug($class->name);
    //     }
    //     if (!is_null($status)) {
    //         $fileName .= '-' . ($status === 1 ? 'active' : 'inactive');
    //     }
    //     $fileName .= '.pdf';

    //     $pdf = Pdf::loadView('pdf.assign_subject_list', $data)
    //               ->setPaper('A4', 'landscape');

    //     return $pdf->stream($fileName, ['Attachment' => false]);
    // }

public function download(Request $request)
{
    $q = \App\Models\ClassSubject::query()
        ->leftJoin('classes as c', 'c.id', '=', 'class_subjects.class_id')
        ->leftJoin('subjects as s', 's.id', '=', 'class_subjects.subject_id')
        ->leftJoin('users as u', 'u.id', '=', 'class_subjects.created_by')
        ->select([
            'class_subjects.*',
            'c.name as class_name',
            's.name as subject_name',
            'u.name as created_by_name',
        ])
        ->orderBy('c.name')
        ->orderBy('s.name');

    // filters (unchanged)
    $classId   = $request->filled('class_id')   ? (int) $request->class_id   : null;
    $subjectId = $request->filled('subject_id') ? (int) $request->subject_id : null;

    $statusRaw = $request->input('status', null);
    $status    = null;
    if ($statusRaw !== null && $statusRaw !== '') {
        $map = ['1'=>1,'0'=>0,'active'=>1,'inactive'=>0];
        $key = is_string($statusRaw) ? strtolower(trim($statusRaw)) : (string)$statusRaw;
        $status = array_key_exists($key, $map) ? $map[$key] : (is_numeric($statusRaw) ? (int)$statusRaw : null);
    }

    if (!is_null($classId))   $q->where('class_subjects.class_id', $classId);
    if (!is_null($subjectId)) $q->where('class_subjects.subject_id', $subjectId);
    if (!is_null($status))    $q->where('class_subjects.status', $status);

    $records = $q->get();

    // 🔹 Add school header data
    $header = $this->schoolHeaderData();

    $data = [
        'records' => $records,
        'filters' => [
            'class_id'   => $classId,
            'subject_id' => $subjectId,
            'status'     => $status,
        ],
    ] + $header;

    // filename (unchanged)
    $fileName = 'assign-subjects';
    if (!is_null($classId)) {
        $class = \App\Models\ClassModel::find($classId);
        if ($class) $fileName .= '-' . \Illuminate\Support\Str::slug($class->name);
    }
    if (!is_null($status)) $fileName .= '-' . ($status === 1 ? 'active' : 'inactive');
    $fileName .= '.pdf';

    return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.assign_subject_list', $data)
        ->setPaper('A4', 'portrait') // portrait to match your screenshot
        ->stream($fileName, ['Attachment' => false]);
}


}
