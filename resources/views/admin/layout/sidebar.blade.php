<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    @php
        use App\Models\School;

        $user            = Auth::user();
        $currentSchoolId = session('current_school_id');
        $isSuper         = $user && $user->role === 'super_admin';
        $isAdminContext  = $isSuper && $currentSchoolId;

        // Brand school & text
        $brandSchool = $isAdminContext ? School::find($currentSchoolId) : ($user?->school);
        $brandText   = $isSuper && !$isAdminContext
            ? 'Super Admin'
            : ($brandSchool?->short_name ?? $brandSchool?->name ?? 'School');

        // Brand link
        $dashboardRoute = match (true) {
            $isAdminContext => route('admin.dashboard'),
            $isSuper        => route('superadmin.dashboard'),
            $user && in_array($user->role, ['admin','teacher','student','parent']) => route($user->role . '.dashboard'),
            default         => route('admin.login.page'),
        };

        // Helper: unified My Account target + active state
        [$accountRouteName, $accountActive] = match ($user?->role) {
            'admin' => [
                'admin.account',
                request()->routeIs('admin.account') || request()->routeIs('admin.edit-account'),
            ],
            'teacher' => [
                'teacher.account',
                request()->routeIs('teacher.account') || request()->routeIs('teacher.edit-account'),
            ],
            'student' => [
                'student.account',
                request()->routeIs('student.account') || request()->routeIs('student.edit-account'),
            ],
            'parent' => [
                'parent.account',
                request()->routeIs('parent.account') || request()->routeIs('parent.edit-account'),
            ],
            'super_admin' => $isAdminContext
                ? [
                    'admin.account',
                    request()->routeIs('admin.account') || request()->routeIs('admin.edit-account'),
                  ]
                : [
                    'superadmin.account',
                    request()->routeIs('superadmin.account') || request()->routeIs('superadmin.edit-account'),
                  ],
            default => ['#', false],
        };
    @endphp

    <!-- Brand -->
    @php
        // $brandSchool is already set earlier in your sidebar (from your snippet)
        // Fallback logo
        $defaultLogo = asset('admin-assets/images/AdminLTELogo.png');

        // Resolve school logo (supports both absolute URLs and storage paths)
        $schoolLogo = null;
        if (!empty($brandSchool?->logo)) {
            $schoolLogo = \Illuminate\Support\Str::startsWith($brandSchool->logo, ['http://','https://'])
                ? $brandSchool->logo
                : asset('storage/'.$brandSchool->logo);
        }
    @endphp

    <div class="sidebar-brand">
        <a href="{{ $dashboardRoute }}" class="brand-link">
            <img
                src="{{ $schoolLogo ?: $defaultLogo }}"
                alt="{{ $brandSchool?->short_name ?? $brandSchool?->name ?? 'Logo' }}"
                class="brand-image opacity-75 shadow"
                onerror="this.onerror=null;this.src='{{ $defaultLogo }}';"
            />
            <span class="brand-text fw-light">{{ $brandText }}</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                {{-- ===================== ADMIN (and Super Admin in context) ===================== --}}
                @if($isAdminContext || ($user && $user->role === 'admin'))

                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-speedometer2"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.admin.list') }}" class="nav-link {{ request()->routeIs('admin.admin.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-person-gear"></i>
                            <p>Admins</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.teacher.list') }}" class="nav-link {{ request()->routeIs('admin.teacher.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-person-badge"></i>
                            <p>Teachers</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.student.list') }}" class="nav-link {{ request()->routeIs('admin.student.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-mortarboard"></i>
                            <p>Students</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.parent.list') }}" class="nav-link {{ request()->routeIs('admin.parent.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-people"></i>
                            <p>Parents</p>
                        </a>
                    </li>

                    {{-- ===================== FEES (Admin) ===================== --}}
                    <li class="nav-item {{ request()->routeIs('admin.fees.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.fees.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-cash-coin"></i>
                            <p>Fees <i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.fees.structures.index') }}"
                                class="nav-link {{ request()->routeIs('admin.fees.structures.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon"></i><p>Fee Structure</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.fees.invoices.index') }}"
                                class="nav-link {{ request()->routeIs('admin.fees.invoices.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon"></i><p>Payments</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.fees.reports.class-monthly') }}"
                                class="nav-link {{ request()->routeIs('admin.fees.reports.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon"></i><p>Reports</p>
                                </a>
                            </li>
                        </ul>
                    </li>


                    <li class="nav-item {{ request()->routeIs('admin.class.*') || request()->routeIs('admin.subject.*') || request()->routeIs('admin.assign-subject.*') || request()->routeIs('admin.assign-class-teacher.*') || request()->routeIs('admin.class-timetable.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.class.*') || request()->routeIs('admin.subject.*') || request()->routeIs('admin.assign-subject.*') || request()->routeIs('admin.assign-class-teacher.*') || request()->routeIs('admin.class-timetable.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-journal-bookmark"></i>
                            <p>Academics <i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.class.list') }}" class="nav-link {{ request()->routeIs('admin.class.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon"></i><p>Classes</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.subject.list') }}" class="nav-link {{ request()->routeIs('admin.subject.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon"></i><p>Subjects</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.assign-subject.list') }}" class="nav-link {{ request()->routeIs('admin.assign-subject.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon"></i><p>Assign Subject to Class</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.assign-class-teacher.list') }}" class="nav-link {{ request()->routeIs('admin.assign-class-teacher.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon"></i><p>Assign Class to Teacher</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.class-timetable.list') }}" class="nav-link {{ request()->routeIs('admin.class-timetable.*') ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon"></i><p>Class Timetable</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item {{ request()->routeIs('admin.exam.*') || request()->routeIs('admin.exam-schedule.*') || request()->routeIs('admin.marks-register.*') || request()->routeIs('admin.marks-grade.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.exam.*') || request()->routeIs('admin.exam-schedule.*') || request()->routeIs('admin.marks-register.*') || request()->routeIs('admin.marks-grade.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-clipboard2-check"></i>
                            <p>Examinations <i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('admin.exam.list') }}" class="nav-link {{ request()->routeIs('admin.exam.*') ? 'active' : '' }}"><i class="bi bi-circle nav-icon"></i><p>Exam List</p></a></li>
                            <li class="nav-item"><a href="{{ route('admin.exam-schedule.list') }}" class="nav-link {{ request()->routeIs('admin.exam-schedule.*') ? 'active' : '' }}"><i class="bi bi-circle nav-icon"></i><p>Exam Schedule</p></a></li>
                            <li class="nav-item"><a href="{{ route('admin.marks-register.list') }}" class="nav-link {{ request()->routeIs('admin.marks-register.*') ? 'active' : '' }}"><i class="bi bi-circle nav-icon"></i><p>Marks Register</p></a></li>
                            <li class="nav-item"><a href="{{ route('admin.marks-grade.list') }}" class="nav-link {{ request()->routeIs('admin.marks-grade.*') ? 'active' : '' }}"><i class="bi bi-circle nav-icon"></i><p>Marks Grade</p></a></li>
                        </ul>
                    </li>

                    <li class="nav-item {{ request()->routeIs('admin.student-attendance.*') || request()->routeIs('admin.attendance-report.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.student-attendance.*') || request()->routeIs('admin.attendance-report.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-calendar2-check"></i>
                            <p>Attendance <i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('admin.student-attendance.view') }}" class="nav-link {{ request()->routeIs('admin.student-attendance.*') ? 'active' : '' }}"><i class="bi bi-circle nav-icon"></i><p>Student Attendance</p></a></li>
                            <li class="nav-item"><a href="{{ route('admin.attendance-report.view') }}" class="nav-link {{ request()->routeIs('admin.attendance-report.*') ? 'active' : '' }}"><i class="bi bi-circle nav-icon"></i><p>Attendance Report</p></a></li>
                        </ul>
                    </li>

                    <li class="nav-item {{ request()->routeIs('admin.notice-board.*') || request()->routeIs('admin.email.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.notice-board.*') || request()->routeIs('admin.email.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-megaphone"></i>
                            <p>Communicate <i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('admin.notice-board.list') }}" class="nav-link {{ request()->routeIs('admin.notice-board.*') ? 'active' : '' }}"><i class="bi bi-circle nav-icon"></i><p>Notice Board</p></a></li>
                            <li class="nav-item"><a href="{{ route('admin.email.form') }}" class="nav-link {{ request()->routeIs('admin.email.*') ? 'active' : '' }}"><i class="bi bi-circle nav-icon"></i><p>Send Email</p></a></li>
                        </ul>
                    </li>

                    @php
                        $inHomework        = request()->routeIs('admin.homework.*');          // includes report
                        $inHomeworkReport  = request()->routeIs('admin.homework.report');     // report only
                    @endphp

                    <li class="nav-item {{ ($inHomework || $inHomeworkReport) ? 'menu-open' : '' }}">
                        <a href="#"
                        class="nav-link {{ ($inHomework || $inHomeworkReport) ? 'active' : '' }}">
                            <i class="nav-icon bi bi-journal-text"></i>
                            <p>Homework <i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            {{-- Homework (CRUD/list etc.) — active on any admin.homework.* EXCEPT report --}}
                            <li class="nav-item">
                                <a href="{{ route('admin.homework.list') }}"
                                class="nav-link {{ ($inHomework && !$inHomeworkReport) ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon"></i><p>Homework</p>
                                </a>
                            </li>

                            {{-- Homework Report — active only on admin.homework.report --}}
                            <li class="nav-item">
                                <a href="{{ route('admin.homework.report') }}"
                                class="nav-link {{ $inHomeworkReport ? 'active' : '' }}">
                                    <i class="bi bi-circle nav-icon"></i><p>Homework Report</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- My Account (works for admin & super admin in context) --}}
                    <li class="nav-item">
                        <a href="{{ route($accountRouteName) }}" class="nav-link {{ $accountActive ? 'active' : '' }}">
                            <i class="nav-icon bi bi-person-circle"></i>
                            <p>My Account</p>
                        </a>
                    </li>

                {{-- ===================== TEACHER ===================== --}}
                @elseif($user && $user->role === 'teacher')

                    <li class="nav-item"><a href="{{ route('teacher.dashboard') }}" class="nav-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}"><i class="nav-icon bi bi-speedometer2"></i><p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="{{ route('teacher.my-class-subject') }}" class="nav-link {{ request()->routeIs('teacher.my-class-subject') ? 'active' : '' }}"><i class="nav-icon bi bi-journals"></i><p>My Class & Subject</p></a></li>
                    <li class="nav-item"><a href="{{ route('teacher.my-timetable') }}" class="nav-link {{ request()->routeIs('teacher.my-timetable') ? 'active' : '' }}"><i class="nav-icon bi bi-table"></i><p>My Class Timetable</p></a></li>
                    <li class="nav-item"><a href="{{ route('teacher.my-exam-timetable') }}" class="nav-link {{ request()->routeIs('teacher.my-exam-timetable') ? 'active' : '' }}"><i class="nav-icon bi bi-calendar2-event"></i><p>My Exam Timetable</p></a></li>
                    <li class="nav-item"><a href="{{ route('teacher.my-student') }}" class="nav-link {{ request()->routeIs('teacher.my-student') ? 'active' : '' }}"><i class="nav-icon bi bi-mortarboard"></i><p>My Students</p></a></li>
                    <li class="nav-item"><a href="{{ route('teacher.homework.list') }}" class="nav-link {{ request()->routeIs('teacher.homework.*') ? 'active' : '' }}"><i class="nav-icon bi bi-journal-text"></i><p>Homework</p></a></li>

                    <li class="nav-item {{ request()->routeIs('teacher.student-attendance.*') || request()->routeIs('teacher.attendance-report.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('teacher.student-attendance.*') || request()->routeIs('teacher.attendance-report.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-calendar-check"></i>
                            <p>Attendance <i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('teacher.student-attendance.view') }}" class="nav-link {{ request()->routeIs('teacher.student-attendance.*') ? 'active' : '' }}"><i class="bi bi-circle nav-icon"></i><p>Student Attendance</p></a></li>
                            <li class="nav-item"><a href="{{ route('teacher.attendance-report.view') }}" class="nav-link {{ request()->routeIs('teacher.attendance-report.*') ? 'active' : '' }}"><i class="bi bi-circle nav-icon"></i><p>Attendance Report</p></a></li>
                        </ul>
                    </li>

                    <li class="nav-item"><a href="{{ route('teacher.marks-register.list') }}" class="nav-link {{ request()->routeIs('teacher.marks-register.*') ? 'active' : '' }}"><i class="nav-icon bi bi-123"></i><p>Marks Register</p></a></li>
                    <li class="nav-item"><a href="{{ route('teacher.notice-board') }}" class="nav-link {{ request()->routeIs('teacher.notice-board') ? 'active' : '' }}"><i class="nav-icon bi bi-megaphone"></i><p>Notice Board</p></a></li>
                    <li class="nav-item"><a href="{{ route('teacher.inbox') }}" class="nav-link {{ request()->routeIs('teacher.inbox*') ? 'active' : '' }}"><i class="nav-icon bi bi-inbox"></i><p>My Emails</p></a></li>

                    <li class="nav-item">
                        <a href="{{ route($accountRouteName) }}" class="nav-link {{ $accountActive ? 'active' : '' }}">
                            <i class="nav-icon bi bi-person-circle"></i><p>My Account</p>
                        </a>
                    </li>

                {{-- ===================== STUDENT ===================== --}}
                @elseif($user && $user->role === 'student')

                    <li class="nav-item"><a href="{{ route('student.dashboard') }}" class="nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}"><i class="nav-icon bi bi-speedometer2"></i><p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="{{ route('student.my-calendar') }}" class="nav-link {{ request()->routeIs('student.my-calendar') ? 'active' : '' }}"><i class="nav-icon bi bi-calendar-week"></i><p>My Calendar</p></a></li>
                    <li class="nav-item"><a href="{{ route('student.attendance.month') }}" class="nav-link {{ request()->routeIs('student.attendance.month') ? 'active' : '' }}"><i class="nav-icon bi bi-calendar-check"></i><p>My Attendance</p></a></li>
                    <li class="nav-item"><a href="{{ route('student.homework.list') }}" class="nav-link {{ request()->routeIs('student.homework.list') || request()->routeIs('student.homework.submit*') ? 'active' : '' }}"><i class="nav-icon bi bi-journal-text"></i><p>My Homework</p></a></li>
                    <li class="nav-item"><a href="{{ route('student.homework.submitted') }}" class="nav-link {{ request()->routeIs('student.homework.submitted') ? 'active' : '' }}"><i class="nav-icon bi bi-upload"></i><p>Submitted Homework</p></a></li>
                    <li class="nav-item"><a href="{{ route('student.my-exam-calendar') }}" class="nav-link {{ request()->routeIs('student.my-exam-calendar') ? 'active' : '' }}"><i class="nav-icon bi bi-calendar2-event"></i><p>My Exam Calendar</p></a></li>
                    <li class="nav-item"><a href="{{ route('student.marks-register.list') }}" class="nav-link {{ request()->routeIs('student.marks-register.list') ? 'active' : '' }}"><i class="nav-icon bi bi-123"></i><p>My Exam Result</p></a></li>
                    <li class="nav-item"><a href="{{ route('student.my-timetable') }}" class="nav-link {{ request()->routeIs('student.my-timetable') ? 'active' : '' }}"><i class="nav-icon bi bi-table"></i><p>My Class Timetable</p></a></li>
                    <li class="nav-item"><a href="{{ route('student.my-exam-timetable') }}" class="nav-link {{ request()->routeIs('student.my-exam-timetable') ? 'active' : '' }}"><i class="nav-icon bi bi-clock-history"></i><p>My Exam Timetable</p></a></li>
                    <li class="nav-item"><a href="{{ route('student.notice-board') }}" class="nav-link {{ request()->routeIs('student.notice-board') ? 'active' : '' }}"><i class="nav-icon bi bi-megaphone"></i><p>Notice Board</p></a></li>
                    <li class="nav-item"><a href="{{ route('student.inbox') }}" class="nav-link {{ request()->routeIs('student.inbox*') ? 'active' : '' }}"><i class="nav-icon bi bi-inbox"></i><p>My Emails</p></a></li>
                    <li class="nav-item">
                        <a href="{{ route('student.fees.index') }}" class="nav-link {{ request()->routeIs('student.fees.index') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-cash-stack"></i><p>My Fees</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route($accountRouteName) }}" class="nav-link {{ $accountActive ? 'active' : '' }}">
                            <i class="nav-icon bi bi-person-circle"></i><p>My Account</p>
                        </a>
                    </li>

                {{-- ===================== PARENT ===================== --}}
                @elseif($user && $user->role === 'parent')

                    <li class="nav-item"><a href="{{ route('parent.dashboard') }}" class="nav-link {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}"><i class="nav-icon bi bi-speedometer2"></i><p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="{{ route('parent.my-timetable') }}" class="nav-link {{ request()->routeIs('parent.my-timetable') ? 'active' : '' }}"><i class="nav-icon bi bi-table"></i><p>Child Class Timetable</p></a></li>
                    <li class="nav-item"><a href="{{ route('parent.child.homework.list') }}" class="nav-link {{ request()->routeIs('parent.child.homework.*') ? 'active' : '' }}"><i class="nav-icon bi bi-journal-text"></i><p>Child Homework</p></a></li>
                    <li class="nav-item"><a href="{{ route('parent.attendance.month') }}" class="nav-link {{ request()->routeIs('parent.attendance.month') ? 'active' : '' }}"><i class="nav-icon bi bi-calendar-check"></i><p>Child Attendance</p></a></li>
                    <li class="nav-item"><a href="{{ route('parent.my-exam-timetable') }}" class="nav-link {{ request()->routeIs('parent.my-exam-timetable') ? 'active' : '' }}"><i class="nav-icon bi bi-calendar2-event"></i><p>Child Exam Schedule</p></a></li>
                    <li class="nav-item"><a href="{{ route('parent.marks-register.list') }}" class="nav-link {{ request()->routeIs('parent.marks-register.list') ? 'active' : '' }}"><i class="nav-icon bi bi-123"></i><p>Child Exam Result</p></a></li>
                    <li class="nav-item"><a href="{{ route('parent.notice-board') }}" class="nav-link {{ request()->routeIs('parent.notice-board') ? 'active' : '' }}"><i class="nav-icon bi bi-megaphone"></i><p>Notice Board</p></a></li>
                    <li class="nav-item"><a href="{{ route('parent.inbox') }}" class="nav-link {{ request()->routeIs('parent.inbox*') ? 'active' : '' }}"><i class="nav-icon bi bi-inbox"></i><p>My Emails</p></a></li>
                    <li class="nav-item">
                        <a href="{{ route('parent.fees.index') }}" class="nav-link {{ request()->routeIs('parent.fees.index') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-cash"></i><p>Child Fees</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route($accountRouteName) }}" class="nav-link {{ $accountActive ? 'active' : '' }}">
                            <i class="nav-icon bi bi-person-circle"></i><p>My Account</p>
                        </a>
                    </li>

                {{-- ===================== SUPER ADMIN (no school selected) ===================== --}}
                @elseif($isSuper && !$isAdminContext)

                    <li class="nav-item">
                        <a href="{{ route('superadmin.dashboard') }}" class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-speedometer2"></i>
                            <p>Global Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('superadmin.schools.index') }}" class="nav-link {{ request()->routeIs('superadmin.schools.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-buildings"></i>
                            <p>Schools</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('superadmin.schools.switch') }}" class="nav-link {{ request()->routeIs('superadmin.schools.switch') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-arrow-left-right"></i>
                            <p>Switch School</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route($accountRouteName) }}" class="nav-link {{ $accountActive ? 'active' : '' }}">
                            <i class="nav-icon bi bi-person-circle"></i>
                            <p>My Account</p>
                        </a>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>
