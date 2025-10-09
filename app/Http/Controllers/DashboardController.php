<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

use App\Models\School;
use App\Models\User;
use App\Models\Attendance;

// Adjust these model imports to your actual names if different:
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\ClassSubject;
use App\Models\ExamSchedule;
use App\Models\Homework;
use App\Models\AssignClassTeacherModel;

use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        switch ($user->role) {
            case 'super_admin': {
                $schoolId = session('current_school_id');
                if ($schoolId) {
                    $school = School::find($schoolId);
                    if (!$school) {
                        return redirect()->route('superadmin.dashboard')
                            ->with('error', 'Selected school not found.');
                    }

                    $stats = $this->schoolStats($school->id);
                    $recentSchools = School::latest()->take(8)->get();

                    return view('superadmin.dashboard', compact('stats', 'recentSchools'));
                }

                return redirect()
                    ->route('superadmin.dashboard')
                    ->with('error', 'Please select a school first.');
            }

            case 'admin': {
                $schoolId = session('current_school_id') ?: ($user->school_id ?? null);
                abort_if(!$schoolId, 403, 'No school context for admin.');
                $school = School::findOrFail($schoolId);

                $stats       = $this->schoolStats($school->id);
                $extras      = $this->adminExtras($school->id);
                $recentUsers = User::where('school_id', $school->id)
                                    ->orderByRaw('last_login_at IS NULL')
                                    ->orderByDesc('last_login_at')
                                    ->get();

                return view('admin.dashboard', compact('stats', 'school', 'recentUsers', 'extras'));
            }

            case 'teacher': {
                $schoolId = $user->school_id;
                abort_if(!$schoolId, 403, 'No school assigned.');
                $school = School::findOrFail($schoolId);

                $stats       = $this->schoolStats($school->id);
                $extras      = $this->teacherExtras($school->id, $user->id);
                $recentUsers = User::where('school_id', $school->id)
                    ->whereIn('role', ['student','parent'])
                    ->latest()->take(8)->get();

                return view('teacher.dashboard', compact('stats', 'school', 'recentUsers', 'extras'));
            }

            case 'student': {
                // Slim student dashboard
                $schoolId = $user->school_id;
                abort_if(!$schoolId, 403, 'No school assigned.');
                $school   = School::findOrFail($schoolId);

                $overview = $this->studentOverview($school->id, $user);

                return view('student.dashboard', [
                    'school'   => $school,
                    'overview' => $overview,
                ]);
            }

            case 'parent': {
                // Reworked parent dashboard only (others unchanged)
                $schoolId = $user->school_id;
                abort_if(!$schoolId, 403, 'No school assigned.');
                $school = School::findOrFail($schoolId);

                $overview = $this->parentOverview($school->id, $user);

                $stats = [
                    'header_title' => ($school->short_name ?: $school->name) . ' — Parent Dashboard',
                ];

                return view('parent.dashboard', [
                    'school'   => $school,
                    'overview' => $overview,
                    'stats'    => $stats,
                ]);
            }

            default:
                abort(403, 'Unauthorized role.');
        }
    }

    private function schoolStats(int $schoolId): array
    {
        $school = School::findOrFail($schoolId);
        $usersQuery = User::where('school_id', $schoolId);

        $classesCount  = class_exists(ClassModel::class)
            ? ClassModel::where('school_id', $schoolId)->count() : 0;

        $subjectsCount = class_exists(Subject::class)
            ? Subject::where('school_id', $schoolId)->count() : 0;

        $homeworkCount = class_exists(Homework::class)
            ? Homework::where('school_id', $schoolId)->count() : 0;

        $upcomingExams = 0;
        if (class_exists(ExamSchedule::class) && Schema::hasTable('exam_schedules')) {
            $dateCol = $this->resolveColumn('exam_schedules', [
                'exam_date', 'date', 'scheduled_for', 'starts_at', 'start_date'
            ]);

            if ($dateCol) {
                $now  = Carbon::now()->startOfDay();
                $in30 = Carbon::now()->addDays(30)->endOfDay();

                $upcomingExams = ExamSchedule::where('school_id', $schoolId)
                    ->whereBetween($dateCol, [$now, $in30])
                    ->count();
            }
        }

        $attendanceToday = 0;
        if (class_exists(Attendance::class) && Schema::hasTable('attendances')) {
            $attendanceToday = Attendance::where('school_id', $schoolId)
                ->onDate(Carbon::today())
                ->ofType(Attendance::TYPE_PRESENT)
                ->count();
        }

        return [
            'header_title'     => $school->short_name
                                  ? ($school->short_name . ' — Dashboard')
                                  : ($school->name . ' — Dashboard'),
            'schools_total'    => 1,
            'schools_active'   => (int)($school->status == 1),
            'schools_inactive' => (int)($school->status == 0),
            'users_total' => (clone $usersQuery)->count(),
            'admins'      => (clone $usersQuery)->where('role', 'admin')->count(),
            'teachers'    => (clone $usersQuery)->where('role', 'teacher')->count(),
            'students'    => (clone $usersQuery)->where('role', 'student')->count(),
            'parents'     => (clone $usersQuery)->where('role', 'parent')->count(),
            'classes_total'     => $classesCount,
            'subjects_total'    => $subjectsCount,
            'homeworks_total'   => $homeworkCount,
            'exams_upcoming_30' => $upcomingExams,
            'attendance_today'  => $attendanceToday,
            'school'            => $school,
        ];
    }

    private function adminExtras(int $schoolId): array
    {
        return [
            'quick_links' => array_filter([
                ['label' => 'Manage Classes', 'route' => route_exists('admin.class.list') ? route('admin.class.list') : '#'],
                ['label' => 'Create Exam',    'route' => route_exists('admin.exam.add')   ? route('admin.exam.add')   : '#'],
            ]),
        ];
    }

    private function teacherExtras(int $schoolId, int $userId): array
    {
        $activeClassIds = collect();
        $assignedClasses = 0;

        if (class_exists(AssignClassTeacherModel::class) && Schema::hasTable('assign_class_teacher')) {
            $activeClassIds = AssignClassTeacherModel::query()
                ->where('school_id', $schoolId)
                ->where('teacher_id', $userId)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('class_id');

            $assignedClasses = $activeClassIds->unique()->count();
        }

        $pendingHomework = 0;
        if (class_exists(Homework::class) && Schema::hasTable('homeworks')) {
            $q = Homework::query()
                ->where('school_id', $schoolId)
                ->when(method_exists(Homework::class, 'scopeCreatedBy'), fn($qq) => $qq->createdBy($userId), fn($qq) => $qq->where('created_by', $userId))
                ->when($activeClassIds->isNotEmpty() && Schema::hasColumn('homeworks','class_id'),
                    fn($qq) => $qq->whereIn('class_id', $activeClassIds)
                );

            if (method_exists(Homework::class, 'scopePending')) {
                $q->pending();
            } else {
                $dueCol = $this->resolveColumn('homeworks', ['submission_date','due_date','deadline','end_date']);
                if ($dueCol) {
                    $q->where(function($w) use ($dueCol) {
                        $w->whereNull($dueCol)->orWhereDate($dueCol, '>=', Carbon::today());
                    });
                }
            }

            $pendingHomework = $q->count();
        }

        $todayMarked = 0;
        if (class_exists(Attendance::class) && Schema::hasTable('attendances')) {
            $todayMarked = Attendance::where('school_id', $schoolId)
                ->where('created_by', $userId)
                ->onDate(Carbon::today())
                ->count();
        }

        return compact('assignedClasses', 'pendingHomework', 'todayMarked');
    }

    /**
     * Student-focused overview with your real route names wired.
     */
    private function studentOverview(int $schoolId, User $user): array
    {
        $usersTable = (new User)->getTable();
        $classId    = null;
        $sectionId  = null;

        if (\Illuminate\Support\Facades\Schema::hasColumn($usersTable, 'class_id'))   $classId   = $user->class_id;
        if (\Illuminate\Support\Facades\Schema::hasColumn($usersTable, 'section_id')) $sectionId = $user->section_id;

        // Attendance rate
        $attendanceRate = null;
        $presentDays = 0;
        $totalDays   = 0;

        if (class_exists(\App\Models\Attendance::class) && \Illuminate\Support\Facades\Schema::hasTable('attendances')) {
            $totalDays = \App\Models\Attendance::where('attendances.school_id', $schoolId)
                ->where('attendances.student_id', $user->id)
                ->count();

            $presentDays = \App\Models\Attendance::where('attendances.school_id', $schoolId)
                ->where('attendances.student_id', $user->id)
                ->ofType(\App\Models\Attendance::TYPE_PRESENT)
                ->count();

            $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : null;
        }

        // Subjects count
        $subjectsCount = 0;
        if (class_exists(\App\Models\ClassSubject::class) && \Illuminate\Support\Facades\Schema::hasTable('class_subjects')) {
            $subjectsQuery = \App\Models\ClassSubject::where('class_subjects.school_id', $schoolId);
            if ($classId && \Illuminate\Support\Facades\Schema::hasColumn('class_subjects','class_id')) {
                $subjectsQuery->where('class_subjects.class_id', $classId);
            }
            $subjectsCount = $subjectsQuery->count();
        } elseif (class_exists(\App\Models\Subject::class) && \Illuminate\Support\Facades\Schema::hasTable('subjects')) {
            $subjectsCount = \App\Models\Subject::where('subjects.school_id', $schoolId)->count();
        }

        // Upcoming exams (next 30 days)
        $upcomingExamsCount = 0;
        $examsList = collect();

        if (class_exists(\App\Models\ExamSchedule::class) && \Illuminate\Support\Facades\Schema::hasTable('exam_schedules')) {
            $dateCol = $this->resolveColumn('exam_schedules', ['exam_date','date','scheduled_for','starts_at','start_date']);
            if ($dateCol) {
                $now  = \Carbon\Carbon::now()->startOfDay();
                $in30 = \Carbon\Carbon::now()->addDays(30)->endOfDay();

                $examQ = \App\Models\ExamSchedule::query()
                    ->where('exam_schedules.school_id', $schoolId)
                    ->whereBetween("exam_schedules.$dateCol", [$now, $in30]);

                if ($classId && \Illuminate\Support\Facades\Schema::hasColumn('exam_schedules','class_id')) {
                    $examQ->where('exam_schedules.class_id', $classId);
                }
                if ($sectionId && \Illuminate\Support\Facades\Schema::hasColumn('exam_schedules','section_id')) {
                    $examQ->where('exam_schedules.section_id', $sectionId);
                }

                $upcomingExamsCount = (clone $examQ)->count();

                $titleCol   = $this->firstExistingColumn('exam_schedules', ['title','name','exam_name']);
                $subjectCol = $this->firstExistingColumn('exam_schedules', ['subject_name','subject']);

                $examsList = $examQ
                    ->orderBy("exam_schedules.$dateCol")
                    ->limit(5)
                    ->get()
                    ->map(function ($e) use ($dateCol, $titleCol, $subjectCol) {
                        return [
                            'title'   => $titleCol   ? ($e->{$titleCol} ?? 'Exam') : 'Exam',
                            'subject' => $subjectCol ? ($e->{$subjectCol} ?? null) : null,
                            'date'    => \Carbon\Carbon::parse($e->{$dateCol})->toDateString(),
                        ];
                    });
            }
        }

        // Homework due soon (next 7 days)
        $homeworkDueCount = 0;
        $homeworkList = collect();

        if (class_exists(\App\Models\Homework::class) && \Illuminate\Support\Facades\Schema::hasTable('homeworks')) {
            $dueCol   = $this->resolveColumn('homeworks', ['submission_date','due_date','deadline','end_date']);
            $titleCol = $this->firstExistingColumn('homeworks', ['title','name','topic']);

            $homeQ = \App\Models\Homework::query()
                ->from('homeworks')
                ->where('homeworks.school_id', $schoolId);

            if ($classId && \Illuminate\Support\Facades\Schema::hasColumn('homeworks','class_id')) {
                $homeQ->where('homeworks.class_id', $classId);
            }
            if ($sectionId && \Illuminate\Support\Facades\Schema::hasColumn('homeworks','section_id')) {
                $homeQ->where('homeworks.section_id', $sectionId);
            }

            if ($dueCol) {
                $homeQ->whereDate("homeworks.$dueCol", '>=', \Carbon\Carbon::today())
                    ->whereDate("homeworks.$dueCol", '<=', \Carbon\Carbon::today()->addDays(7));
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('subjects') && \Illuminate\Support\Facades\Schema::hasColumn('homeworks', 'subject_id')) {
                $subjectNameCol = $this->firstExistingColumn('subjects', ['name','subject_name','title']);
                if ($subjectNameCol) {
                    $homeQ->leftJoin('subjects as s', 's.id', '=', 'homeworks.subject_id')
                        ->addSelect('homeworks.*', \Illuminate\Support\Facades\DB::raw('s.' . $subjectNameCol . ' as _subject_name'));
                }
            }

            $homeworkDueCount = (clone $homeQ)->count('homeworks.id');

            $homeworkList = $homeQ->orderBy($dueCol ? "homeworks.$dueCol" : 'homeworks.id', 'asc')
                ->limit(5)
                ->get()
                ->map(function ($h) use ($dueCol, $titleCol) {
                    $title = $titleCol ? ($h->{$titleCol} ?? 'Homework') : 'Homework';
                    $subjectName = property_exists($h, '_subject_name') ? ($h->_subject_name ?: null) : null;

                    return [
                        'title'     => $title,
                        'subject'   => $subjectName,
                        'due_date'  => $dueCol ? optional(\Carbon\Carbon::parse($h->{$dueCol}))->toDateString() : null,
                    ];
                });
        }

        // Quick links
        $quickLinks = array_filter([
            ['label' => 'My Attendance',     'route' => route_exists('student.attendance.month')     ? route('student.attendance.month')     : '#'],
            ['label' => 'My Homework',       'route' => route_exists('student.homework.list')        ? route('student.homework.list')        : '#'],
            ['label' => 'My Timetable',      'route' => route_exists('student.my-timetable')         ? route('student.my-timetable')         : '#'],
            ['label' => 'Exam Timetable',    'route' => route_exists('student.my-exam-timetable')    ? route('student.my-exam-timetable')    : '#'],
            ['label' => 'Marks Register',    'route' => route_exists('student.marks-register.list')  ? route('student.marks-register.list')  : '#'],
            ['label' => 'Notice Board',      'route' => route_exists('student.notice-board')         ? route('student.notice-board')         : '#'],
            ['label' => 'Inbox',             'route' => route_exists('student.inbox')                ? route('student.inbox')                : '#'],
            ['label' => 'My Calendar',       'route' => route_exists('student.my-calendar')          ? route('student.my-calendar')          : '#'],
            ['label' => 'Exam Calendar',     'route' => route_exists('student.my-exam-calendar')     ? route('student.my-exam-calendar')     : '#'],
        ], fn($x) => $x['route'] !== '#');

        // My class title (optional)
        $myClass = null;
        if ($classId && class_exists(\App\Models\ClassModel::class) && \Illuminate\Support\Facades\Schema::hasTable('classes')) {
            $class = \App\Models\ClassModel::where('classes.school_id', $schoolId)->find($classId);
            if ($class) {
                $nameCol = $this->firstExistingColumn('classes', ['name','class_name','title']);
                $myClass = $nameCol ? ($class->{$nameCol} ?? null) : null;
            }
        }

        return [
            'attendance_rate'      => $attendanceRate,
            'present_days'         => $presentDays,
            'total_days'           => $totalDays,
            'subjects_count'       => $subjectsCount,
            'upcoming_exams_count' => $upcomingExamsCount,
            'homework_due_count'   => $homeworkDueCount,
            'exams'                => $examsList,
            'homeworks'            => $homeworkList,
            'quick_links'          => $quickLinks,
            'my_class'             => $myClass,
        ];
    }

    /* =========================
     * Parent-specific helpers
     * ========================= */

    private function parentOverview(int $schoolId, \App\Models\User $parent): array
    {
        // Build a safe select list (no aliasing to avoid SoftDeletes alias issue)
        $select = ['id', 'name'];
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'class_id')) {
            $select[] = 'class_id';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'section_id')) {
            $select[] = 'section_id';
        }

        $children = \App\Models\User::query()
            // NO alias here; keep base table name so SoftDeletes adds "users.deleted_at is null"
            ->where('school_id', $schoolId)
            ->where('role', 'student')
            ->where('parent_id', $parent->id)
            ->orderBy('name')
            ->get($select);

        $childrenSummaries = [];
        foreach ($children as $child) {
            $childrenSummaries[] = $this->studentSnapshotForParent($schoolId, $child);
        }

        return [
            'children_count' => $children->count(),
            'children'       => $childrenSummaries,
        ];
    }

    private function studentSnapshotForParent(int $schoolId, User $child): array
    {
        $classId   = $child->class_id ?? null;
        $sectionId = $child->section_id ?? null;

        // Class/Section names (optional)
        $className = null;
        if ($classId && class_exists(ClassModel::class) && Schema::hasTable('classes')) {
            $class = ClassModel::query()
                ->from('classes')
                ->where('classes.school_id', $schoolId)
                ->where('classes.id', $classId)
                ->first();
            if ($class) {
                $nameCol  = $this->firstExistingColumn('classes', ['name','class_name','title']);
                $className = $nameCol ? ($class->{$nameCol} ?? null) : null;
            }
        }

        // Attendance (simple lifetime %)
        $attendanceRate = null;
        $presentDays = 0;
        $totalDays   = 0;

        if (class_exists(Attendance::class) && Schema::hasTable('attendances')) {
            $totalDays = Attendance::query()
                ->from('attendances')
                ->where('attendances.school_id', $schoolId)
                ->where('attendances.student_id', $child->id)
                ->count();

            $presentQ = Attendance::query()
                ->from('attendances')
                ->where('attendances.school_id', $schoolId)
                ->where('attendances.student_id', $child->id);

            if (method_exists(Attendance::class, 'scopeOfType') && defined(Attendance::class.'::TYPE_PRESENT')) {
                $presentQ->ofType(Attendance::TYPE_PRESENT);
            } elseif (Schema::hasColumn('attendances', 'type')) {
                $presentQ->where('attendances.type', 'present');
            } elseif (Schema::hasColumn('attendances', 'is_present')) {
                $presentQ->where('attendances.is_present', 1);
            }

            $presentDays    = $presentQ->count();
            $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : null;
        }

        // Homework due next 7 days
        $homeworkDueCount = 0;
        $homeworks = collect();
        if (class_exists(Homework::class) && Schema::hasTable('homeworks')) {
            $dueCol   = $this->resolveColumn('homeworks', ['submission_date','due_date','deadline','end_date']);
            $titleCol = $this->firstExistingColumn('homeworks', ['title','name','topic']);

            $homeQ = Homework::query()
                ->from('homeworks')
                ->where('homeworks.school_id', $schoolId);

            if ($classId && Schema::hasColumn('homeworks','class_id')) {
                $homeQ->where('homeworks.class_id', $classId);
            }
            if ($sectionId && Schema::hasColumn('homeworks','section_id')) {
                $homeQ->where('homeworks.section_id', $sectionId);
            }

            if ($dueCol) {
                $homeQ->whereDate("homeworks.$dueCol", '>=', Carbon::today())
                      ->whereDate("homeworks.$dueCol", '<=', Carbon::today()->addDays(7));
            }

            // Optional subject join
            if (Schema::hasTable('subjects') && Schema::hasColumn('homeworks', 'subject_id')) {
                $subjectNameCol = $this->firstExistingColumn('subjects', ['name','subject_name','title']);
                if ($subjectNameCol) {
                    $homeQ->leftJoin('subjects as s', 's.id', '=', 'homeworks.subject_id')
                          ->addSelect('homeworks.*', DB::raw('s.' . $subjectNameCol . ' as _subject_name'));
                }
            }

            $homeworkDueCount = (clone $homeQ)->count('homeworks.id');

            $homeworks = $homeQ
                ->orderBy($dueCol ? "homeworks.$dueCol" : 'homeworks.id', 'asc')
                ->limit(5)
                ->get()
                ->map(function ($h) use ($dueCol, $titleCol) {
                    $title = $titleCol ? ($h->{$titleCol} ?? 'Homework') : 'Homework';
                    $subjectName = property_exists($h, '_subject_name') ? ($h->_subject_name ?: null) : null;

                    return [
                        'title'     => $title,
                        'subject'   => $subjectName,
                        'due_date'  => $dueCol ? optional(Carbon::parse($h->{$dueCol}))->toDateString() : null,
                    ];
                });
        }

        // Exams next 30 days
        $upcomingExamsCount = 0;
        $exams = collect();
        if (class_exists(ExamSchedule::class) && Schema::hasTable('exam_schedules')) {
            $dateCol    = $this->resolveColumn('exam_schedules', ['exam_date','date','scheduled_for','starts_at','start_date']);
            $titleCol   = $this->firstExistingColumn('exam_schedules', ['title','name','exam_name']);
            $subjectCol = $this->firstExistingColumn('exam_schedules', ['subject_name','subject']);

            if ($dateCol) {
                $now  = Carbon::now()->startOfDay();
                $in30 = Carbon::now()->addDays(30)->endOfDay();

                $examQ = ExamSchedule::query()
                    ->from('exam_schedules')
                    ->where('exam_schedules.school_id', $schoolId)
                    ->whereBetween("exam_schedules.$dateCol", [$now, $in30]);

                if ($classId && Schema::hasColumn('exam_schedules','class_id')) {
                    $examQ->where('exam_schedules.class_id', $classId);
                }
                if ($sectionId && Schema::hasColumn('exam_schedules','section_id')) {
                    $examQ->where('exam_schedules.section_id', $sectionId);
                }

                $upcomingExamsCount = (clone $examQ)->count('exam_schedules.id');

                $exams = $examQ->orderBy("exam_schedules.$dateCol")
                    ->limit(5)
                    ->get()
                    ->map(function ($e) use ($dateCol, $titleCol, $subjectCol) {
                        return [
                            'title'   => $titleCol   ? ($e->{$titleCol} ?? 'Exam') : 'Exam',
                            'subject' => $subjectCol ? ($e->{$subjectCol} ?? null) : null,
                            'date'    => Carbon::parse($e->{$dateCol})->toDateString(),
                        ];
                    });
            }
        }

        return [
            'id'                   => $child->id,
            'name'                 => $child->name,
            'class'                => $className ?: ($classId ? ('Class #'.$classId) : null),
            'attendance_rate'      => $attendanceRate,
            'present_days'         => $presentDays,
            'total_days'           => $totalDays,
            'homework_due_count'   => $homeworkDueCount,
            'homeworks'            => $homeworks,
            'upcoming_exams_count' => $upcomingExamsCount,
            'exams'                => $exams,
        ];
    }

    private function parentExtras(int $schoolId, int $userId): array
    {
        // Retained for compatibility
        $childrenCount = User::where('school_id', $schoolId)
            ->where('role', 'student')
            ->where('parent_id', $userId)
            ->count();

        return compact('childrenCount');
    }

    private function resolveColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $col) {
            if (Schema::hasColumn($table, $col)) {
                return $col;
            }
        }
        return null;
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        return $this->resolveColumn($table, $candidates);
    }
}

if (! function_exists('route_exists')) {
    function route_exists(string $name): bool
    {
        try {
            return app('router')->has($name);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
