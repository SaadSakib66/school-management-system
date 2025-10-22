<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\ClassSubject;
use App\Models\ClassModel;
use App\Models\Week;
use App\Models\ClassTimetable;
use App\Models\AssignClassTeacherModel;
use App\Models\User;
use App\Models\Subject;

use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class ClassTimetableController extends Controller
{
    /* ======================= helpers ======================= */

    protected function currentSchoolId(): ?int
    {
        $u = Auth::user();
        if (!$u) return session('current_school_id') ?: session('school_id');
        if (($u->role ?? null) === 'super_admin') {
            return session('current_school_id') ?: session('school_id');
        }
        return $u->school_id ?: (session('current_school_id') ?: session('school_id'));
    }

    /**
     * Universal School Header data (logo/name/EIIN/address/website)
     * Returns: ['school','schoolLogoSrc','schoolPrint'=>[...] ]
     */
    protected function schoolHeaderData(?int $forceSchoolId = null): array
    {
        $schoolId =
            $forceSchoolId
            ?? $this->currentSchoolId()
            ?? (Auth::check() ? (int) Auth::user()->school_id : null)
            ?? session('current_school_id')
            ?? session('school_id');

        if (!$schoolId) {
            $fallback = \App\Models\School::query()->orderBy('id')->value('id');
            if ($fallback) $schoolId = (int) $fallback;
            else abort(403, 'No school context.');
        }

        /** @var \App\Models\School $school */
        $school = \App\Models\School::findOrFail($schoolId);

        // Logo -> data URI
        $logoFile = $school->logo ?? $school->school_logo ?? $school->photo ?? null;
        $schoolLogoSrc = null;

        if ($logoFile) {
            $normalized = ltrim(str_replace(['public/', 'storage/'], '', $logoFile), '/');
            $candidates = [$normalized, 'schools/'.basename($normalized), 'school_logos/'.basename($normalized)];
            foreach ($candidates as $path) {
                if (Storage::disk('public')->exists($path)) {
                    $bin = Storage::disk('public')->get($path);

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

                    $schoolLogoSrc = 'data:'.$mime.';base64,'.base64_encode($bin);
                    break;
                }
            }
        }

        // EIIN (DB uses eiin_num)
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

    /**
     * AJAX: subjects assigned to a class (active only).
     * Route param name uses {class_id} (not {class}).
     */
    public function subjectsForClass($class_id)
    {
        // Handle super_admin without a selected school cleanly
        $user = Auth::user();
        if ($user && $user->role === 'super_admin') {
            $currentSchoolId = (int) session('current_school_id');
            if (! $currentSchoolId) {
                return response()->json([
                    'message' => 'Please select a school first.'
                ], 409);
            }
        }

        // If class is not visible in current school scope, return a clear 404 JSON
        $class = ClassModel::find((int) $class_id);
        if (! $class) {
            return response()->json([
                'message' => 'Class not found or not accessible in the current school.'
            ], 404);
        }

        $subjects = ClassSubject::subjectsForClass($class->id);
        return response()->json($subjects, 200);
    }

    /**
     * Admin: timetable builder screen.
     */
    public function list(Request $request)
    {
        $data['header_title'] = 'Class Timetable';

        // Classes are already school-scoped via global scope
        $data['getClass'] = ClassModel::orderBy('name')->get(['id','name']);

        $selectedClassId   = (int) $request->get('class_id');
        $selectedSubjectId = (int) $request->get('subject_id');

        // Subjects dropdown (only when a valid class from current school is chosen)
        $data['getSubject'] = collect();
        if ($selectedClassId) {
            $class = ClassModel::findOrFail($selectedClassId);
            $data['getSubject'] = ClassSubject::subjectsForClass($class->id);
        }

        $data['selectedClassId']   = $selectedClassId ?: null;
        $data['selectedSubjectId'] = $selectedSubjectId ?: null;
        $data['weeks']             = Week::orderBy('sort')->get(['id','name','sort']);

        // Existing rows keyed by week_id (only when both selected & visible)
        $data['existing'] = collect();
        if ($selectedClassId && $selectedSubjectId) {
            ClassModel::findOrFail($selectedClassId);
            Subject::findOrFail($selectedSubjectId);

            $data['existing'] = ClassTimetable::where('class_id', $selectedClassId)
                ->where('subject_id', $selectedSubjectId)
                ->get()
                ->keyBy('week_id');
        }

        return view('admin.class_timetable.list', $data);
    }

    /**
     * Admin: save timetable rows for a class+subject for all weekdays.
     */
    public function save(Request $request)
    {
        $request->validate([
            'class_id'    => ['required','integer'],
            'subject_id'  => ['required','integer'],
            'start_time'  => ['array'],
            'end_time'    => ['array'],
            'room_number' => ['array'],
        ]);

        $class   = ClassModel::findOrFail((int) $request->class_id);
        $subject = Subject::findOrFail((int) $request->subject_id);

        $isAssigned = ClassSubject::where('class_id', $class->id)
            ->where('subject_id', $subject->id)
            ->where('status', 1)
            ->exists();

        if (! $isAssigned) {
            return back()->with('error', 'Selected subject is not assigned to this class.')->withInput();
        }

        $weeks = Week::pluck('id');

        DB::transaction(function () use ($request, $weeks, $class, $subject) {
            foreach ($weeks as $weekId) {
                $start = trim((string) ($request->input("start_time.$weekId") ?? ''));
                $end   = trim((string) ($request->input("end_time.$weekId")   ?? ''));
                $room  = trim((string) ($request->input("room_number.$weekId") ?? ''));

                if ($start === '' && $end === '' && $room === '') {
                    ClassTimetable::where([
                        'class_id'   => $class->id,
                        'subject_id' => $subject->id,
                        'week_id'    => (int) $weekId,
                    ])->delete();
                    continue;
                }

                ClassTimetable::updateOrCreate(
                    [
                        'class_id'   => $class->id,
                        'subject_id' => $subject->id,
                        'week_id'    => (int) $weekId,
                    ],
                    [
                        'start_time'  => $start !== '' ? $start : null,
                        'end_time'    => $end   !== '' ? $end   : null,
                        'room_number' => $room  !== '' ? $room  : null,
                        'school_id'   => $class->school_id ?? null,
                    ]
                );
            }
        });

        return redirect()
            ->route('admin.class-timetable.list', [
                'class_id'   => $class->id,
                'subject_id' => $subject->id,
            ])
            ->with('success', 'Timetable saved.');
    }

    /**
     * Student: my timetable.
     */
    public function myTimetablelist()
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'student', 403);

        $classId = $user->class_id;
        $class   = $classId ? ClassModel::select('id','name')->find($classId) : null;

        $data = [
            'header_title' => 'My Class Timetable',
            'classId'      => $classId,
            'class'        => $class,
            'weeks'        => Week::orderBy('sort')->get(['id','name','sort']),
        ];

        if (! $classId || ! $class) {
            $data['byWeek'] = collect();
            return view('student.my_timetable', $data);
        }

        $rows = ClassTimetable::with(['subject:id,name','week:id,name,sort'])
            ->join('class_subjects as cs', function ($j) use ($classId) {
                $j->on('cs.subject_id', '=', 'class_timetables.subject_id')
                  ->where('cs.class_id', $classId)
                  ->where('cs.status', 1)
                  ->whereNull('cs.deleted_at');
            })
            ->where('class_timetables.class_id', $classId)
            ->select('class_timetables.*')
            ->distinct()
            ->orderBy('class_timetables.week_id')
            ->orderBy('class_timetables.start_time')
            ->get();

        $data['byWeek'] = $rows->groupBy('week_id');

        return view('student.my_timetable', $data);
    }

    /**
     * Teacher: view timetable for one of their assigned classes.
     */
    public function teacherTimetable(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'teacher', 403);

        // Scope to the teacher's school (teacher routes won't hit super_admin context)
        $schoolId = $user->school_id ?: null;

        // Pull only ACTIVE assignments and ACTIVE classes for this teacher
        $classes = AssignClassTeacherModel::classesForTeacher(
            teacherId: $user->id,
            schoolId:  $schoolId,
            onlyActiveAssignments: true,
            onlyActiveClasses:     true
        );

        $selectedClassId = (int) $request->get('class_id');

        $weeks  = Week::orderBy('sort')->get(['id','name','sort']);
        $byWeek = collect();

        if ($selectedClassId) {
            if (! $classes->pluck('id')->contains($selectedClassId)) {
                abort(403, 'Not your class.');
            }

            $rows = ClassTimetable::with(['subject:id,name','week:id,name,sort'])
                ->join('class_subjects as cs', function ($j) use ($selectedClassId) {
                    $j->on('cs.subject_id', '=', 'class_timetables.subject_id')
                    ->where('cs.class_id', $selectedClassId)
                    ->where('cs.status', 1)
                    ->whereNull('cs.deleted_at');
                })
                ->where('class_timetables.class_id', $selectedClassId)
                ->select('class_timetables.*')
                ->distinct()
                ->orderBy('class_timetables.week_id')
                ->orderBy('class_timetables.start_time')
                ->get();

            $byWeek = $rows->groupBy('week_id');
        }

        return view('teacher.my_timetable', [
            'header_title'    => 'My Class Timetable',
            'classes'         => $classes,
            'selectedClassId' => $selectedClassId ?: null,
            'weeks'           => $weeks,
            'byWeek'          => $byWeek,
        ]);
    }

    /**
     * Parent: view a selected child’s timetable (with PDF branch).
     */
//  public function parentTimetable(Request $request) { /* old commented version kept intentionally */ }

    public function parentTimetable(Request $request)
    {
        $parent = Auth::user();
        abort_unless($parent && $parent->role === 'parent', 403);

        $students = User::select('id','name','last_name','class_id')
            ->where('role', 'student')
            ->where('parent_id', $parent->id)
            ->orderBy('name')
            ->get(); // SoftDeletes global scope already filters deleted_at

        $selectedStudentId = null;
        if ($students->count() === 1) {
            $selectedStudentId = (int) $students->first()->id;
        } elseif ($request->filled('student_id')) {
            $candidate = (int) $request->get('student_id');
            if ($students->firstWhere('id', $candidate)) {
                $selectedStudentId = $candidate;
            }
        }

        $selectedStudent = $selectedStudentId ? $students->firstWhere('id', $selectedStudentId) : null;

        $class   = null;
        $weeks   = Week::orderBy('sort')->get(['id','name','sort']);
        $byWeek  = collect();
        $rows    = collect();

        if ($selectedStudent && $selectedStudent->class_id) {
            $classId = $selectedStudent->class_id;
            $class   = ClassModel::select('id','name')->find($classId);

            if ($class) {
                $rows = ClassTimetable::with(['subject:id,name','week:id,name,sort'])
                    ->join('class_subjects as cs', function ($j) use ($classId) {
                        $j->on('cs.subject_id', '=', 'class_timetables.subject_id')
                          ->where('cs.class_id', $classId)
                          ->where('cs.status', 1)
                          ->whereNull('cs.deleted_at');
                    })
                    ->where('class_timetables.class_id', $classId)
                    ->select('class_timetables.*')
                    ->distinct()
                    ->orderBy('class_timetables.week_id')
                    ->orderBy('class_timetables.start_time')
                    ->get();

                $byWeek = $rows->groupBy('week_id');
            }
        }

        /* ---------- PDF Download branch ---------- */
        if ($request->boolean('download')) {
            if (! $selectedStudent) {
                return back()->with('error', 'Please select a student first.');
            }
            if (! $class) {
                return back()->with('error', 'Selected student is not assigned to any class.');
            }
            if ($rows->isEmpty()) {
                return back()->with('error', 'No timetable entries found for this class.');
            }

            $data = [
                'title'        => 'Class Routine',
                'student'      => $selectedStudent,
                'class'        => $class,
                'weeks'        => $weeks,
                'rows'         => $rows,
                'byWeek'       => $byWeek,
                'generated_at' => now(),
            ] + $this->schoolHeaderData(); // 🔹 add header data

            $filename = 'Class-Routine_'.str_replace(' ', '-', trim($selectedStudent->name.' '.$selectedStudent->last_name)).'_'.($class->name ?? 'Class').'.pdf';

            return PDF::loadView('pdf.parent_class_schedule', $data)
                ->setPaper('a4', 'portrait')
                ->stream($filename); // inline preview instead of download
        }
        /* ---------- /PDF Download branch ---------- */

        return view('parent.my_timetable', [
            'header_title'      => 'My Child’s Timetable',
            'students'          => $students,
            'selectedStudentId' => $selectedStudentId,
            'selectedStudent'   => $selectedStudent,
            'class'             => $class,
            'weeks'             => $weeks,
            'byWeek'            => $byWeek,
        ]);
    }

    /**
     * Admin: download class schedule PDF (with header).
     */
    public function download(Request $request)
    {
        $request->validate([
            'class_id'   => ['required','integer'],
            'subject_id' => ['nullable','integer'],
        ]);

        $classId   = (int) $request->class_id;
        $subjectId = $request->filled('subject_id') ? (int) $request->subject_id : null;

        $class   = ClassModel::select('id','name','school_id')->findOrFail($classId);
        $subject = $subjectId ? Subject::select('id','name')->findOrFail($subjectId) : null;

        $weeks = Week::orderBy('sort')->get(['id','name','sort']);
        $weekIds = $weeks->pluck('id')->all();

        // Build hourly slots (07:00–19:00)
        $slots = [];
        for ($h = 7; $h < 19; $h++) {
            $key = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
            $slots[$key] = sprintf('%02d:00-%02d:00', $h, $h+1);
        }

        // Fetch rows (only active assignments)
        $q = ClassTimetable::with(['subject:id,name'])
            ->join('class_subjects as cs', function ($j) use ($classId) {
                $j->on('cs.subject_id', '=', 'class_timetables.subject_id')
                  ->where('cs.class_id', $classId)
                  ->where('cs.status', 1)
                  ->whereNull('cs.deleted_at');
            })
            ->where('class_timetables.class_id', $classId);
        if ($subject) $q->where('class_timetables.subject_id', $subject->id);

        $rows = $q->select('class_timetables.*')->get();

        // Matrix: [slotKey][week_id] = string
        $grid = [];
        foreach ($slots as $k => $label) $grid[$k] = array_fill_keys($weekIds, '');
        foreach ($rows as $r) {
            if (!$r->start_time || !$r->end_time) continue;
            $slotKey = substr($r->start_time, 0, 2) . ':00'; // floor to hour
            if (!isset($grid[$slotKey])) continue;
            $txt = ($r->subject->name ?? 'N/A') . "\n"
                 . substr($r->start_time,0,5) . ' - ' . substr($r->end_time,0,5);
            if ($r->room_number) $txt .= "\nRoom: ".$r->room_number;
            $wk = (int)$r->week_id;
            $grid[$slotKey][$wk] = trim($grid[$slotKey][$wk] . ($grid[$slotKey][$wk] ? "\n" : "") . $txt);
        }

        $params = [
            'class'     => $class,
            'subject'   => $subject,
            'weeks'     => $weeks,
            'slots'     => $slots,
            'grid'      => $grid,
            'title'     => $subject ? "Class Schedule - {$class->name} ({$subject->name})"
                                    : "Class Schedule - {$class->name}",
            'generated' => now()->format('d M Y g:i A'),
        ] + $this->schoolHeaderData(); // 🔹 add header data

        $pdf = PDF::loadView('pdf.class_schedule', $params)
            ->setPaper('a4', 'landscape');

        $file = $subject
            ? "Class_Schedule_{$class->name}_{$subject->name}.pdf"
            : "Class_Schedule_{$class->name}.pdf";

        return $pdf->stream($file, ['Attachment' => false]);
    }

    /**
     * Teacher: download class schedule PDF (with header).
     */
    public function teacherDownload(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'teacher', 403);

        $request->validate([
            'class_id'   => ['required','integer'],
            'subject_id' => ['nullable','integer'], // optional
        ]);

        $classId   = (int) $request->class_id;
        $subjectId = $request->filled('subject_id') ? (int) $request->subject_id : null;

        // Ensure class is under this teacher (active assignment & class)
        $allowedClasses = AssignClassTeacherModel::classesForTeacher(
            teacherId: $user->id,
            schoolId:  $user->school_id,
            onlyActiveAssignments: true,
            onlyActiveClasses:     true
        );
        abort_unless($allowedClasses->pluck('id')->contains($classId), 403, 'Not your class.');

        $class   = ClassModel::select('id','name','school_id')->findOrFail($classId);
        $subject = $subjectId ? Subject::select('id','name')->findOrFail($subjectId) : null;

        $weeks = Week::orderBy('sort')->get(['id','name','sort']);
        $weekIds = $weeks->pluck('id')->all();

        // Slots (07:00–19:00)
        $slots = [];
        for ($h = 7; $h < 19; $h++) {
            $key = sprintf('%02d:00', $h);
            $slots[$key] = sprintf('%02d:00-%02d:00', $h, $h+1);
        }

        // Fetch rows (require class_subjects active link)
        $q = ClassTimetable::with(['subject:id,name'])
            ->join('class_subjects as cs', function ($j) use ($classId) {
                $j->on('cs.subject_id', '=', 'class_timetables.subject_id')
                  ->where('cs.class_id', $classId)
                  ->where('cs.status', 1)
                  ->whereNull('cs.deleted_at');
            })
            ->where('class_timetables.class_id', $classId);

        if ($subject) {
            $q->where('class_timetables.subject_id', $subject->id);
        }

        $rows = $q->select('class_timetables.*')->get();

        // Build grid
        $grid = [];
        foreach ($slots as $k => $label) $grid[$k] = array_fill_keys($weekIds, '');
        foreach ($rows as $r) {
            if (!$r->start_time || !$r->end_time) continue;
            $slotKey = substr($r->start_time, 0, 2) . ':00';
            if (!isset($grid[$slotKey])) continue;

            $txt = ($r->subject->name ?? 'N/A') . "\n"
                 . substr($r->start_time,0,5) . ' - ' . substr($r->end_time,0,5);
            if ($r->room_number) $txt .= "\nRoom: ".$r->room_number;

            $grid[$slotKey][(int)$r->week_id] = trim($grid[$slotKey][(int)$r->week_id] . ($grid[$slotKey][(int)$r->week_id] ? "\n" : "") . $txt);
        }

        $params = [
            'class'     => $class,
            'subject'   => $subject,
            'weeks'     => $weeks,
            'slots'     => $slots,
            'grid'      => $grid,
            'title'     => $subject
                            ? "Class Schedule - {$class->name} ({$subject->name})"
                            : "Class Schedule - {$class->name}",
            'generated' => now()->format('d M Y g:i A') . ' — Teacher Copy',
        ] + $this->schoolHeaderData(); // 🔹 add header data

        $pdf = PDF::loadView('pdf.class_schedule', $params)
            ->setPaper('a4', 'landscape');

        $file = $subject
            ? "Class_Schedule_{$class->name}_{$subject->name}.pdf"
            : "Class_Schedule_{$class->name}.pdf";

        return $pdf->stream($file, ['Attachment' => false]);
    }
}
