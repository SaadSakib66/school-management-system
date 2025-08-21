<!--begin::Header-->
<nav class="app-header navbar navbar-expand bg-body">
    <!--begin::Container-->
    <div class="container-fluid">
        <!--begin::Start Navbar Links-->
        <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
            <i class="bi bi-list"></i>
            </a>
        </li>
        <li class="nav-item d-none d-md-block"><a href="#" class="nav-link">Home</a></li>
        </ul>
        <!--end::Start Navbar Links-->
        <!--begin::End Navbar Links-->
        <ul class="navbar-nav ms-auto">

        <!--end::Navbar Search-->

        <!--begin::Notifications Dropdown Menu-->
        <li class="nav-item dropdown">
            <a class="nav-link" data-bs-toggle="dropdown" href="#">
            <i class="bi bi-bell-fill"></i>
            <span class="navbar-badge badge text-bg-warning">15</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
            <span class="dropdown-item dropdown-header">15 Notifications</span>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
                <i class="bi bi-envelope me-2"></i> 4 new messages
                <span class="float-end text-secondary fs-7">3 mins</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
                <i class="bi bi-people-fill me-2"></i> 8 friend requests
                <span class="float-end text-secondary fs-7">12 hours</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item">
                <i class="bi bi-file-earmark-fill me-2"></i> 3 new reports
                <span class="float-end text-secondary fs-7">2 days</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item dropdown-footer"> See All Notifications </a>
            </div>
        </li>
        <!--end::Notifications Dropdown Menu-->

        <!--begin::User Menu Dropdown-->
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
            @php
                $user = Auth::user();

                // Map roles to their photo columns
                $photoField = match($user->role) {
                    'admin'   => 'admin_photo',
                    'teacher' => 'teacher_photo',
                    'student' => 'student_photo',
                    'parent'  => 'parent_photo',
                    default   => null,
                };

                // Get photo path or default image
                $photoPath = !empty($photoField) && !empty($user->$photoField)
                    ? asset('storage/' . $user->$photoField)
                    : asset('images/default-profile.png');
            @endphp

            <img
                src="{{ $photoPath }}"
                class="user-image rounded-circle shadow"
                alt="User Image"
            />
            <span class="d-none d-md-inline">{{Auth::user()->name}}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
            <!--begin::User Image-->
            <li class="user-header text-bg-primary">
                <img
                    src="{{ $photoPath }}"
                    class="user-image rounded-circle shadow"
                    alt="User Image"
                />
                <p>
                    {{ ucfirst(Auth::user()->name) }} {{ ucfirst(Auth::user()->last_name) }}
                </p>
            </li>
            <!--end::User Image-->
            {{-- <!--begin::Menu Body-->
            <li class="user-body">
                <!--begin::Row-->
                <div class="row">
                <div class="col-4 text-center"><a href="#">Followers</a></div>
                <div class="col-4 text-center"><a href="#">Sales</a></div>
                <div class="col-4 text-center"><a href="#">Friends</a></div>
                </div>
                <!--end::Row-->
            </li>
            <!--end::Menu Body--> --}}
            <!--begin::Menu Footer-->
            <li class="user-footer">
                @php
                    $user = Auth::user();
                    $accountRoute = match($user->role) {
                        'admin' => route('admin.account'),
                        'teacher' => route('teacher.account'),
                        'student' => route('student.account'),
                        'parent'  => route('parent.account'),
                        default   => '#', // fallback if no role match
                    };
                @endphp

                <a href="{{ $accountRoute }}" class="btn btn-default btn-flat">Profile</a>
                <a href="{{ url('admin/logout') }}" class="btn btn-default btn-flat float-end">Sign out</a>
            </li>
            <!--end::Menu Footer-->
            </ul>
        </li>
        <!--end::User Menu Dropdown-->
        </ul>
        <!--end::End Navbar Links-->
    </div>
    <!--end::Container-->
</nav>
<!--end::Header-->
