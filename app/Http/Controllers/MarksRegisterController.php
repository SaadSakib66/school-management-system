<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

use App\Models\Exam;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\ExamSchedule;
use App\Models\User;
use App\Models\MarksRegister;
use Carbon\Carbon;
use App\Models\AssignClassTeacherModel;
use App\Models\Subject;
use App\Models\MarksGrade;

class MarksRegisterController extends Controller
{
    /* -------------------------------
     * School-context helpers (P6)
     * ------------------------------- */
    protected function currentSchoolId(): ?int
    {
        $u = Auth::user();
        if (! $u) return null;
        if ($u->role === 'super_admin') {
            return (int) session('current_school_id');
        }
        return (int) $u->school_id;
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

    public function list(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $data['header_title'] = 'Marks Register';
        $data['exams']   = Exam::orderBy('name')->get(['id','name']);
        $data['classes'] = ClassModel::getClass();
        $data['grades']  = MarksGrade::orderBy('percent_from','desc')->get(['grade_name','percent_from','percent_to']);

        $selectedExamId  = (int) $request->get('exam_id');
        $selectedClassId = (int) $request->get('class_id');

        $data['selectedExamId']  = $selectedExamId ?: null;
        $data['selectedClassId'] = $selectedClassId ?: null;

        // Subjects assigned to the class (ACTIVE)
        $subjects = collect();
        if ($selectedClassId) {
            $subjects = ClassSubject::subjectsForClass($selectedClassId); // -> id, name
        }
        $data['subjects'] = $subjects;

        // Exam schedule for (passing/full) display
        $scheduleMap = collect();
        if ($selectedExamId && $selectedClassId) {
            $scheduleMap = ExamSchedule::where('exam_id', $selectedExamId)
                ->where('class_id', $selectedClassId)
                ->get()
                ->keyBy('subject_id');
        }
        $data['scheduleMap'] = $scheduleMap;

        // Students in the class
        $students = collect();
        if ($selectedClassId) {
            $students = User::where('role', 'student')
                ->where('class_id', $selectedClassId)
                ->orderBy('name')->orderBy('last_name')
                ->get(['id','name','last_name']);
        }
        $data['students'] = $students;

        // Existing marks keyed by [student_id][subject_id]
        $marks = [];
        if ($selectedExamId && $selectedClassId && $students->count() && $subjects->count()) {
            $existing = MarksRegister::where('exam_id',  $selectedExamId)
                ->where('class_id', $selectedClassId)
                ->whereIn('student_id', $students->pluck('id'))
                ->whereIn('subject_id', $subjects->pluck('id'))
                ->get();

            foreach ($existing as $row) {
                $marks[$row->student_id][$row->subject_id] = $row;
            }
        }
        $data['marks'] = $marks;

        return view('admin.marks_register.list', $data);
    }

    public function save(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $request->validate([
            'exam_id'        => ['required','exists:exams,id'],
            'class_id'       => ['required','exists:classes,id'],
            'marks'          => ['array'], // marks[student][subject][class_work|home_work|test_work|exam]
        ]);

        $examId  = (int) $request->exam_id;
        $classId = (int) $request->class_id;
        $rows    = $request->input('marks', []);

        $onlyStudentId = (int) $request->input('only_student_id');

        // If a per-row save was clicked, keep only that student's subtree
        if ($onlyStudentId) {
            $rows = isset($rows[$onlyStudentId]) ? [$onlyStudentId => $rows[$onlyStudentId]] : [];
        }

        // Allowed students/subjects for this class (guard tampering)
        $allowedStudentIds = User::where('role','student')
            ->where('class_id', $classId)
            ->pluck('id')
            ->toArray();

        $subjectList = ClassSubject::subjectsForClass($classId);
        $allowedSubjectIds = $subjectList->pluck('id')->toArray();

        // Pull schedule once to know full marks per subject
        $scheduleMap = ExamSchedule::where('exam_id', $examId)
            ->where('class_id', $classId)
            ->get()
            ->keyBy('subject_id');

        $toInt = static function($v) {
            if ($v === '' || $v === null) return null;
            $n = (int) $v;
            if ($n < 0) $n = 0;
            if ($n > 10000) $n = 10000;
            return $n;
        };

        DB::transaction(function () use ($rows, $examId, $classId, $toInt, $scheduleMap, $allowedStudentIds, $allowedSubjectIds) {
            foreach ($rows as $studentId => $subjects) {
                $studentId = (int) $studentId;
                if (!in_array($studentId, $allowedStudentIds, true)) {
                    continue; // skip students not in this class
                }

                foreach ($subjects as $subjectId => $vals) {
                    $subjectId = (int) $subjectId;
                    if (!in_array($subjectId, $allowedSubjectIds, true)) {
                        continue; // skip subjects not in this class
                    }

                    $cw = $toInt($vals['class_work'] ?? null);
                    $hw = $toInt($vals['home_work']  ?? null);
                    $tw = $toInt($vals['test_work']  ?? null);
                    $ex = $toInt($vals['exam']       ?? null);

                    $allEmpty = is_null($cw) && is_null($hw) && is_null($tw) && is_null($ex);

                    $keys = [
                        'exam_id'    => $examId,
                        'class_id'   => $classId,
                        'student_id' => $studentId,
                        'subject_id' => $subjectId,
                    ];

                    if ($allEmpty) {
                        if ($row = MarksRegister::withTrashed()->where($keys)->first()) {
                            $row->delete();
                        }
                        continue;
                    }

                    $sum = collect([$cw,$hw,$tw,$ex])->filter(fn($v)=>$v!==null)->sum();

                    // Optional guard: do not allow exceeding full mark if defined
                    $full = optional($scheduleMap->get($subjectId))->full_mark;
                    if (!is_null($full) && $sum > (int)$full) {
                        // Clamp to full. If you prefer validation, return back with error instead.
                        $sum = (int)$full;
                    }

                    $row = MarksRegister::withTrashed()->firstOrNew($keys);
                    if ($row->trashed()) $row->restore();

                    $row->class_work = $cw;
                    $row->home_work  = $hw;
                    $row->test_work  = $tw;
                    $row->exam_mark  = $ex;
                    $row->total      = $sum;

                    if (!$row->exists || !$row->created_by) {
                        $row->created_by = Auth::id();
                    }
                    $row->updated_by = Auth::id();

                    $row->save();
                }
            }
        });

        return redirect()->route('admin.marks-register.list', [
            'exam_id'  => $examId,
            'class_id' => $classId,
        ])->with('success', 'Marks saved successfully.');
    }

    // Teacher-specific methods

    public function teacherMarkRegisterList(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $teacher = Auth::user();
        abort_unless($teacher && $teacher->role === 'teacher', 403);

        // Only ACTIVE class assignments
        $assignedClassIds = AssignClassTeacherModel::where('teacher_id', $teacher->id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->pluck('class_id')
            ->unique()
            ->values();

        // Class dropdown shows only ACTIVE classes
        $classes = ClassModel::whereIn('id', $assignedClassIds)
            ->orderBy('name')
            ->get(['id','name']);

        $exams = Exam::orderBy('name')->get(['id','name']);

        $selectedExamId  = $request->integer('exam_id') ?: null;
        $selectedClassId = $request->integer('class_id') ?: null;

        // Block inactive / unassigned class
        if ($selectedClassId && !$assignedClassIds->contains($selectedClassId)) {
            abort(403, 'You are not assigned to this class (or it is inactive).');
        }

        // Subjects for the chosen class (ACTIVE)
        $subjects = collect();
        if ($selectedClassId) {
            $subjects = ClassSubject::subjectsForClass($selectedClassId); // -> id, name
        }

        // Exam schedule map for (passing/full)
        $scheduleMap = collect();
        if ($selectedExamId && $selectedClassId) {
            $scheduleMap = ExamSchedule::where('exam_id', $selectedExamId)
                ->where('class_id', $selectedClassId)
                ->get()
                ->keyBy('subject_id');
        }

        // Students in the chosen class
        $students = collect();
        if ($selectedClassId) {
            $students = User::where('role','student')
                ->where('class_id', $selectedClassId)
                ->orderBy('name')->orderBy('last_name')
                ->get(['id','name','last_name']);
        }

        // Existing marks keyed by [student_id][subject_id]
        $marks = [];
        if ($selectedExamId && $selectedClassId && $students->count() && $subjects->count()) {
            $existing = MarksRegister::where('exam_id',  $selectedExamId)
                ->where('class_id', $selectedClassId)
                ->whereIn('student_id', $students->pluck('id'))
                ->whereIn('subject_id', $subjects->pluck('id'))
                ->get();

            foreach ($existing as $row) {
                $marks[$row->student_id][$row->subject_id] = $row;
            }
        }

        return view('teacher.marks_register', [
            'header_title'    => 'Marks Register',
            'exams'           => $exams,
            'classes'         => $classes,
            'selectedExamId'  => $selectedExamId,
            'selectedClassId' => $selectedClassId,
            'subjects'        => $subjects,
            'students'        => $students,
            'scheduleMap'     => $scheduleMap,
            'marks'           => $marks,
        ]);
    }

    public function teacherMarkRegisterSave(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $teacher = Auth::user();
        abort_unless($teacher && $teacher->role === 'teacher', 403);

        $request->validate([
            'exam_id'        => ['required','exists:exams,id'],
            'class_id'       => ['required','exists:classes,id'],
            'marks'          => ['array'],
            'only_student_id'=> ['nullable','integer'],
        ]);

        $examId  = (int) $request->exam_id;
        $classId = (int) $request->class_id;
        $rows    = $request->input('marks', []);
        $onlyId  = (int) $request->input('only_student_id');

        // Only allow ACTIVE class assignments when saving
        $allowedClassIds = AssignClassTeacherModel::where('teacher_id', $teacher->id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->pluck('class_id');

        abort_unless($allowedClassIds->contains($classId), 403, 'You are not assigned to this class (or it is inactive).');

        // Per-row Save → keep only that student's subtree
        if ($onlyId) {
            $rows = isset($rows[$onlyId]) ? [$onlyId => $rows[$onlyId]] : [];
        }

        // Allowed students/subjects for this class
        $allowedStudentIds = User::where('role','student')
            ->where('class_id', $classId)
            ->pluck('id')
            ->toArray();

        $subjectList = ClassSubject::subjectsForClass($classId);
        $allowedSubjectIds = $subjectList->pluck('id')->toArray();

        // Schedule map for full marks
        $scheduleMap = ExamSchedule::where('exam_id', $examId)
            ->where('class_id', $classId)
            ->get()
            ->keyBy('subject_id');

        $toInt = static function($v) {
            if ($v === '' || $v === null) return null;
            $n = (int) $v;
            if ($n < 0) $n = 0;
            if ($n > 10000) $n = 10000;
            return $n;
        };

        DB::transaction(function () use ($rows, $examId, $classId, $toInt, $allowedStudentIds, $allowedSubjectIds, $scheduleMap, $teacher) {
            foreach ($rows as $studentId => $subjects) {
                $studentId = (int) $studentId;
                if (!in_array($studentId, $allowedStudentIds, true)) {
                    continue; // skip students not in this class
                }

                foreach ($subjects as $subjectId => $vals) {
                    $subjectId = (int) $subjectId;
                    if (!in_array($subjectId, $allowedSubjectIds, true)) {
                        continue; // skip subjects not in this class
                    }

                    $cw = $toInt($vals['class_work'] ?? null);
                    $hw = $toInt($vals['home_work']  ?? null);
                    $tw = $toInt($vals['test_work']  ?? null);
                    $ex = $toInt($vals['exam']       ?? null);

                    $allEmpty = is_null($cw) && is_null($hw) && is_null($tw) && is_null($ex);
                    $keys = [
                        'exam_id'    => $examId,
                        'class_id'   => $classId,
                        'student_id' => $studentId,
                        'subject_id' => $subjectId,
                    ];

                    if ($allEmpty) {
                        if ($row = MarksRegister::withTrashed()->where($keys)->first()) {
                            $row->delete();
                        }
                        continue;
                    }

                    $sum  = collect([$cw,$hw,$tw,$ex])->filter(fn($v)=>$v!==null)->sum();
                    $full = optional($scheduleMap->get($subjectId))->full_mark;
                    if (!is_null($full) && $sum > (int) $full) {
                        $sum = (int) $full; // clamp (or return with error if preferred)
                    }

                    $row = MarksRegister::withTrashed()->firstOrNew($keys);
                    if ($row->trashed()) $row->restore();

                    $row->class_work = $cw;
                    $row->home_work  = $hw;
                    $row->test_work  = $tw;
                    $row->exam_mark  = $ex;
                    $row->total      = $sum;

                    if (!$row->exists || !$row->created_by) {
                        $row->created_by = $teacher->id;
                    }
                    $row->updated_by = $teacher->id;

                    $row->save();
                }
            }
        });

        return redirect()->route('teacher.marks-register.list', [
            'exam_id'  => $examId,
            'class_id' => $classId,
        ])->with('success', 'Marks saved successfully.');
    }

    // Student-specific method

    public function studentMarkRegisterList(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $student = Auth::user();
        abort_unless($student && $student->role === 'student', 403);

        if (!$student->class_id) {
            return view('student.marks_register', [
                'header_title'   => 'My Exam Result',
                'exams'          => collect(),
                'selectedExamId' => null,
                'sections'       => [],
                'noClass'        => true,
            ]);
        }

        $classId = (int) $student->class_id;

        // Exams that actually have a schedule for THIS class
        $exams = Exam::select('exams.id', 'exams.name')
            ->join('exam_schedules as es', 'es.exam_id', '=', 'exams.id')
            ->where('es.class_id', $classId)
            ->groupBy('exams.id', 'exams.name')
            ->orderBy('exams.name')
            ->get();

        $selectedExamId  = $request->integer('exam_id') ?: null;
        $examIdsToShow   = $selectedExamId ? [$selectedExamId] : $exams->pluck('id')->all();

        // Grade bands (per school via global scope)
        $gradeBands = MarksGrade::orderBy('percent_from', 'desc')
            ->get(['grade_name','percent_from','percent_to']);

        $pickGrade = static function (?float $pct) use ($gradeBands) {
            if ($pct === null) return null;
            foreach ($gradeBands as $g) {
                if ($pct >= (float)$g->percent_from && $pct <= (float)$g->percent_to) {
                    return $g->grade_name;
                }
            }
            return null;
        };

        $sections = [];

        if (!empty($examIdsToShow)) {
            $schedules = ExamSchedule::whereIn('exam_id', $examIdsToShow)
                ->where('class_id', $classId)
                ->get();

            $subjectNames = Subject::whereIn('id', $schedules->pluck('subject_id')->unique())
                ->pluck('name', 'id');

            $marksByExamSubject = MarksRegister::where('class_id', $classId)
                ->where('student_id', $student->id)
                ->whereIn('exam_id', $examIdsToShow)
                ->get()
                ->keyBy(fn ($r) => $r->exam_id . '|' . $r->subject_id);

            foreach ($examIdsToShow as $eid) {
                $exam = $exams->firstWhere('id', $eid);
                if (!$exam) continue;

                $rows        = [];
                $grandFull   = 0;
                $grandTotal  = 0;
                $anyFail     = false;

                $examSchedules = $schedules->where('exam_id', $eid);
                foreach ($examSchedules as $es) {
                    $sid        = (int) $es->subject_id;
                    $subName    = $subjectNames[$sid] ?? ('Subject #' . $sid);
                    $full       = (int) ($es->full_mark ?? 0);
                    $passing    = (int) ($es->passing_mark ?? 0);
                    $grandFull += $full;

                    $mark = $marksByExamSubject->get($eid.'|'.$sid);
                    $cw = (int) ($mark->class_work ?? 0);
                    $tw = (int) ($mark->test_work  ?? 0);
                    $hw = (int) ($mark->home_work  ?? 0);
                    $ex = (int) ($mark->exam_mark  ?? 0);
                    $ttl = (int) ($mark->total      ?? ($cw + $tw + $hw + $ex));

                    $grandTotal += $ttl;

                    $pass = $ttl >= $passing;
                    if (!$pass) $anyFail = true;

                    $rows[] = [
                        'subject'       => $subName,
                        'class_work'    => $cw,
                        'test_work'     => $tw,
                        'home_work'     => $hw,
                        'exam'          => $ex,
                        'total'         => $ttl,
                        'passing_mark'  => $passing,
                        'full_mark'     => $full,
                        'result'        => $pass ? 'Pass' : 'Fail',
                    ];
                }

                $percentage = $grandFull > 0 ? round($grandTotal * 100 / $grandFull, 2) : null;
                $overall    = $anyFail ? 'Fail' : 'Pass';
                $gradeName  = $pickGrade($percentage);

                $sections[] = [
                    'exam'       => $exam,
                    'rows'       => $rows,
                    'grandFull'  => $grandFull,
                    'grandTotal' => $grandTotal,
                    'percentage' => $percentage,
                    'overall'    => $overall,
                    'grade'      => $gradeName,
                ];
            }
        }

        return view('student.marks_register', [
            'header_title'   => 'My Exam Result',
            'exams'          => $exams,
            'selectedExamId' => $selectedExamId,
            'sections'       => $sections,
            'noClass'        => false,
        ]);
    }

    // Parent-specific method

    public function parentMarkRegisterList(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $parent = Auth::user();
        abort_unless($parent && $parent->role === 'parent', 403);

        // Children assigned to this parent
        $children = $this->childrenForParent($parent->id);

        // Auto-select the only child (if exactly one)
        $selectedStudentId = $request->integer('student_id')
            ?: ($children->count() === 1 ? (int) $children->first()->id : null);

        // Ensure the selection belongs to this parent
        if ($selectedStudentId && !$children->pluck('id')->contains($selectedStudentId)) {
            abort(403, 'This student is not assigned to you.');
        }

        // Build a map of exams per student (based on each child's class)
        $examsByStudent = [];
        if ($children->isNotEmpty()) {
            $classIds = $children->pluck('class_id')->filter()->unique()->values();

            $examRows = Exam::select('exams.id', 'exams.name', 'es.class_id')
                ->join('exam_schedules as es', 'es.exam_id', '=', 'exams.id')
                ->whereIn('es.class_id', $classIds)
                ->groupBy('exams.id', 'exams.name', 'es.class_id')
                ->orderBy('exams.name')
                ->get();

            foreach ($children as $ch) {
                $examsByStudent[$ch->id] = $examRows->where('class_id', $ch->class_id)
                    ->map(fn($r) => ['id' => $r->id, 'name' => $r->name])
                    ->values()
                    ->all();
            }
        }

        // Exams to render for the currently selected child (server-side initial state)
        $exams = collect($selectedStudentId ? ($examsByStudent[$selectedStudentId] ?? []) : [])
            ->map(fn($e) => (object) $e);

        $selectedExamId = $request->integer('exam_id') ?: null;

        // Only show a section when BOTH student and exam are chosen and the exam is valid for that student
        $section = null;
        if ($selectedStudentId && $selectedExamId) {
            $child = $children->firstWhere('id', $selectedStudentId);
            $selectedClassId = (int) ($child->class_id ?? 0);

            // Validate the exam belongs to this student's class
            $allowedExamIds = collect($examsByStudent[$selectedStudentId] ?? [])->pluck('id');
            if ($selectedClassId && $allowedExamIds->contains($selectedExamId)) {

                // Pull schedules for this exam + class
                $schedules = ExamSchedule::where('exam_id', $selectedExamId)
                    ->where('class_id', $selectedClassId)
                    ->get();

                if ($schedules->isNotEmpty()) {
                    $subjectNames = Subject::whereIn('id', $schedules->pluck('subject_id')->unique())
                        ->pluck('name', 'id');

                    // Student's marks for this exam
                    $marksBySubject = MarksRegister::where('class_id', $selectedClassId)
                        ->where('student_id', $selectedStudentId)
                        ->where('exam_id', $selectedExamId)
                        ->get()
                        ->keyBy('subject_id');

                    $rows = [];
                    $grandFull = 0;
                    $grandTotal = 0;
                    $anyFail = false;

                    foreach ($schedules as $es) {
                        $sid     = (int) $es->subject_id;
                        $sName   = $subjectNames[$sid] ?? ('Subject #'.$sid);
                        $full    = (int) ($es->full_mark ?? 0);
                        $passing = (int) ($es->passing_mark ?? 0);

                        $grandFull += $full;

                        $m  = $marksBySubject->get($sid);
                        $cw = (int) ($m->class_work ?? 0);
                        $tw = (int) ($m->test_work  ?? 0);
                        $hw = (int) ($m->home_work  ?? 0);
                        $ex = (int) ($m->exam_mark  ?? 0);
                        $tt = (int) ($m->total      ?? ($cw + $tw + $hw + $ex));

                        $grandTotal += $tt;
                        $pass = $tt >= $passing;
                        if (!$pass) $anyFail = true;

                        $rows[] = [
                            'subject'      => $sName,
                            'class_work'   => $cw,
                            'test_work'    => $tw,
                            'home_work'    => $hw,
                            'exam'         => $ex,
                            'total'        => $tt,
                            'passing_mark' => $passing,
                            'full_mark'    => $full,
                            'result'       => $pass ? 'Pass' : 'Fail',
                        ];
                    }

                    $percentage = $grandFull > 0 ? round($grandTotal * 100 / $grandFull, 2) : null;
                    $overall    = $anyFail ? 'Fail' : 'Pass';

                    $examName = collect($examsByStudent[$selectedStudentId] ?? [])
                        ->firstWhere('id', $selectedExamId)['name'] ?? 'Selected Exam';

                    $section = [
                        'exam'       => (object)['name' => $examName],
                        'rows'       => $rows,
                        'grandFull'  => $grandFull,
                        'grandTotal' => $grandTotal,
                        'percentage' => $percentage,
                        'overall'    => $overall,
                    ];
                }
            }
        }

        return view('parent.marks_register', [
            'header_title'      => 'Child Exam Results',
            'children'          => $children,
            'exams'             => $exams,              // options for current child (for first render)
            'examsByStudent'    => $examsByStudent,     // full map for JS to repopulate instantly
            'selectedStudentId' => $selectedStudentId,
            'selectedExamId'    => $selectedExamId,
            'section'           => $section,            // null unless both chosen
        ]);
    }

    /**
     * Return children assigned to this parent as a collection of:
     * [id, name, last_name, class_id]
     * Supports either a pivot table `parent_students (parent_id, student_id)`
     * or a direct users.parent_id column.
     */
    private function childrenForParent(int $parentId)
    {
        if (Schema::hasTable('parent_students')) {
            return User::select('users.id','users.name','users.last_name','users.class_id')
                ->join('parent_students as ps', 'ps.student_id', '=', 'users.id')
                ->where('ps.parent_id', $parentId)
                ->where('users.role', 'student')
                ->whereNull('users.deleted_at')
                ->orderBy('users.name')->orderBy('users.last_name')
                ->get();
        }

        if (Schema::hasColumn('users', 'parent_id')) {
            return User::where('role','student')
                ->where('parent_id', $parentId)
                ->whereNull('deleted_at')
                ->orderBy('name')->orderBy('last_name')
                ->get(['id','name','last_name','class_id']);
        }

        return collect();
    }
}
