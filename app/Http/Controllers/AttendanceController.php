<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\User;
use App\Models\AssignClassTeacherModel;

class AttendanceController extends Controller
{
    public function studentAttendance(Request $request)
    {
        $data['header_title'] = "Student Attendance";

        // Class list
        $data['classes'] = ClassModel::getClass(); // assumes your helper returns id,name

        // Filters
        $selectedClassId = $request->integer('class_id') ?: null;

        // Date (max today)
        $today = Carbon::today()->format('Y-m-d');
        $selectedDate = $request->get('attendance_date');
        if ($selectedDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = null;
        }
        // you can default to today or leave blank; screenshot shows a picked date
        $data['selectedClassId'] = $selectedClassId;
        $data['selectedDate']    = $selectedDate;

        // Students + existing attendance (only when both chosen)
        $students = collect();
        $existing = collect();
        if ($selectedClassId && $selectedDate) {
            $students = User::where('role', 'student')
                ->where('class_id', $selectedClassId)
                ->orderBy('roll_number') // or ->orderByRaw('CAST(roll_number AS UNSIGNED)') if numeric strings
                ->get(['id','name','last_name','roll_number']);

            $existing = Attendance::where('class_id', $selectedClassId)
                ->whereDate('attendance_date', $selectedDate)
                ->get()
                ->keyBy('student_id');
        }

        $data['students'] = $students;
        $data['existing'] = $existing;
        $data['today']    = $today;

        return view('admin.attendance.list', $data);
    }

    public function saveStudentAttendance(Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');

        $request->validate([
            'class_id'        => ['required','exists:classes,id'],
            'attendance_date' => ['required','date','before_or_equal:'.$today],
            'attendance'      => ['array'], // attendance[student_id] = 1|2|3|4
        ]);

        $classId   = (int) $request->class_id;
        $date      = $request->attendance_date;
        $rows      = $request->input('attendance', []);

        // Ensure we only process actual students from the class
        $allowedStudentIds = User::where('role','student')
            ->where('class_id', $classId)
            ->pluck('id')
            ->toArray();

        foreach ($rows as $studentId => $type) {
            $studentId = (int) $studentId;
            if (!in_array($studentId, $allowedStudentIds, true)) {
                continue;
            }
            $type = (int) $type;
            if (!in_array($type, [1,2,3,4], true)) {
                continue;
            }

            Attendance::updateOrCreate(
                [
                    'class_id'        => $classId,
                    'attendance_date' => $date,
                    'student_id'      => $studentId,
                ],
                [
                    'attendance_type' => $type,
                    'created_by'      => Auth::id(),
                ]
            );
        }

        return redirect()->route('admin.student-attendance.view', [
            'class_id'        => $classId,
            'attendance_date' => $date,
        ])->with('success', 'Attendance saved successfully.');
    }


    public function teacherAttendance(Request $request)
    {
        $teacher = Auth::user();
        abort_unless($teacher && $teacher->role === 'teacher', 403);

        $data['header_title'] = "Student Attendance";

        // classes actively assigned to this teacher
        $classes = AssignClassTeacherModel::query()
            ->join('classes', 'classes.id', '=', 'assign_class_teacher.class_id')
            ->where('assign_class_teacher.teacher_id', $teacher->id)
            ->where('assign_class_teacher.status', 1)
            ->whereNull('assign_class_teacher.deleted_at')
            ->orderBy('classes.name')
            ->get(['classes.id', 'classes.name']);

        $data['classes'] = $classes;

        $selectedClassId = $request->integer('class_id') ?: null;

        // date (max today)
        $today = Carbon::today()->format('Y-m-d');
        $selectedDate = $request->get('attendance_date');
        if ($selectedDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = null;
        }

        $data['selectedClassId'] = $selectedClassId;
        $data['selectedDate']    = $selectedDate;
        $data['today']           = $today;

        $students = collect();
        $existing = collect();

        if ($selectedClassId && $selectedDate) {
            // ensure the chosen class is this teacher's active class
            abort_unless($classes->pluck('id')->contains($selectedClassId), 403, 'Not your class.');

            $students = User::where('role', 'student')
                ->where('class_id', $selectedClassId)
                ->orderBy('roll_number') // show by roll
                ->get(['id','name','last_name','roll_number']);

            $existing = Attendance::where('class_id', $selectedClassId)
                ->whereDate('attendance_date', $selectedDate)
                ->get()
                ->keyBy('student_id');
        }

        return view('teacher.student_attendance', $data + compact('students','existing'));
    }

    public function teacherAttendanceSave(Request $request)
    {
        $teacher = Auth::user();
        abort_unless($teacher && $teacher->role === 'teacher', 403);

        $today = Carbon::today()->format('Y-m-d');

        $request->validate([
            'class_id'        => ['required','integer'],
            'attendance_date' => ['required','date','before_or_equal:'.$today],
            'attendance'      => ['array'],
        ]);

        $classId = (int) $request->class_id;

        // verify class belongs to teacher (active assignment)
        $allowed = AssignClassTeacherModel::where('teacher_id', $teacher->id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->pluck('class_id');
        abort_unless($allowed->contains($classId), 403, 'Not your class.');

        $date = $request->attendance_date;
        $rows = $request->input('attendance', []);

        $allowedStudentIds = User::where('role','student')
            ->where('class_id', $classId)
            ->pluck('id')
            ->toArray();

        foreach ($rows as $studentId => $type) {
            $studentId = (int) $studentId;
            if (!in_array($studentId, $allowedStudentIds, true)) continue;

            $type = (int) $type;
            if (!in_array($type, [1,2,3,4], true)) continue;

            Attendance::updateOrCreate(
                ['class_id'=>$classId,'attendance_date'=>$date,'student_id'=>$studentId],
                ['attendance_type'=>$type,'created_by'=>$teacher->id]
            );
        }

        return redirect()->route('teacher.student-attendance.view', [
            'class_id'        => $classId,
            'attendance_date' => $date,
        ])->with('success', 'Attendance saved successfully.');
    }

    public function studentMonthlyAttendance(Request $request)
    {
        $student = Auth::user();
        abort_unless($student && $student->role === 'student', 403);

        // Resolve month/year from selects (m=1..12, y=YYYY) or fallback to ?month=YYYY-MM or current
        $now = Carbon::now();
        $selMonth = (int) $request->integer('m') ?: null;
        $selYear  = (int) $request->integer('y') ?: null;

        if (!$selMonth || !$selYear) {
            $raw = $request->get('month');
            if ($raw && preg_match('/^\d{4}-\d{2}$/', $raw)) {
                $dt = Carbon::createFromFormat('Y-m', $raw)->startOfMonth();
            } else {
                $dt = $now->copy()->startOfMonth();
            }
            $selMonth = (int) $dt->month;
            $selYear  = (int) $dt->year;
        }

        $start = Carbon::createFromDate($selYear, $selMonth, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        $monthLabel = $start->format('F - Y'); // "January - 2025"

        // Attendance for this student in month
        $rows = Attendance::where('student_id', $student->id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->attendance_date)->toDateString());

        $labels = [1=>'Present', 2=>'Late', 3=>'Half Day', 4=>'Absent'];
        $badges = [1=>'bg-success', 2=>'bg-warning', 3=>'bg-info', 4=>'bg-danger'];

        $days = [];
        $count = ['present'=>0,'late'=>0,'halfday'=>0,'absent'=>0];

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $key = $cursor->toDateString();
            $rec = $rows->get($key);

            $code = $rec->attendance_type ?? null;
            $text = $code ? ($labels[$code] ?? '-') : null;
            $badge= $code ? ($badges[$code] ?? 'bg-secondary') : 'bg-secondary';

            if ($code === 1) $count['present']++;
            if ($code === 2) $count['late']++;
            if ($code === 3) $count['halfday']++;
            if ($code === 4) $count['absent']++;

            $days[] = [
                'date_iso' => $key,
                'date_fmt' => $cursor->format('d-m-Y'),
                'dow'      => $cursor->format('D'),
                'code'     => $code,
                'text'     => $text,
                'badge'    => $badge,
            ];
        }

        // Year dropdown range (adjust as you like)
        $years = range($now->year - 5, $now->year + 1);

        return view('student.attendance', [
            'header_title' => 'My Attendance',
            'selMonth'     => $selMonth,
            'selYear'      => $selYear,
            'years'        => $years,
            'monthLabel'   => $monthLabel,
            'days'         => $days,
            'count'        => $count,
        ]);
    }


    public function parentMonthlyAttendance(Request $request)
    {
        $parent = Auth::user();
        abort_unless($parent && $parent->role === 'parent', 403);

        $children = $this->childrenForParent($parent->id);
        $selectedStudentId = $request->integer('student_id')
            ?: ($children->count() === 1 ? (int)$children->first()->id : null);

        // Month/year from selects or fallback
        $now = Carbon::now();
        $selMonth = (int) $request->integer('m') ?: null;
        $selYear  = (int) $request->integer('y') ?: null;

        if (!$selMonth || !$selYear) {
            $raw = $request->get('month');
            if ($raw && preg_match('/^\d{4}-\d{2}$/', $raw)) {
                $dt = Carbon::createFromFormat('Y-m', $raw)->startOfMonth();
            } else {
                $dt = $now->copy()->startOfMonth();
            }
            $selMonth = (int) $dt->month;
            $selYear  = (int) $dt->year;
        }

        $start = Carbon::createFromDate($selYear, $selMonth, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        $monthLabel = $start->format('F - Y');

        $days  = [];
        $count = ['present'=>0,'late'=>0,'halfday'=>0,'absent'=>0];

        if ($selectedStudentId) {
            abort_unless($children->pluck('id')->contains($selectedStudentId), 403, 'This student is not assigned to you.');

            $rows = Attendance::where('student_id', $selectedStudentId)
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->keyBy(fn($r) => Carbon::parse($r->attendance_date)->toDateString());

            $labels = [1=>'Present', 2=>'Late', 3=>'Half Day', 4=>'Absent'];
            $badges = [1=>'bg-success', 2=>'bg-warning', 3=>'bg-info', 4=>'bg-danger'];

            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $key = $cursor->toDateString();
                $rec = $rows->get($key);

                $code = $rec->attendance_type ?? null;
                $text = $code ? ($labels[$code] ?? '-') : null;
                $badge= $code ? ($badges[$code] ?? 'bg-secondary') : 'bg-secondary';

                if ($code === 1) $count['present']++;
                if ($code === 2) $count['late']++;
                if ($code === 3) $count['halfday']++;
                if ($code === 4) $count['absent']++;

                $days[] = [
                    'date_iso' => $key,
                    'date_fmt' => $cursor->format('d-m-Y'),
                    'dow'      => $cursor->format('D'),
                    'code'     => $code,
                    'text'     => $text,
                    'badge'    => $badge,
                ];
            }
        }

        $years = range($now->year - 5, $now->year + 1);

        return view('parent.attendance', [
            'header_title'      => 'Child Attendance',
            'children'          => $children,
            'selectedStudentId' => $selectedStudentId,
            'selMonth'          => $selMonth,
            'selYear'           => $selYear,
            'years'             => $years,
            'monthLabel'        => $monthLabel,
            'days'              => $days,
            'count'             => $count,
        ]);
    }


    /**
     * Children helper (pivot `parent_students` or users.parent_id fallback)
     */
    private function childrenForParent(int $parentId)
    {
        if (Schema::hasTable('parent_students')) {
            return User::select('users.id','users.name','users.last_name','users.class_id','users.roll_number')
                ->join('parent_students as ps', 'ps.student_id', '=', 'users.id')
                ->where('ps.parent_id', $parentId)
                ->where('users.role', 'student')
                ->whereNull('users.deleted_at')
                ->orderBy('users.name')
                ->get();
        }

        if (Schema::hasColumn('users', 'parent_id')) {
            return User::where('role','student')
                ->where('parent_id', $parentId)
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id','name','last_name','class_id','roll_number']);
        }

        return collect();
    }












}
