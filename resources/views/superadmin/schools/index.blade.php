@extends('admin.layout.layout')

@push('styles')
<style>
  /* mini stat cards */
  .stat-card {
    border: 0; border-radius: 1rem; color:#fff;
    padding: 1rem 1.25rem;
  }
  .bg-grad-blue  { background: linear-gradient(135deg,#3b82f6,#1d4ed8); }
  .bg-grad-green { background: linear-gradient(135deg,#22c55e,#15803d); }
  .bg-grad-gray  { background: linear-gradient(135deg,#475569,#1f2937); }

  .stat-card .value { font-size: 1.75rem; font-weight: 800; line-height: 1; }
  .stat-card .label { opacity: .9; }

  .badge-status { font-size: .75rem; padding:.35rem .5rem; border-radius:9999px; }
  .badge-active { background:#dcfce7; color:#166534; }
  .badge-inactive { background:#fee2e2; color:#991b1b; }

  .table thead th { font-weight: 600; }
  .toolbar .form-control, .toolbar .form-select { max-width: 240px; }
  @media (max-width: 576px) { .toolbar .form-control, .toolbar .form-select { max-width: 100%; } }
</style>
@endpush

@section('content')
<div class="container py-4">

  {{-- Page header --}}
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Schools</h3>
      <div class="text-muted">Manage all schools from a single place</div>
    </div>
    <a href="{{ route('superadmin.schools.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-lg me-1"></i> Add School
    </a>
  </div>

  {{-- Flash --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  {{-- Quick stats --}}
  @php
    $total    = $total    ?? ($schools->total() ?? 0);
    $active   = $active   ?? \App\Models\School::where('status',1)->count();
    $inactive = $inactive ?? \App\Models\School::where('status',0)->count();
  @endphp
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
      <div class="stat-card bg-grad-blue shadow-sm">
        <div class="value">{{ $total }}</div>
        <div class="label">Total Schools</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="stat-card bg-grad-green shadow-sm">
        <div class="value">{{ $active }}</div>
        <div class="label">Active</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="stat-card bg-grad-gray shadow-sm">
        <div class="value">{{ $inactive }}</div>
        <div class="label">Inactive</div>
      </div>
    </div>
  </div>

  {{-- Toolbar: search + filter --}}
<form method="GET" action="{{ route('superadmin.schools.index') }}" class="toolbar d-flex flex-wrap gap-2 align-items-center mb-3">
  <div class="input-group" style="max-width:480px;">
    <span class="input-group-text"><i class="bi bi-search"></i></span>
    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by name, short code, or email">
  </div>

  <select name="status" class="form-select">
    <option value="">All Status</option>
    <option value="1" @selected(request('status')==='1')>Active</option>
    <option value="0" @selected(request('status')==='0')>Inactive</option>
  </select>

  <button class="btn btn-outline-secondary"><i class="bi bi-funnel me-1"></i>Filter</button>
  @if(request()->hasAny(['q','status']))
    <a href="{{ route('superadmin.schools.index') }}" class="btn btn-link">Reset</a>
  @endif
</form>


  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Short</th>
              <th>EIIN Number</th>
              <th>Email</th>
              <th>Status</th>
              <th class="text-end" style="width:320px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($schools as $s)
              <tr>
                <td>
                  <div class="fw-semibold">{{ $s->name }}</div>
                  @if($s->address)
                    <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ $s->address }}</div>
                  @endif
                </td>
                <td class="text-muted">{{ $s->short_name ?: '—' }}</td>
                <td class="text-muted">{{ $s->eiin_num ?: '—' }}</td>
                <td>
                  @if($s->email)
                    <a class="text-decoration-none" href="mailto:{{ $s->email }}">{{ $s->email }}</a>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @if((int)$s->status === 1)
                    <span class="badge-status badge-active">Active</span>
                  @else
                    <span class="badge-status badge-inactive">Inactive</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="btn-group" role="group" aria-label="Actions">
                    {{-- Act as --}}
                    <form method="POST" action="{{ route('superadmin.schools.switch.set') }}">
                      @csrf
                      <input type="hidden" name="school_id" value="{{ $s->id }}">
                      <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Act as this school">
                        <i class="bi bi-box-arrow-in-right"></i> Act As
                      </button>
                    </form>

                    {{-- Edit --}}
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('superadmin.schools.edit', $s) }}" data-bs-toggle="tooltip" title="Edit">
                      <i class="bi bi-pencil-square"></i> Edit
                    </a>

                    {{-- Toggle status --}}
                    <form method="POST" action="{{ route('superadmin.schools.toggle', $s) }}">
                      @csrf @method('PATCH')
                      <button class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Toggle status">
                        <i class="bi bi-shuffle"></i> Toggle
                      </button>
                    </form>

                    {{-- Delete --}}
                    <form method="POST" action="{{ route('superadmin.schools.destroy', $s) }}"
                          onsubmit="return confirm('Delete this school? This cannot be undone.');">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete">
                        <i class="bi bi-trash"></i> Delete
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-5">
                  <i class="bi bi-inboxes me-2"></i>No schools found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if(method_exists($schools,'links'))
      <div class="card-footer bg-body-tertiary">
        <div class="d-flex justify-content-between align-items-center">
          <div class="small text-muted">
            Showing {{ $schools->firstItem() }}–{{ $schools->lastItem() }} of {{ $schools->total() }}
          </div>
          {{ $schools->withQueryString()->links() }}
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
  // enable tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
</script>
@endpush
