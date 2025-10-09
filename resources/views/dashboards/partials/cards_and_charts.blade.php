@php
  // Inputs:
  // $stats (array) — required
  // $school, $recentUsers, $extras — optional

  $schools_total    = $stats['schools_total']    ?? 0;
  $schools_active   = $stats['schools_active']   ?? 0;
  $schools_inactive = $stats['schools_inactive'] ?? 0;

  $users_total = $stats['users_total'] ?? 0;
  $admins      = $stats['admins']      ?? 0;
  $teachers    = $stats['teachers']    ?? 0;
  $students    = $stats['students']    ?? 0;
  $parents     = $stats['parents']     ?? 0;

  $classes_total     = $stats['classes_total']     ?? 0;
  $subjects_total    = $stats['subjects_total']    ?? 0;
  $homeworks_total   = $stats['homeworks_total']   ?? 0;
  $exams_upcoming_30 = $stats['exams_upcoming_30'] ?? 0;
  $attendance_today  = $stats['attendance_today']  ?? 0;

  $contextSchool    = $stats['school'] ?? ($school ?? null);
@endphp

{{-- ===== Summary row ===== --}}
<div class="row g-3">
  <div class="col-lg-3 col-6">
    <div class="small-box gradient-blue">
      <div class="inner">
        <h3>{{ $schools_total }}</h3>
        <p>{{ $contextSchool ? ($contextSchool->short_name ?? $contextSchool->name) : 'This School' }}</p>
      </div>
      <i class="small-box-icon bi bi-building"></i>
      <span class="small-box-footer">&nbsp;</span>
    </div>
  </div>

  <div class="col-lg-3 col-6">
    <div class="small-box gradient-green">
      <div class="inner">
        <h3>{{ $schools_active }}</h3>
        <p>Active</p>
      </div>
      <i class="small-box-icon bi bi-check2-circle"></i>
      <span class="small-box-footer">&nbsp;</span>
    </div>
  </div>

  <div class="col-lg-3 col-6">
    <div class="small-box gradient-red">
      <div class="inner">
        <h3>{{ $schools_inactive }}</h3>
        <p>Inactive</p>
      </div>
      <i class="small-box-icon bi bi-slash-circle"></i>
      <span class="small-box-footer">&nbsp;</span>
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

{{-- ===== Roles row ===== --}}
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

{{-- ===== Academic row ===== --}}
<div class="row g-3">
  <div class="col-lg-3 col-6">
    <div class="small-box gradient-blue">
      <div class="inner"><h3>{{ $classes_total }}</h3><p>Classes</p></div>
      <i class="small-box-icon bi bi-grid-3x3-gap-fill"></i>
      <span class="small-box-footer">&nbsp;</span>
    </div>
  </div>

  <div class="col-lg-3 col-6">
    <div class="small-box gradient-purple">
      <div class="inner"><h3>{{ $subjects_total }}</h3><p>Subjects</p></div>
      <i class="small-box-icon bi bi-book-half"></i>
      <span class="small-box-footer">&nbsp;</span>
    </div>
  </div>

  <div class="col-lg-3 col-6">
    <div class="small-box gradient-green">
      <div class="inner"><h3>{{ $exams_upcoming_30 }}</h3><p>Exams (Next 30d)</p></div>
      <i class="small-box-icon bi bi-calendar2-check"></i>
      <span class="small-box-footer">&nbsp;</span>
    </div>
  </div>

  <div class="col-lg-3 col-6">
    <div class="small-box gradient-amber">
      <div class="inner"><h3>{{ $homeworks_total }}</h3><p>Homeworks</p></div>
      <i class="small-box-icon bi bi-list-task"></i>
      <span class="small-box-footer">&nbsp;</span>
    </div>
  </div>
</div>

{{-- ===== Charts row ===== --}}
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

{{-- ===== Role-specific EXTRAS (optional small cards/tables) ===== --}}
@if(!empty($extras))
  <div class="row g-3 mb-4">
    @if(isset($extras['assignedClasses']))
      <div class="col-lg-3 col-6">
        <div class="small-box gradient-teal">
          <div class="inner"><h3>{{ $extras['assignedClasses'] }}</h3><p>My Assigned Classes</p></div>
          <i class="small-box-icon bi bi-person-lines-fill"></i>
          <span class="small-box-footer">&nbsp;</span>
        </div>
      </div>
    @endif

    @if(isset($extras['pendingHomework']))
      <div class="col-lg-3 col-6">
        <div class="small-box gradient-rose">
          <div class="inner"><h3>{{ $extras['pendingHomework'] }}</h3><p>Pending Homework</p></div>
          <i class="small-box-icon bi bi-hourglass-split"></i>
          <span class="small-box-footer">&nbsp;</span>
        </div>
      </div>
    @endif

    @if(isset($extras['mySubjects']))
      <div class="col-lg-3 col-6">
        <div class="small-box gradient-sky">
          <div class="inner"><h3>{{ $extras['mySubjects'] }}</h3><p>My Subjects</p></div>
          <i class="small-box-icon bi bi-collection"></i>
          <span class="small-box-footer">&nbsp;</span>
        </div>
      </div>
    @endif

    @if(isset($extras['attendanceRate']))
      <div class="col-lg-3 col-6">
        <div class="small-box gradient-green">
          <div class="inner"><h3>{{ $extras['attendanceRate'] ?? 0 }}%</h3><p>My Attendance Rate</p></div>
          <i class="small-box-icon bi bi-activity"></i>
          <span class="small-box-footer">&nbsp;</span>
        </div>
      </div>
    @endif

    @if(isset($extras['childrenCount']))
      <div class="col-lg-3 col-6">
        <div class="small-box gradient-amber">
          <div class="inner"><h3>{{ $extras['childrenCount'] }}</h3><p>My Children</p></div>
          <i class="small-box-icon bi bi-people"></i>
          <span class="small-box-footer">&nbsp;</span>
        </div>
      </div>
    @endif

    @if(isset($extras['quick_links']) && is_array($extras['quick_links']) && count($extras['quick_links']))
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header"><h3 class="card-title mb-0">Quick Links</h3></div>
          <div class="card-body">
            @foreach($extras['quick_links'] as $link)
              <a href="{{ $link['route'] ?? '#' }}" class="btn btn-sm btn-outline-primary me-2 mb-2">{{ $link['label'] }}</a>
            @endforeach
          </div>
        </div>
      </div>
    @endif
  </div>
@endif

{{-- ===== Recent users (optional) ===== --}}
@if(isset($recentUsers))
  <div class="card shadow-sm">
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
            </tr>
          </thead>
          <tbody>
            @forelse($recentUsers as $u)
              <tr>
                <td>{{ $u->name }} {{ $u->last_name }}</td>
                <td class="text-capitalize">{{ $u->role }}</td>
                <td>{{ $u->email }}</td>
                <td>{{ optional($u->created_at)->format('d M Y') }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-center text-muted p-4">No recent users.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endif

@push('scripts')
  @once
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  @endonce
  <script>
    // Donut (schools)
    (function(){
      const el = document.getElementById('schoolsPie');
      if (!el) return;
      new Chart(el, {
        type: 'doughnut',
        data: {
          labels: ['Active', 'Inactive'],
          datasets: [{ data: [{{ $schools_active }}, {{ $schools_inactive }}] }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom' } },
          cutout: '60%'
        }
      });
    })();

    // Bar (roles)
    (function(){
      const el = document.getElementById('rolesBar');
      if (!el) return;
      new Chart(el, {
        type: 'bar',
        data: {
          labels: ['Admins', 'Teachers', 'Students', 'Parents'],
          datasets: [{ label: 'Users', data: [{{ $admins }}, {{ $teachers }}, {{ $students }}, {{ $parents }}] }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
      });
    })();
  </script>
@endpush
