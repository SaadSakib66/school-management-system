@extends('admin.layout.layout')

@push('styles')
<style>
  .small-box.gradient-blue    { background: linear-gradient(135deg,#3b82f6,#1d4ed8); color:#fff; }
  .small-box.gradient-green   { background: linear-gradient(135deg,#22c55e,#15803d); color:#fff; }
  .small-box.gradient-amber   { background: linear-gradient(135deg,#f59e0b,#b45309); color:#fff; }
  .small-box.gradient-purple  { background: linear-gradient(135deg,#a855f7,#6d28d9); color:#fff; }
  .small-box .inner h3 { font-weight:800; }
  .card-table td, .card-table th { vertical-align: middle; }
</style>
@endpush

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-8">
          <h3 class="mb-0">
            {{ $school->short_name ?? $school->name }} — Student Dashboard
          </h3>
          @if(!empty($overview['my_class']))
            <div class="text-muted">My Class: {{ $overview['my_class'] }}</div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      {{-- Stat cards --}}
      <div class="row g-3">
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="small-box gradient-blue">
            <div class="inner">
              <h3 class="mb-1">
                @if(!is_null($overview['attendance_rate'])) {{ $overview['attendance_rate'] }}%@else — @endif
              </h3>
              <p class="mb-0">Attendance Rate</p>
              @if(!empty($overview['total_days']))
                <small>{{ $overview['present_days'] }}/{{ $overview['total_days'] }} days present</small>
              @endif
            </div>
            <div class="icon"><i class="fas fa-user-check"></i></div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <div class="small-box gradient-green">
            <div class="inner">
              <h3 class="mb-1">{{ $overview['subjects_count'] ?? 0 }}</h3>
              <p class="mb-0">Subjects</p>
              <small>Currently enrolled</small>
            </div>
            <div class="icon"><i class="fas fa-book"></i></div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <div class="small-box gradient-amber">
            <div class="inner">
              <h3 class="mb-1">{{ $overview['homework_due_count'] ?? 0 }}</h3>
              <p class="mb-0">Homework Due (7 days)</p>
              <small>Next 7 days</small>
            </div>
            <div class="icon"><i class="fas fa-tasks"></i></div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <div class="small-box gradient-purple">
            <div class="inner">
              <h3 class="mb-1">{{ $overview['upcoming_exams_count'] ?? 0 }}</h3>
              <p class="mb-0">Upcoming Exams (30 days)</p>
              <small>Stay prepared</small>
            </div>
            <div class="icon"><i class="fas fa-pencil-alt"></i></div>
          </div>
        </div>
      </div>

      {{-- Homework due soon --}}
      <div class="row mt-3">
        <div class="col-md-6">
          <div class="card card-primary card-outline">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0">Homework Due (Next 7 Days)</h3>
              <a class="btn btn-sm btn-primary" href="{{ route('student.homework.list') }}">View All</a>
            </div>
            <div class="card-body p-0">
              @if(!empty($overview['homeworks']) && count($overview['homeworks']) > 0)
                <div class="table-responsive">
                  <table class="table table-striped mb-0 card-table">
                    <thead>
                      <tr>
                        <th style="width:40%">Title</th>
                        <th style="width:30%">Subject</th>
                        <th style="width:30%">Due</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($overview['homeworks'] as $hw)
                        <tr>
                          <td>{{ $hw['title'] }}</td>
                          <td>{{ $hw['subject'] ?? '—' }}</td>
                          <td>{{ $hw['due_date'] ? \Carbon\Carbon::parse($hw['due_date'])->format('d M Y') : '—' }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <div class="p-3 text-muted">No homework due in the next 7 days. 🎉</div>
              @endif
            </div>
          </div>
        </div>

        {{-- Upcoming exams --}}
        <div class="col-md-6">
          <div class="card card-primary card-outline">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0">Upcoming Exams (Next 30 Days)</h3>
              <a class="btn btn-sm btn-primary" href="{{ route('student.my-exam-timetable') }}">Exam Timetable</a>
            </div>
            <div class="card-body p-0">
              @if(!empty($overview['exams']) && count($overview['exams']) > 0)
                <div class="table-responsive">
                  <table class="table table-striped mb-0 card-table">
                    <thead>
                      <tr>
                        <th style="width:40%">Title</th>
                        <th style="width:30%">Subject</th>
                        <th style="width:30%">Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($overview['exams'] as $ex)
                        <tr>
                          <td>{{ $ex['title'] }}</td>
                          <td>{{ $ex['subject'] ?? '—' }}</td>
                          <td>{{ \Carbon\Carbon::parse($ex['date'])->format('d M Y') }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @else
                <div class="p-3 text-muted">No upcoming exams in the next 30 days.</div>
              @endif
            </div>
          </div>
        </div>
      </div>

      {{-- Quick actions row (optional second placement) --}}
      @if(!empty($overview['quick_links']))
        <div class="row mt-3">
          <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
              @foreach($overview['quick_links'] as $link)
                <a href="{{ $link['route'] }}" class="btn btn-outline-primary btn-sm">{{ $link['label'] }}</a>
              @endforeach
            </div>
          </div>
        </div>
      @endif

    </div>
  </div>
</main>
@endsection
