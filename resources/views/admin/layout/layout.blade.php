<!doctype html>
<html lang="en">
    <!--begin::Head-->
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>School - {{ !empty($header_title) ? $header_title : ''}}</title>
        <!--begin::Primary Meta Tags-->
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="title" content="AdminLTE v4 | Dashboard" />
        <meta name="author" content="ColorlibHQ" />
        <meta
            name="description"
            content="School Management System is a comprehensive platform for managing school operations, including student enrollment, attendance tracking, and academic performance monitoring."
            />
        <meta
            name="keywords"
            content="school management, student enrollment, attendance tracking, academic performance, education software"
            />
        <!--end::Primary Meta Tags-->
        <!--begin::Fonts-->
        @include('admin.layout.styles')

    </head>
    <!--end::Head-->
    <!--begin::Body-->
    <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
        <!--begin::App Wrapper-->
        <div class="app-wrapper">
            <!--begin::Header-->
            @include('admin.layout.header')
            <!--end::Header-->
            <!--begin::Sidebar-->
            @include('admin.layout.sidebar')
            <!--end::Sidebar-->
            <!--begin::App Main-->
            @yield('content')
            <!--end::App Main-->
            <!--begin::Footer-->
            @include('admin.layout.footer')
            <!--end::Footer-->
        </div>
        <!--end::App Wrapper-->
        <!--begin::Script-->
        <!--begin::Third Party Plugin(OverlayScrollbars)-->
        @include('admin.layout.scripts')
        <!--end::Script-->
    </body>
    <!--end::Body-->
</html>
