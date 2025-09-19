<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

use App\Models\Exam;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\ExamSchedule;
use App\Models\AssignClassTeacherModel;
use App\Models\User;

class ExamScheduleController extends Controller
{
    /* ------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------ */
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

    /* ------------------------------------------------------------
     * Admin: list + edit grid
     * ------------------------------------------------------------ */
    public function list(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $data['header_title'] = 'Exam Schedule';

        // Exams/classes scoped by SchoolScope
        $data['exams']   = Exam::orderBy('name')->get(['id','name']);
        $data['classes'] = ClassModel::getClass();

        $selectedExamId  = (int) $request->get('exam_id');
        $selectedClassId = (int) $request->get('class_id');

        // Optionally ensure the ids belong to this school (avoids query string spoofing)
        if ($selectedExamId && ! Exam::whereKey($selectedExamId)->exists()) {
            $selectedExamId = 0;
        }
        if ($selectedClassId && ! ClassModel::whereKey($selectedClassId)->exists()) {
            $selectedClassId = 0;
        }

        $data['selectedExamId']  = $selectedExamId ?: null;
        $data['selectedClassId'] = $selectedClassId ?: null;

        // subjects assigned to this class (status=1, not soft-deleted)
        $data['subjects'] = collect();
        if ($selectedClassId) {
            $data['subjects'] = ClassSubject::subjectsForClass($selectedClassId); // -> id, name
        }

        // existing schedules keyed by subject_id for quick lookup
        $data['existing'] = collect();
        if ($selectedExamId && $selectedClassId) {
            $data['existing'] = ExamSchedule::where('exam_id',  $selectedExamId)
                ->where('class_id', $selectedClassId)
                ->get()
                ->keyBy('subject_id');
        }

        return view('admin.exam_schedule.list', $data);
    }

    public function save(Request $request)
    {
        if ($resp = $this->guardSchoolContext()) return $resp;

        $schoolId = $this->currentSchoolId();

        // Validate IDs against current school (use Rule::exists + where)
        $request->validate([
            'exam_id' => [
                'required',
                'integer',
                Rule::exists('exams', 'id')->where(fn($q) => $q->where('school_id', $schoolId)),
            ],
            'class_id' => [
                'required',
                'integer',
                Rule::exists('classes', 'id')->where(fn($q) => $q->where('school_id', $schoolId)),
            ],
            // arrays are optional; we validate per-subject below
            'exam_date'       => ['array'],
            'exam_date.*'     => ['nullable','date_format:d-m-Y'],
            'start_time'      => ['array'],
            'start_time.*'    => ['nullable','string'],
            'end_time'        => ['array'],
            'end_time.*'      => ['nullable','string'],
            'room_number'     => ['array'],
            'room_number.*'   => ['nullable','string','max:100'],
            'full_mark'       => ['array'],
            'full_mark.*'     => ['nullable','integer','min:0','max:10000'],
            'passing_mark'    => ['array'],
            'passing_mark.*'  => ['nullable','integer','min:0','max:10000'],
        ]);

        $examId  = (int) $request->exam_id;
        $classId = (int) $request->class_id;

        // Resolve in-scope models (throws 404 if cross-school or missing)
        $exam  = Exam::findOrFail($examId);
        $class = ClassModel::findOrFail($classId);

        // Subjects assigned to this class (ACTIVE only)
        $subjects = ClassSubject::subjectsForClass($classId); // -> id, name

        // Helpers to normalize inputs
        $toTime = static function ($v) {
            if ($v === null || $v === '') return null;
            return Carbon::parse($v)->format('H:i:s'); // supports "10:00", "10 AM", etc.
        };

        $toDate = static function ($v) {
            $v = trim((string) $v);
            if ($v === '') return null;
            $v = str_replace(['/', '.', ' '], '-', $v); // allow 24/08/2025 etc.
            try {
                $dt = Carbon::createFromFormat('d-m-Y', $v);
                $errors = Carbon::getLastErrors();
                if (($errors['warning_count'] ?? 0) || ($errors['error_count'] ?? 0)) {
                    return null;
                }
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        };

        // Extra sanity checks per subject row
        foreach ($subjects as $s) {
            $sid  = (int) $s->id;

            $full = $request->input("full_mark.$sid");
            $pass = $request->input("passing_mark.$sid");
            if ($full !== null && $full !== '' && $pass !== null && $pass !== '' && (int)$pass > (int)$full) {
                return back()->withErrors([
                    "passing_mark.$sid" => "Passing mark for {$s->name} cannot exceed Full mark.",
                ])->withInput();
            }

            $st = $request->input("start_time.$sid");
            $et = $request->input("end_time.$sid");
            if ($st !== null && $st !== '' && $et !== null && $et !== '') {
                $stH = Carbon::parse($st);
                $etH = Carbon::parse($et);
                if ($etH->lessThanOrEqualTo($stH)) {
                    return back()->withErrors([
                        "end_time.$sid" => "End time for {$s->name} must be after start time.",
                    ])->withInput();
                }
            }
        }

        DB::transaction(function () use ($request, $subjects, $examId, $classId, $toTime, $toDate) {
            foreach ($subjects as $s) {
                $sid   = (int) $s->id;

                $date  = $request->input("exam_date.$sid");
                $start = $request->input("start_time.$sid");
                $end   = $request->input("end_time.$sid");
                $room  = $request->input("room_number.$sid");
                $full  = $request->input("full_mark.$sid");
                $pass  = $request->input("passing_mark.$sid");

                $allEmpty = ($date === null || $date === '')
                    && ($start === null || $start === '')
                    && ($end === null || $end === '')
                    && ($room === null || $room === '')
                    && ($full === null || $full === '')
                    && ($pass === null || $pass === '');

                $keys = ['exam_id' => $examId, 'class_id' => $classId, 'subject_id' => $sid];

                if ($allEmpty) {
                    if ($row = ExamSchedule::withTrashed()->where($keys)->first()) {
                        $row->delete(); // soft delete
                    }
                    continue;
                }

                // Find existing (including trashed); restore if needed, then update
                $row = ExamSchedule::withTrashed()->firstOrNew($keys);
                if ($row->trashed()) {
                    $row->restore();
                }

                $row->exam_date    = $toDate($date);
                $row->start_time   = $toTime($start);
                $row->end_time     = $toTime($end);
                $row->room_number  = ($room === '' ? null : trim($room));
                $row->full_mark    = ($full === '' ? null : (int)$full);
                $row->passing_mark = ($pass === '' ? null : (int)$pass);

                if (! $row->exists || ! $row->created_by) {
                    $row->created_by = Auth::id();
                }

                $row->save();
            }
        });

        return redirect()->route('admin.exam-schedule.list', [
            'exam_id'  => $examId,
            'class_id' => $classId,
        ])->with('success', 'Exam schedule saved.');
    }

    /* ------------------------------------------------------------
     * Student view
     * ------------------------------------------------------------ */
    public function studentExamTimetable(Request $request)
    {
        $student = Auth::user();
        abort_unless($student && $student->role === 'student', 403);

        if (! $student->class_id) {
            return view('student.my_exam_timetable', [
                'header_title'     => 'My Exam Timetable',
                'exams'            => collect(),
                'selectedExamId'   => null,
                'selectedExam'     => null,
                'rows'             => collect(),
                'studentClassName' => null,
            ])->with('info', 'You are not assigned to any class yet.');
        }

        $exams = Exam::whereIn('id', function ($q) use ($student) {
                $q->from('exam_schedules as es')
                  ->join('class_subjects as cs', function ($j) use ($student) {
                      $j->on('cs.subject_id', '=', 'es.subject_id')
                        ->where('cs.class_id', $student->class_id)
                        ->where('cs.status', 1)
                        ->whereNull('cs.deleted_at');
                  })
                  ->select('es.exam_id')
                  ->where('es.class_id', $student->class_id)
                  ->whereNull('es.deleted_at')
                  ->groupBy('es.exam_id');
            })
            ->orderBy('name')
            ->get(['id','name']);

        $requestedExamId = $request->get('exam_id');
        $selectedExamId  = $requestedExamId !== null ? (int) $requestedExamId : ($exams->first()->id ?? 0);
        if (! $exams->firstWhere('id', $selectedExamId)) {
            $selectedExamId = $exams->first()->id ?? null;
        }
        $selectedExam = $exams->firstWhere('id', $selectedExamId);

        $rows = collect();
        if ($selectedExam) {
            $rows = ExamSchedule::with('subject:id,name')
                ->join('class_subjects as cs', function ($j) use ($student) {
                    $j->on('cs.subject_id', '=', 'exam_schedules.subject_id')
                      ->where('cs.class_id', $student->class_id)
                      ->where('cs.status', 1)
                      ->whereNull('cs.deleted_at');
                })
                ->where('exam_schedules.class_id', $student->class_id)
                ->where('exam_schedules.exam_id',  $selectedExam->id)
                ->whereNull('exam_schedules.deleted_at')
                ->select('exam_schedules.*')
                ->orderBy('exam_schedules.exam_date')
                ->orderBy('exam_schedules.start_time')
                ->get();
        }

        $studentClassName = optional($student->class)->name ?? null;

        return view('student.my_exam_timetable', [
            'header_title'     => 'My Exam Timetable',
            'exams'            => $exams,
            'selectedExamId'   => $selectedExam?->id,
            'selectedExam'     => $selectedExam,
            'rows'             => $rows,
            'studentClassName' => $studentClassName,
        ]);
    }

    /* ------------------------------------------------------------
     * Teacher view
     * ------------------------------------------------------------ */
    public function teacherExamTimetable(Request $request)
    {
        $teacher = Auth::user();
        abort_unless($teacher && $teacher->role === 'teacher', 403);

        // All classes this teacher is assigned to
        $classes = AssignClassTeacherModel::classesForTeacher($teacher->id);
        $selectedClassId = (int) $request->get('class_id');

        // Exams list depends on chosen class
        $exams = collect();
        if ($selectedClassId) {
            // Optional: ensure the class is actually one of the teacher’s
            abort_unless($classes->pluck('id')->contains($selectedClassId), 403, 'Not your class.');

            $exams = Exam::whereIn('id', function ($q) use ($selectedClassId) {
                    $q->from('exam_schedules as es')
                      ->join('class_subjects as cs', function ($j) use ($selectedClassId) {
                          $j->on('cs.subject_id', '=', 'es.subject_id')
                            ->where('cs.class_id', $selectedClassId)
                            ->where('cs.status', 1)
                            ->whereNull('cs.deleted_at');
                      })
                      ->select('es.exam_id')
                      ->where('es.class_id', $selectedClassId)
                      ->whereNull('es.deleted_at')
                      ->groupBy('es.exam_id');
                })
                ->orderBy('name')
                ->get(['id','name']);
        }

        $requestedExamId = $request->get('exam_id');
        $selectedExamId  = $requestedExamId !== null
            ? (int) $requestedExamId
            : ($exams->count() === 1 ? $exams->first()->id : null);

        if ($selectedExamId && ! $exams->firstWhere('id', $selectedExamId)) {
            $selectedExamId = null;
        }
        $selectedExam = $selectedExamId ? $exams->firstWhere('id', $selectedExamId) : null;

        $rows = collect();
        if ($selectedClassId && $selectedExam) {
            $rows = ExamSchedule::with('subject:id,name')
                ->join('class_subjects as cs', function ($j) use ($selectedClassId) {
                    $j->on('cs.subject_id', '=', 'exam_schedules.subject_id')
                      ->where('cs.class_id', $selectedClassId)
                      ->where('cs.status', 1)
                      ->whereNull('cs.deleted_at');
                })
                ->where('exam_schedules.class_id', $selectedClassId)
                ->where('exam_schedules.exam_id',  $selectedExam->id)
                ->whereNull('exam_schedules.deleted_at')
                ->select('exam_schedules.*')
                ->orderBy('exam_schedules.exam_date')
                ->orderBy('exam_schedules.start_time')
                ->get();
        }

        return view('teacher.my_exam_timetable', [
            'header_title'    => 'My Exam Schedule',
            'classes'         => $classes,
            'exams'           => $exams,
            'selectedClassId' => $selectedClassId ?: null,
            'selectedExamId'  => $selectedExam?->id,
            'selectedExam'    => $selectedExam,
            'rows'            => $rows,
        ]);
    }

    /** AJAX: Exams available for a class (only those with schedules and ACTIVE subjects) */
    public function examsForClass(int $classId)
    {
        $teacher = Auth::user();
        abort_unless($teacher && $teacher->role === 'teacher', 403);

        // Ensure teacher owns the class
        $allowed = AssignClassTeacherModel::classesForTeacher($teacher->id)->pluck('id');
        abort_unless($allowed->contains($classId), 403, 'Not your class.');

        $exams = Exam::whereIn('id', function ($q) use ($classId) {
                $q->from('exam_schedules as es')
                  ->join('class_subjects as cs', function ($j) use ($classId) {
                      $j->on('cs.subject_id', '=', 'es.subject_id')
                        ->where('cs.class_id', $classId)
                        ->where('cs.status', 1)
                        ->whereNull('cs.deleted_at');
                  })
                  ->select('es.exam_id')
                  ->where('es.class_id', $classId)
                  ->whereNull('es.deleted_at')
                  ->groupBy('es.exam_id');
            })
            ->orderBy('name')
            ->get(['id','name']);

        return response()->json($exams);
    }

    /* ------------------------------------------------------------
     * Parent view
     * ------------------------------------------------------------ */
    public function parentExamTimetable(Request $request)
    {
        $parent = Auth::user();
        abort_unless($parent && $parent->role === 'parent', 403);

        // Children of this parent (same-school thanks to SchoolScope on User)
        $students = User::select('id','name','last_name','class_id')
            ->where('role', 'student')
            ->where('parent_id', $parent->id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $selectedStudentId = null;
        if ($students->count() === 1) {
            $selectedStudentId = $students->first()->id;
        } elseif ($request->filled('student_id')) {
            $candidate = (int) $request->get('student_id');
            if ($students->firstWhere('id', $candidate)) {
                $selectedStudentId = $candidate;
            }
        }
        $selectedStudent = $selectedStudentId ? $students->firstWhere('id', $selectedStudentId) : null;

        $exams = collect();
        $class = null;

        if ($selectedStudent && $selectedStudent->class_id) {
            $class = ClassModel::select('id','name')->find($selectedStudent->class_id);

            $exams = Exam::whereIn('id', function ($q) use ($selectedStudent) {
                    $q->from('exam_schedules as es')
                      ->join('class_subjects as cs', function ($j) use ($selectedStudent) {
                          $j->on('cs.subject_id', '=', 'es.subject_id')
                            ->where('cs.class_id', $selectedStudent->class_id)
                            ->where('cs.status', 1)
                            ->whereNull('cs.deleted_at');
                      })
                      ->select('es.exam_id')
                      ->where('es.class_id', $selectedStudent->class_id)
                      ->whereNull('es.deleted_at')
                      ->groupBy('es.exam_id');
                })
                ->orderBy('name')
                ->get(['id','name']);
        }

        $requestedExamId = $request->get('exam_id');
        $selectedExamId  = $requestedExamId !== null
            ? (int) $requestedExamId
            : ($exams->count() === 1 ? $exams->first()->id : null);

        if ($selectedExamId && ! $exams->firstWhere('id', $selectedExamId)) {
            $selectedExamId = null;
        }
        $selectedExam = $selectedExamId ? $exams->firstWhere('id', $selectedExamId) : null;

        $rows = collect();
        if ($selectedStudent && $selectedStudent->class_id && $selectedExam) {
            $rows = ExamSchedule::with('subject:id,name')
                ->join('class_subjects as cs', function ($j) use ($selectedStudent) {
                    $j->on('cs.subject_id', '=', 'exam_schedules.subject_id')
                      ->where('cs.class_id', $selectedStudent->class_id)
                      ->where('cs.status', 1)
                      ->whereNull('cs.deleted_at');
                })
                ->where('exam_schedules.class_id', $selectedStudent->class_id)
                ->where('exam_schedules.exam_id',  $selectedExam->id)
                ->whereNull('exam_schedules.deleted_at')
                ->select('exam_schedules.*')
                ->orderBy('exam_schedules.exam_date')
                ->orderBy('exam_schedules.start_time')
                ->get();
        }

        return view('parent.my_exam_timetable', [
            'header_title'      => 'My Child’s Exam Schedule',
            'students'          => $students,
            'selectedStudentId' => $selectedStudentId,
            'selectedStudent'   => $selectedStudent,
            'class'             => $class,
            'exams'             => $exams,
            'selectedExamId'    => $selectedExam?->id,
            'selectedExam'      => $selectedExam,
            'rows'              => $rows,
        ]);
    }

    /** AJAX: Exams for a given student (parent must own that student). */
    public function examsForStudent(int $studentId)
    {
        $parent = Auth::user();
        abort_unless($parent && $parent->role === 'parent', 403);

        $student = User::select('id','class_id')
            ->where('id', $studentId)
            ->where('role','student')
            ->where('parent_id', $parent->id)
            ->whereNull('deleted_at')
            ->first();

        if (! $student || ! $student->class_id) {
            return response()->json([]);
        }

        $exams = Exam::whereIn('id', function ($q) use ($student) {
                $q->from('exam_schedules as es')
                  ->join('class_subjects as cs', function ($j) use ($student) {
                      $j->on('cs.subject_id', '=', 'es.subject_id')
                        ->where('cs.class_id', $student->class_id)
                        ->where('cs.status', 1)
                        ->whereNull('cs.deleted_at');
                  })
                  ->select('es.exam_id')
                  ->where('es.class_id', $student->class_id)
                  ->whereNull('es.deleted_at')
                  ->groupBy('es.exam_id');
            })
            ->orderBy('name')
            ->get(['id','name']);

        return response()->json($exams);
    }
}
