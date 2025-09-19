<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Models\ClassSubject;
use App\Models\ClassModel;
use App\Models\Week;
use App\Models\ClassTimetable;
use App\Models\AssignClassTeacherModel;
use App\Models\User;
use App\Models\Subject;

class ClassTimetableController extends Controller
{
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
            // 404 if class not visible in this school
            $class = ClassModel::findOrFail($selectedClassId);
            $data['getSubject'] = ClassSubject::subjectsForClass($class->id);
        }

        $data['selectedClassId']   = $selectedClassId ?: null;
        $data['selectedSubjectId'] = $selectedSubjectId ?: null;
        $data['weeks']             = Week::orderBy('sort')->get(['id','name','sort']);

        // Existing rows keyed by week_id (only when both selected & visible)
        $data['existing'] = collect();
        if ($selectedClassId && $selectedSubjectId) {
            // Ensure both belong to current school scope (Subject::findOrFail is scoped)
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
            'class_id'   => ['required','integer'],
            'subject_id' => ['required','integer'],
            'start_time' => ['array'],
            'end_time'   => ['array'],
            'room_number'=> ['array'],
        ]);

        // Ensure these IDs are visible under current school scope
        $class   = ClassModel::findOrFail((int) $request->class_id);
        $subject = Subject::findOrFail((int) $request->subject_id);

        // Optional: ensure subject is actually assigned to class (active)
        $isAssigned = ClassSubject::where('class_id', $class->id)
            ->where('subject_id', $subject->id)
            ->where('status', 1)
            ->exists();
        if (! $isAssigned) {
            return back()->with('error', 'Selected subject is not assigned to this class.')->withInput();
        }

        $weeks = Week::pluck('id'); // [1..7]

        DB::transaction(function () use ($request, $weeks, $class, $subject) {
            foreach ($weeks as $weekId) {
                $start = trim((string) ($request->input("start_time.$weekId") ?? ''));
                $end   = trim((string) ($request->input("end_time.$weekId")   ?? ''));
                $room  = trim((string) ($request->input("room_number.$weekId") ?? ''));

                // If every field is empty, delete existing entry for that day (scoped)
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
                        'start_time' => $start !== '' ? $start : null,
                        'end_time'   => $end   !== '' ? $end   : null,
                        'room_number'=> $room  !== '' ? $room  : null,
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
     * AJAX: subjects assigned to a class (active only).
     */
    public function subjectsForClass($classId)
    {
        $class = ClassModel::findOrFail((int) $classId); // 404 if not in this school
        return response()->json(
            ClassSubject::subjectsForClass($class->id)
        );
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

        $classes = AssignClassTeacherModel::classesForTeacher($user->id); // school-scoped helper
        $selectedClassId = (int) $request->get('class_id');

        $weeks  = Week::orderBy('sort')->get(['id','name','sort']);
        $byWeek = collect();

        if ($selectedClassId) {
            // Ensure teacher actually has this class
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
     * Parent: view a selected child’s timetable.
     */
    public function parentTimetable(Request $request)
    {
        $parent = Auth::user();
        abort_unless($parent && $parent->role === 'parent', 403);

        // Children (school-scoped automatically if you fetch via User)
        $students = User::select('id','name','last_name','class_id')
            ->where('role', 'student')
            ->where('parent_id', $parent->id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        // Auto-select logic
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

        $class = null;
        $weeks = Week::orderBy('sort')->get(['id','name','sort']);
        $byWeek = collect();

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
}
