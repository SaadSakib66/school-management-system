@extends('admin.layout.layout')

@push('styles')
<style>
  /* ---- Gradient summary cards ---- */
  .small-box.gradient-blue    { background: linear-gradient(135deg,#3b82f6,#1d4ed8); color:#fff; }
  .small-box.gradient-green   { background: linear-gradient(135deg,#22c55e,#15803d); color:#fff; }
  .small-box.gradient-red     { background: linear-gradient(135deg,#ef4444,#b91c1c); color:#fff; }
  .small-box.gradient-purple  { background: linear-gradient(135deg,#a855f7,#6d28d9); color:#fff; }
  .small-box.gradient-amber   { background: linear-gradient(135deg,#f59e0b,#b45309); color:#fff; }
  .small-box.gradient-teal    { background: linear-gradient(135deg,#14b8a6,#0f766e); color:#fff; }
  .small-box.gradient-rose    { background: linear-gradient(135deg,#fb7185,#be123c); color:#fff; }
  .small-box.gradient-sky     { background: linear-gradient(135deg,#38bdf8,#0369a1); color:#fff; }
  .small-box .inner h3 { font-weight:800; }

  /* ---- Chart sizing (both charts same height) ---- */
  .chart-h-280 { position: relative; height: 280px; }
  .chart-h-280 canvas { width: 100% !important; height: 100% !important; }

  /* ---- Status pills ---- */
  .status-badge { font-size:.75rem; padding:.35rem .5rem; border-radius:9999px; }
  .status-active { background:#dcfce7; color:#166534; }
  .status-inactive { background:#fee2e2; color:#991b1b; }
</style>
@endpush

@section('content')
<main class="app-main">
  <!-- Page header -->
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-8">
          <h3 class="mb-0">Global Dashboard</h3>
          <div class="text-muted">Super Admin overview across all schools</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Page body -->
  <div class="app-content">
    <div class="container-fluid">

      @php
        // Fallbacks if controller didn't pass data yet
        $stats = $stats ?? [];
        $schools_total    = $stats['schools_total']    ?? 0;
        $schools_active   = $stats['schools_active']   ?? 0;
        $schools_inactive = $stats['schools_inactive'] ?? 0;
        $users_total      = $stats['users_total']      ?? 0;
        $admins           = $stats['admins']           ?? 0;
        $teachers         = $stats['teachers']         ?? 0;
        $students         = $stats['students']         ?? 0;
        $parents          = $stats['parents']          ?? 0;

        $allSchools    = $allSchools    ?? \App\Models\School::orderBy('name')->get();
        $recentSchools = $recentSchools ?? \App\Models\School::latest()->take(8)->get();
      @endphp

      {{-- Summary row --}}
      <div class="row g-3">
        <div class="col-lg-3 col-6">
          <div class="small-box gradient-blue">
            <div class="inner">
              <h3>{{ $schools_total }}</h3>
              <p>Total Schools</p>
            </div>
            <i class="small-box-icon bi bi-building"></i>
            <a href="{{ route('superadmin.schools.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
              Manage Schools <i class="bi bi-link-45deg"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box gradient-green">
            <div class="inner">
              <h3>{{ $schools_active }}</h3>
              <p>Active Schools</p>
            </div>
            <i class="small-box-icon bi bi-check2-circle"></i>
            <a href="{{ route('superadmin.schools.index') }}?status=1" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
              View Active <i class="bi bi-link-45deg"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box gradient-red">
            <div class="inner">
              <h3>{{ $schools_inactive }}</h3>
              <p>Inactive Schools</p>
            </div>
            <i class="small-box-icon bi bi-slash-circle"></i>
            <a href="{{ route('superadmin.schools.index') }}?status=0" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
              View Inactive <i class="bi bi-link-45deg"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box gradient-purple">
            <div class="inner">
              <h3>{{ $users_total }}</h3>
              <p>Total Users</p>
            </div>
            <i class="small-box-icon bi bi-people-fill"></i>
            <span class="small-box-footer">&nbsp;</span>
          </div>
        </div>
      </div>

      {{-- Roles row --}}
      <div class="row g-3">
        <div class="col-lg-3 col-6">
          <div class="small-box gradient-amber">
            <div class="inner"><h3>{{ $admins }}</h3><p>Admins</p></div>
            <i class="small-box-icon bi bi-shield-lock-fill"></i>
            <span class="small-box-footer">&nbsp;</span>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box gradient-teal">
            <div class="inner"><h3>{{ $teachers }}</h3><p>Teachers</p></div>
            <i class="small-box-icon bi bi-mortarboard-fill"></i>
            <span class="small-box-footer">&nbsp;</span>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box gradient-sky">
            <div class="inner"><h3>{{ $students }}</h3><p>Students</p></div>
            <i class="small-box-icon bi bi-journal-text"></i>
            <span class="small-box-footer">&nbsp;</span>
          </div>
        </div>

        <div class="col-lg-3 col-6">
          <div class="small-box gradient-rose">
            <div class="inner"><h3>{{ $parents }}</h3><p>Parents</p></div>
            <i class="small-box-icon bi bi-person-heart"></i>
            <span class="small-box-footer">&nbsp;</span>
          </div>
        </div>
      </div>

      {{-- Charts row (both fixed height) --}}
      <div class="row g-3 mb-5">
        <div class="col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h3 class="card-title mb-0">School Status</h3>
              <span class="badge text-bg-light">Active vs Inactive</span>
            </div>
            <div class="card-body">
              <div class="chart-h-280">
                <canvas id="schoolsPie"></canvas>
              </div>
            </div>
          </div>
        </div>

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

      {{-- Quick switch + Recent schools --}}
      <div class="row g-3">
        <div class="col-lg-4">
          <div class="card shadow-sm">
            <div class="card-header">
              <h3 class="card-title mb-0"><i class="bi bi-arrow-left-right me-2"></i>Quick School Switch</h3>
            </div>
            <div class="card-body">
              <form method="POST" action="{{ route('superadmin.schools.switch.set') }}" class="d-grid gap-2 mb-2">
                @csrf
                <label class="form-label">Select a school to act as:</label>
                <select name="school_id" class="form-select">
                  <option value="">— Choose a school —</option>
                  @foreach($allSchools as $s)
                    <option value="{{ $s->id }}">{{ $s->short_name ?? $s->name }}{{ $s->status ? '' : ' (INACTIVE)' }}</option>
                  @endforeach
                </select>
                <button type="submit" class="btn btn-primary w-100">Switch</button>
              </form>

              <form method="POST" action="{{ route('superadmin.schools.switch.clear') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary w-100">Clear Context</button>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h3 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Recent Schools</h3>
              <a href="{{ route('superadmin.schools.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead class="table-light">
                    <tr>
                      <th style="width:40%">Name</th>
                      <th>Short</th>
                      <th>Status</th>
                      <th style="width:18%">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($recentSchools as $school)
                      <tr>
                        <td>{{ $school->name }}</td>
                        <td>{{ $school->short_name ?? '—' }}</td>
                        <td>
                          @if((int)$school->status === 1)
                            <span class="status-badge status-active">Active</span>
                          @else
                            <span class="status-badge status-inactive">Inactive</span>
                          @endif
                        </td>
                        <td class="text-nowrap">
                          <form method="POST" action="{{ route('superadmin.schools.switch.set') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="school_id" value="{{ $school->id }}">
                            <button class="btn btn-sm btn-primary">
                              <i class="bi bi-box-arrow-in-right"></i> Act As
                            </button>
                          </form>
                          <a href="{{ route('superadmin.schools.edit', $school->id) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil-square"></i>
                          </a>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="4" class="text-center text-muted p-4">No schools found.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /row -->

    </div>
  </div>
</main>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  // Donut (schools)
  const schoolsPieEl = document.getElementById('schoolsPie');
  if (schoolsPieEl) {
    new Chart(schoolsPieEl, {
      type: 'doughnut',
      data: {
        labels: ['Active', 'Inactive'],
        datasets: [{ data: [{{ $schools_active }}, {{ $schools_inactive }}] }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false, // respect .chart-h-280 height
        plugins: { legend: { position: 'bottom' } },
        cutout: '60%'
      }
    });
  }

  // Bar (roles)
  const rolesBarEl = document.getElementById('rolesBar');
  if (rolesBarEl) {
    new Chart(rolesBarEl, {
      type: 'bar',
      data: {
        labels: ['Admins', 'Teachers', 'Students', 'Parents'],
        datasets: [{ label: 'Users', data: [{{ $admins }}, {{ $teachers }}, {{ $students }}, {{ $parents }}] }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false, // respect .chart-h-280 height
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }
</script>
@endpush
