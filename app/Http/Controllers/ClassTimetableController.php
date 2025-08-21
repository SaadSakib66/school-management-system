<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassSubject;
use App\Models\ClassModel;
use App\Models\Week;
use App\Models\ClassTimetable;
use App\Models\AssignClassTeacherModel;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ClassTimetableController extends Controller
{
    public function list(Request $request)
    {
        $data['header_title'] = 'Class Timetable';
        $data['getClass']     = ClassModel::getClass();

        $selectedClassId   = (int) $request->get('class_id');
        $selectedSubjectId = (int) $request->get('subject_id');

        // Server-side fill of subjects when class chosen
        $data['getSubject'] = collect();
        if ($selectedClassId) {
            $data['getSubject'] = ClassSubject::subjectsForClass($selectedClassId); // or Subject::forClass($selectedClassId)->get();
        }

        $data['selectedClassId']   = $selectedClassId ?: null;
        $data['selectedSubjectId'] = $selectedSubjectId ?: null;

        $data['weeks'] = Week::orderBy('sort')->get();

        // Existing timetable rows keyed by week_id
        $data['existing'] = collect();
        if ($selectedClassId && $selectedSubjectId) {
            $data['existing'] = ClassTimetable::where('class_id',$selectedClassId)
                ->where('subject_id',$selectedSubjectId)
                ->get()->keyBy('week_id');
        }

        return view('admin.class_timetable.list', $data);
    }


    public function save(Request $request)
    {
        $request->validate([
            'class_id'   => ['required','exists:classes,id'],
            'subject_id' => ['required','exists:subjects,id'],
            'start_time' => ['array'],
            'end_time'   => ['array'],
            'room_number'=> ['array'],
        ]);

        $weeks = Week::pluck('id'); // [1..7]
        DB::transaction(function () use ($request, $weeks) {
            foreach ($weeks as $weekId) {
                $start = $request->input("start_time.$weekId");
                $end   = $request->input("end_time.$weekId");
                $room  = $request->input("room_number.$weekId");

                // If user left all empty: delete existing entry for that day
                if (empty($start) && empty($end) && empty($room)) {
                    ClassTimetable::where([
                        'class_id'   => $request->class_id,
                        'subject_id' => $request->subject_id,
                        'week_id'    => $weekId,
                    ])->delete();
                    continue;
                }

                ClassTimetable::updateOrCreate(
                    [
                        'class_id'   => $request->class_id,
                        'subject_id' => $request->subject_id,
                        'week_id'    => $weekId,
                    ],
                    [
                        'start_time' => $start ?: null,
                        'end_time'   => $end ?: null,
                        'room_number'=> $room ?: null,
                    ]
                );
            }
        });

        return redirect()
            ->route('admin.class-timetable.list', [
                'class_id'   => $request->class_id,
                'subject_id' => $request->subject_id,
            ])
            ->with('success', 'Timetable saved.');
    }

    // AJAX endpoint to get subjects for a class (only assigned ones)
    // AJAX: return subjects assigned to a class (status=1)
    public function subjectsForClass($classId)
    {
        return response()->json(
            ClassSubject::subjectsForClass((int)$classId)
        );
    }

    public function myTimetablelist()
    {
        $user = Auth::user();
        abort_unless($user->role === 'student', 403);

        $classId = $user->class_id;
        $class   = $classId ? \App\Models\ClassModel::select('id','name')->find($classId) : null;

        $data['header_title'] = 'My Class Timetable';
        $data['classId']      = $classId;
        $data['class']        = $class;
        $data['weeks']        = \App\Models\Week::orderBy('sort')->get();

        if (!$classId) {
            $data['byWeek'] = collect();
            return view('student.my_timetable', $data);
        }

        // ⬇️ Only include ACTIVE class_subjects rows (status = 1)
        $rows = \App\Models\ClassTimetable::with(['subject:id,name','week:id,name,sort'])
            ->join('class_subjects as cs', function ($j) use ($classId) {
                $j->on('cs.subject_id', '=', 'class_timetables.subject_id')
                ->where('cs.class_id', $classId)
                ->where('cs.status', 1)
                ->whereNull('cs.deleted_at');
            })
            ->where('class_timetables.class_id', $classId)
            ->select('class_timetables.*')
            ->distinct() // guard against accidental duplicate cs rows
            ->orderBy('class_timetables.week_id')
            ->orderBy('class_timetables.start_time')
            ->get();

        $data['byWeek'] = $rows->groupBy('week_id');

        return view('student.my_timetable', $data);
    }


    public function teacherTimetable(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->role === 'teacher', 403);

        $classes = AssignClassTeacherModel::classesForTeacher($user->id);
        $selectedClassId = (int) $request->get('class_id');

        $weeks = Week::orderBy('sort')->get();
        $byWeek = collect();

        if ($selectedClassId) {
            // ⬇️ Only include ACTIVE class_subjects rows for this class
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


    public function parentTimetable(Request $request)
    {
        $parent = Auth::user();
        abort_unless($parent->role === 'parent', 403);

        // Children of this parent
        $students = User::select('id','name','last_name','class_id')
            ->where('role', 'student')
            ->where('parent_id', $parent->id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        // Selection rule:
        // - If exactly ONE student -> auto-select that one
        // - If multiple -> only select when explicitly provided in query
        // - If none -> nothing selected
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

        $class = null;
        $weeks = Week::orderBy('sort')->get();
        $byWeek = collect();

        if ($selectedStudent && $selectedStudent->class_id) {
            $classId = $selectedStudent->class_id;
            $class   = ClassModel::select('id','name')->find($classId);

            // Only ACTIVE subjects for that class
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
