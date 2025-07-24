<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">
        <!--begin::Brand Link-->
        <a href="./index.html" class="brand-link">
            <!--begin::Brand Image-->
            <img
                src="{{ asset('admin/images/AdminLTELogo.png') }}"
                alt="AdminLTE Logo"
                class="brand-image opacity-75 shadow"
                />
            <!--end::Brand Image-->
            <!--begin::Brand Text-->
            <span class="brand-text fw-light">Barabd School</span>
            <!--end::Brand Text-->
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
                    <a href="{{ url('admin/dashboard') }}" class="nav-link @if(Request::segment(2) == 'dashboard') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('admin/admin/list') }}" class="nav-link @if(Request::segment(2) == 'admin') active @endif">
                        <i class="nav-icon bi bi-person"></i>
                        <p>Admin</p>
                    </a>
                </li>


                @elseif(Auth::user()->role == 'teacher')

                <li class="nav-item">
                    <a href="{{ url('teacher/dashboard') }}" class="nav-link @if(Request::segment(2) == 'dashboard') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>


                @elseif(Auth::user()->role == 'student')

                <li class="nav-item">
                    <a href="{{ url('student/dashboard') }}" class="nav-link @if(Request::segment(2) == 'dashboard') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>


                @elseif(Auth::user()->role == 'parent')

                <li class="nav-item">
                    <a href="{{ url('parent/dashboard') }}" class="nav-link @if(Request::segment(2) == 'dashboard') active @endif">
                        <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>


                @endif


                {{-- <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-ui-checks-grid"></i>
                        <p>
                            Components
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="./docs/components/main-header.html" class="nav-link">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Main Header</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="./docs/components/main-sidebar.html" class="nav-link">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Main Sidebar</p>
                            </a>
                        </li>
                    </ul>
                </li> --}}

            </ul>
            <!--end::Sidebar Menu-->
        </nav>
    </div>
    <!--end::Sidebar Wrapper-->
</aside>
