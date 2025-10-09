@extends('admin.layout.layout')

@push('styles')
<style>
  .small-box{border-radius:14px; box-shadow:0 6px 18px rgba(0,0,0,.08); position:relative; overflow:hidden}
  .small-box .inner h3{font-weight:800; margin:0}
  .small-box .inner p{margin:0; opacity:.9}
  .small-box a{color:#fff; text-decoration:underline}
  .gradient-blue   {background:linear-gradient(135deg,#3b82f6,#1d4ed8); color:#fff}
  .gradient-purple {background:linear-gradient(135deg,#a855f7,#6d28d9); color:#fff}
  .gradient-amber  {background:linear-gradient(135deg,#f59e0b,#b45309); color:#fff}
  .gradient-green  {background:linear-gradient(135deg,#22c55e,#15803d); color:#fff}
  .quick-actions .btn{border-radius:12px; padding:.7rem 1rem; font-weight:600}
</style>
@endpush

@section('content')
<main class="app-main">
  {{-- Header --}}
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-8">
          <h3 class="mb-0">{{ $stats['header_title'] ?? 'Dashboard' }}</h3>
          <div class="text-muted">Teacher overview — {{ $school->short_name ?? $school->name }}</div>
        </div>
        <div class="col-sm-4 text-sm-end text-muted">
          <span>{{ \Carbon\Carbon::today()->format('D, d M Y') }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      {{-- KPIs just for teachers --}}
      <div class="row g-3">
        <div class="col-12 col-md-3">
          <div class="small-box gradient-blue">
            <div class="inner p-3">
              <h3>{{ $extras['assignedClasses'] ?? 0 }}</h3>
              <p>My Classes</p>
              <a class="d-inline-block mt-1" href="{{ route('teacher.my-class-subject') }}">View classes</a>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-3">
          <div class="small-box gradient-purple">
            <div class="inner p-3">
              <h3>{{ $stats['subjects_total'] ?? 0 }}</h3>
              <p>My Subjects</p>
              <a class="d-inline-block mt-1" href="{{ route('teacher.my-class-subject') }}">View subjects</a>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="small-box gradient-amber">
                <div class="inner p-3">
                <h3>{{ $extras['pendingHomework'] ?? 0 }}</h3>
                <p>Pending Homeworks (Mine)</p>
                <a class="d-inline-block mt-1" href="{{ route('teacher.homework.list', ['mine' => 1, 'pending' => 1]) }}">
                    Open my homework
                </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
          <div class="small-box gradient-green">
            <div class="inner p-3">
              <h3>{{ $extras['todayMarked'] ?? 0 }}</h3>
              <p>Attendance Marked Today</p>
              <a class="d-inline-block mt-1" href="{{ route('teacher.student-attendance.view') }}">Go to attendance</a>
            </div>
          </div>
        </div>
      </div>

      {{-- Quick actions --}}
      <div class="card mt-3">
        <div class="card-header">
          <h3 class="card-title">Quick Actions</h3>
        </div>
        <div class="card-body quick-actions">
          <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('teacher.student-attendance.view') }}" class="btn btn-primary">Take Attendance</a>
            <a href="{{ route('teacher.homework.add') }}" class="btn btn-warning">Create Homework</a>
            <a href="{{ route('teacher.marks-register.list') }}" class="btn btn-success">Marks Register</a>
            <a href="{{ route('teacher.my-timetable') }}" class="btn btn-secondary">My Timetable</a>
            <a href="{{ route('teacher.my-exam-timetable') }}" class="btn btn-info">Exam Timetable</a>
            <a href="{{ route('teacher.notice-board') }}" class="btn btn-dark">Notice Board</a>
          </div>
        </div>
      </div>

      {{-- Optional tiny info (remove if you want ultra-minimal) --}}
      <div class="row g-3 mt-1">
        <div class="col-12 col-lg-6">
          <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Upcoming Exams (Next 30 days)</h3></div>
            <div class="card-body">
              <p class="mb-0 fs-4 fw-bold">{{ $stats['exams_upcoming_30'] ?? 0 }}</p>
              <small class="text-muted">If zero, you’re all clear.</small>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Total Homeworks</h3></div>
            <div class="card-body">
              <p class="mb-0 fs-4 fw-bold">{{ $stats['homeworks_total'] ?? 0 }}</p>
              <small class="text-muted">Includes all statuses.</small>
            </div>
          </div>
        </div>
      </div>
      {{-- End optional --}}
    </div>
  </div>
</main>
@endsection
