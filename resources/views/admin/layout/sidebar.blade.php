

<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">
        <!--begin::Brand Link-->
        @php
            $dashboardRoute = auth()->check() && in_array(auth()->user()->role, ['admin','teacher','student','parent'])
                ? route(auth()->user()->role . '.dashboard')
                : route('admin.login.page'); // fallback if not logged in
        @endphp

        <a href="{{ $dashboardRoute }}" class="brand-link">
            <img src="{{ asset('admin/images/AdminLTELogo.png') }}" alt="AdminLTE Logo" class="brand-image opacity-75 shadow"/>
            <span class="brand-text fw-light">Barabd School</span>
        </a>

        <!--end::Brand Link-->
    </div>
    <!--end::Sidebar Brand-->
    <!--begin::Sidebar Wrapper-->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <!--begin::Sidebar Menu-->
            <ul
                class="nav sidebar-menu flex-column"
                data-lte-toggle="treeview"
                role="menu"
                data-accordion="false"
                >
                @if(Auth::user()->role == 'admin')

                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link @if(Request::segment(2) == 'dashboard') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.admin.list') }}" class="nav-link @if(Request::segment(2) == 'admin') active @endif">
                        <i class="nav-icon bi bi-person"></i>
                        <p>Admin</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.teacher.list') }}" class="nav-link @if(Request::segment(2) == 'teacher') active @endif">
                        <i class="nav-icon bi bi-person"></i>
                        <p>Teacher</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.student.list') }}" class="nav-link @if(Request::segment(2) == 'student') active @endif">
                        <i class="nav-icon bi bi-person"></i>
                        <p>Student</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.parent.list') }}" class="nav-link @if(Request::segment(2) == 'parent') active @endif">
                        <i class="nav-icon bi bi-person"></i>
                        <p>Parent</p>
                    </a>
                </li>


                <li class="nav-item {{ in_array(Request::segment(2), ['class','subject','assign_subject','assign_class_teacher', 'class_timetable']) ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ in_array(Request::segment(2), ['class','subject','assign_subject','assign_class_teacher', 'class_timetable']) ? 'active' : '' }}">
                        <i class="nav-icon bi bi-clipboard-fill"></i>
                        <p>
                            Academics
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">

                        <li class="nav-item">
                            <a href="{{ route('admin.class.list') }}"
                            class="nav-link @if(Request::segment(2) == 'class') active @endif">
                                <i class="bi bi-circle nav-icon"></i>
                                <p>Class</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.subject.list') }}"
                            class="nav-link @if(Request::segment(2) == 'subject') active @endif">
                                <i class="bi bi-circle nav-icon"></i>
                                <p>Subject</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.assign-subject.list') }}"
                            class="nav-link @if(Request::segment(2) == 'assign_subject') active @endif">
                                <i class="bi bi-circle nav-icon"></i>
                                <p>Assign Subject to Class</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.assign-class-teacher.list') }}"
                            class="nav-link @if(Request::segment(2) == 'assign_class_teacher') active @endif">
                                <i class="bi bi-circle nav-icon"></i>
                                <p>Assign Class to Teacher</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.class-timetable.list') }}"
                            class="nav-link @if(Request::segment(2) == 'class_timetable') active @endif">
                                <i class="bi bi-circle nav-icon"></i>
                                <p>Class Timetable</p>
                            </a>
                        </li>

                    </ul>
                </li>


                <li class="nav-item {{ in_array(Request::segment(2), ['exam','exam_schedule','marks_register','marks_grade']) ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ in_array(Request::segment(2), ['exam','exam_schedule','marks_register','marks_grade']) ? 'active' : '' }}">
                        <i class="nav-icon bi bi-clipboard-fill"></i>
                        <p>
                            Examinations
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">

                        <li class="nav-item">
                            <a href="{{ route('admin.exam.list') }}"
                            class="nav-link @if(Request::segment(2) == 'exam') active @endif">
                                <i class="bi bi-circle nav-icon"></i>
                                <p>Exam List</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.exam-schedule.list') }}"
                            class="nav-link @if(Request::segment(2) == 'exam_schedule') active @endif">
                                <i class="bi bi-circle nav-icon"></i>
                                <p>Exam Schedule</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href=""
                            class="nav-link @if(Request::segment(2) == 'marks_register') active @endif">
                                <i class="bi bi-circle nav-icon"></i>
                                <p>Marks Register</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.assign-class-teacher.list') }}"
                            class="nav-link @if(Request::segment(2) == 'marks_grade') active @endif">
                                <i class="bi bi-circle nav-icon"></i>
                                <p>Marks Grade</p>
                            </a>
                        </li>

                    </ul>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.account') }}" class="nav-link @if(Request::segment(2) == 'account') active @endif">
                        <i class="nav-icon bi bi-person"></i>
                        <p>My Account (Profile)</p>
                    </a>
                </li>


                @elseif(Auth::user()->role == 'teacher')

                <li class="nav-item">
                    <a href="{{ route('teacher.dashboard') }}" class="nav-link @if(Request::segment(2) == 'dashboard') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('teacher.my-class-subject') }}" class="nav-link @if(Request::segment(2) == 'my_class_subject') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            My Class & Subject
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('teacher.my-timetable') }}" class="nav-link @if(Request::segment(2) == 'my_timetable') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            My Class Timetable
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('teacher.my-exam-timetable') }}"
                        class="nav-link @if(Request::segment(2) == 'my_exam_timetable') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>My Exam Timetable</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('teacher.my-student') }}" class="nav-link @if(Request::segment(2) == 'my_student') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            My Student
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('teacher.account') }}" class="nav-link @if(Request::segment(2) == 'account') active @endif">
                        <i class="nav-icon bi bi-person"></i>
                        <p>My Account (Profile)</p>
                    </a>
                </li>


                @elseif(Auth::user()->role == 'student')

                <li class="nav-item">
                    <a href="{{ route('student.dashboard') }}" class="nav-link @if(Request::segment(2) == 'dashboard') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('student.my-calendar') }}" class="nav-link @if(Request::segment(2) == 'my_calendar') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            My Calendar
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('student.my-timetable') }}" class="nav-link @if(Request::segment(2) == 'my_timetable') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            My Class Timetable
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('student.my-exam-timetable') }}"
                        class="nav-link @if(Request::segment(2) == 'my_exam_timetable') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>My Exam Timetable</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('student.account') }}" class="nav-link @if(Request::segment(2) == 'account') active @endif">
                        <i class="nav-icon bi bi-person"></i>
                        <p>My Account (Profile)</p>
                    </a>
                </li>


                @elseif(Auth::user()->role == 'parent')

                <li class="nav-item">
                    <a href="{{ route('parent.dashboard') }}" class="nav-link @if(Request::segment(2) == 'dashboard') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('parent.my-timetable') }}" class="nav-link @if(Request::segment(2) == 'my_timetable') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            My Child's Class Timetable
                        </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('parent.my-exam-timetable') }}"
                        class="nav-link @if(Request::segment(2) == 'my_exam_timetable') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>Child Exam Schedule</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('parent.account') }}" class="nav-link @if(Request::segment(2) == 'account') active @endif">
                        <i class="nav-icon bi bi-person"></i>
                        <p>My Account (Profile)</p>
                    </a>
                </li>


                @endif


            </ul>
            <!--end::Sidebar Menu-->
        </nav>
    </div>
    <!--end::Sidebar Wrapper-->
</aside>
