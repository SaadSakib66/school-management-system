@extends('admin.layout.layout')

@push('styles')
<style>
  .small-box.gradient-blue    { background: linear-gradient(135deg,#3b82f6,#1d4ed8); color:#fff; }
  .small-box.gradient-green   { background: linear-gradient(135deg,#22c55e,#15803d); color:#fff; }
  .small-box.gradient-red     { background: linear-gradient(135deg,#ef4444,#b91c1c); color:#fff; }
  .small-box.gradient-purple  { background: linear-gradient(135deg,#a855f7,#6d28d9); color:#fff; }
  .small-box.gradient-amber   { background: linear-gradient(135deg,#f59e0b,#b45309); color:#fff; }
  .small-box.gradient-teal    { background: linear-gradient(135deg,#14b8a6,#0f766e); color:#fff; }
  .small-box.gradient-rose    { background: linear-gradient(135deg,#fb7185,#be123c); color:#fff; }
  .small-box.gradient-sky     { background: linear-gradient(135deg,#38bdf8,#0369a1); color:#fff; }
  .small-box .inner h3 { font-weight:800; }
  .chart-h-280 { position: relative; height: 280px; }
  .chart-h-280 canvas { width:100%!important; height:100%!important; }
</style>
@endpush

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-8">
          <h3 class="mb-0">{{ $stats['header_title'] ?? 'Dashboard' }}</h3>
          <div class="text-muted">Admin overview — {{ $school->short_name ?? $school->name }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      {{-- KPI cards --}}
      <div class="row g-3">
        <div class="col-lg-3 col-6">
          <div class="small-box gradient-amber">
            <div class="inner">
              <h3>{{ $stats['admins'] ?? 0 }}</h3>
              <p>Admins</p>
            </div>
            <i class="small-box-icon bi bi-shield-lock-fill"></i>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box gradient-teal">
            <div class="inner">
              <h3>{{ $stats['teachers'] ?? 0 }}</h3>
              <p>Teachers</p>
            </div>
            <i class="small-box-icon bi bi-mortarboard-fill"></i>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box gradient-sky">
            <div class="inner">
              <h3>{{ $stats['students'] ?? 0 }}</h3>
              <p>Students</p>
            </div>
            <i class="small-box-icon bi bi-journal-text"></i>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box gradient-rose">
            <div class="inner">
              <h3>{{ $stats['parents'] ?? 0 }}</h3>
              <p>Parents</p>
            </div>
            <i class="small-box-icon bi bi-person-heart"></i>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box gradient-green">
            <div class="inner">
              <h3>{{ $stats['exams_upcoming_30'] ?? 0 }}</h3>
              <p>Exams (Next 30d)</p>
            </div>
            <i class="small-box-icon bi bi-calendar2-check"></i>
          </div>
        </div>
      </div>

      {{-- Users by Role chart --}}
      <div class="row g-3 mb-5">
        <div class="col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h3 class="card-title mb-0">Users by Role</h3>
              <span class="badge text-bg-light">Overview</span>
            </div>
            <div class="card-body">
              <div class="chart-h-280">
                <canvas id="rolesBar"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Recent Users table (optional) --}}
      @isset($recentUsers)
        <div class="card shadow-sm mt-3">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Recent Users</h3>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th>Last Login</th>
                  </tr>
                </thead>
                <tbody>
                    @forelse($recentUsers as $u)
                    <tr>
                    <td>{{ $u->name }} {{ $u->last_name }}</td>
                    <td class="text-capitalize">{{ $u->role }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ optional($u->created_at)->format('d M Y') }}</td>
                    <td>
                        @if(!empty($u->last_login_at))
                        {{ \Carbon\Carbon::parse($u->last_login_at)->format('d M Y, h:i A') }}
                        @else
                        <span class="text-muted">Never</span>
                        @endif
                    </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted p-4">No recent users.</td></tr>
                @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endisset

    </div>
  </div>
</main>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  const rolesBarEl = document.getElementById('rolesBar');
  if (rolesBarEl) {
    new Chart(rolesBarEl, {
      type: 'bar',
      data: {
        labels: ['Admins', 'Teachers', 'Students', 'Parents'],
        datasets: [{
          label: 'Users',
          data: [
            {{ $stats['admins'] ?? 0 }},
            {{ $stats['teachers'] ?? 0 }},
            {{ $stats['students'] ?? 0 }},
            {{ $stats['parents'] ?? 0 }}
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }
</script>
@endpush
