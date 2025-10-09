@extends('admin.layout.layout')

@push('styles')
<style>
  .small-box.gradient-blue    { background: linear-gradient(135deg,#3b82f6,#1d4ed8); color:#fff; }
  .small-box.gradient-green   { background: linear-gradient(135deg,#22c55e,#15803d); color:#fff; }
  .small-box.gradient-purple  { background: linear-gradient(135deg,#a855f7,#6d28d9); color:#fff; }
  .small-box.gradient-amber   { background: linear-gradient(135deg,#f59e0b,#b45309); color:#fff; }
  .small-box .inner h3 { font-weight:800; }
  .list-unstyled li + li { margin-top:.25rem; }
  .card-child { border-radius: 12px; }
  .badge-soft { background: rgba(0,0,0,.08); color:#111; }
  .link-muted { color:#4b5563; text-decoration:none; }
  .link-muted:hover { text-decoration:underline; }
</style>
@endpush

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-8">
          <h3 class="mb-0">{{ $stats['header_title'] ?? 'Parent Dashboard' }}</h3>
          <div class="text-muted">Welcome, Parent — {{ $school->short_name ?? $school->name }}</div>
        </div>
        <div class="col-sm-4 text-sm-end">
          <span class="badge badge-soft">Children: {{ $overview['children_count'] ?? 0 }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      {{-- No children fallback --}}
      @if(($overview['children_count'] ?? 0) === 0)
        <div class="alert alert-info">No linked students found under your account.</div>
      @endif

      {{-- Children grid --}}
      <div class="row">
        @foreach(($overview['children'] ?? []) as $child)
          <div class="col-12 col-lg-6">
            <div class="card card-child mb-4">
              <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                  <h5 class="mb-0">{{ $child['name'] }}</h5>
                  <small class="text-muted">
                    {{ $child['class'] ? 'Class: '.$child['class'] : 'Student' }}
                  </small>
                </div>
                @if(!empty($child['links']))
                  <div class="d-none d-sm-flex gap-2">
                    @foreach($child['links'] as $lnk)
                      <a href="{{ $lnk['route'] }}" class="btn btn-sm btn-outline-primary">{{ $lnk['label'] }}</a>
                    @endforeach
                  </div>
                @endif
              </div>

              <div class="card-body">
                <div class="row g-3">

                  {{-- Attendance --}}
                  <div class="col-12 col-md-4">
                    <div class="small-box gradient-green">
                      <div class="inner">
                        <h3 class="mb-0">
                          {{ is_null($child['attendance_rate']) ? '—' : $child['attendance_rate'].'%' }}
                        </h3>
                        <p class="mb-0">Attendance</p>
                        @if(!is_null($child['attendance_rate']))
                          <small class="opacity-75">{{ $child['present_days'] }}/{{ $child['total_days'] }} days</small>
                        @endif
                      </div>
                    </div>
                  </div>

                  {{-- Homework due (7d) --}}
                  <div class="col-12 col-md-4">
                    <div class="small-box gradient-amber">
                      <div class="inner">
                        <h3 class="mb-0">{{ $child['homework_due_count'] ?? 0 }}</h3>
                        <p class="mb-0">Homework (7 days)</p>
                      </div>
                    </div>
                  </div>

                  {{-- Upcoming exams (30d) --}}
                  <div class="col-12 col-md-4">
                    <div class="small-box gradient-blue">
                      <div class="inner">
                        <h3 class="mb-0">{{ $child['upcoming_exams_count'] ?? 0 }}</h3>
                        <p class="mb-0">Upcoming Exams (30d)</p>
                      </div>
                    </div>
                  </div>

                  {{-- Lists --}}
                  <div class="col-12 col-lg-6">
                    <h6 class="mb-2">Due Homework (next 7 days)</h6>
                    <ul class="list-unstyled">
                      @forelse(($child['homeworks'] ?? []) as $hw)
                        <li>
                          <span class="fw-semibold">{{ $hw['title'] }}</span>
                          @if($hw['subject']) <small class="text-muted">— {{ $hw['subject'] }}</small>@endif
                          @if($hw['due_date']) <div class="small text-muted">Due: {{ $hw['due_date'] }}</div>@endif
                        </li>
                      @empty
                        <li class="text-muted">No homework due soon.</li>
                      @endforelse
                    </ul>
                  </div>

                  <div class="col-12 col-lg-6">
                    <h6 class="mb-2">Upcoming Exams (next 30 days)</h6>
                    <ul class="list-unstyled">
                      @forelse(($child['exams'] ?? []) as $ex)
                        <li>
                          <span class="fw-semibold">{{ $ex['title'] }}</span>
                          @if($ex['subject']) <small class="text-muted">— {{ $ex['subject'] }}</small>@endif
                          @if($ex['date']) <div class="small text-muted">Date: {{ $ex['date'] }}</div>@endif
                        </li>
                      @empty
                        <li class="text-muted">No upcoming exams in the next 30 days.</li>
                      @endforelse
                    </ul>
                  </div>

                </div>
              </div>

              {{-- Compact links for small screens --}}
              @if(!empty($child['links']))
                <div class="card-footer d-sm-none">
                  <div class="d-flex flex-wrap gap-2">
                    @foreach($child['links'] as $lnk)
                      <a href="{{ $lnk['route'] }}" class="btn btn-sm btn-outline-primary">{{ $lnk['label'] }}</a>
                    @endforeach
                  </div>
                </div>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</main>
@endsection
