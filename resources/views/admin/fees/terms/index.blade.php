@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col"><h3 class="mb-0">Fee Terms</h3></div>
        <div class="col text-end">
          <a href="{{ route('admin.fees.terms.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Term
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      @include('admin.message')

      {{-- Validation errors --}}
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="card mb-3">
        <div class="card-body">
          <form class="row g-2" method="get">
            <div class="col-sm-3">
              <input type="text" class="form-control" name="year" value="{{ request('year') }}" placeholder="Academic year (e.g. 2025-2026)">
            </div>
            <div class="col-sm-3">
              <input type="text" class="form-control" name="name" value="{{ request('name') }}" placeholder="Term name">
            </div>
            <div class="col-sm-3">
              <select name="status" class="form-select">
                <option value="">-- Any status --</option>
                <option value="1" {{ request('status')==='1'?'selected':'' }}>Active</option>
                <option value="0" {{ request('status')==='0'?'selected':'' }}>Inactive</option>
              </select>
            </div>
            <div class="col-sm-3 d-grid d-sm-flex gap-2">
              <button class="btn btn-secondary"><i class="bi bi-search"></i> Filter</button>
              <a href="{{ route('admin.fees.terms.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Academic Year</th>
                <th>Term</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($terms as $t)
                <tr>
                  <td>{{ $t->id }}</td>
                  <td>{{ $t->academic_year }}</td>
                  <td>{{ $t->name }}</td>
                  <td>{{ $t->start_date?->format('d/m/Y') }}</td>
                  <td>{{ $t->end_date?->format('d/m/Y') }}</td>
                  <td>
                    @if($t->status)
                      <span class="badge bg-success">Active</span>
                    @else
                      <span class="badge bg-secondary">Inactive</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <a href="{{ route('admin.fees.terms.edit', $t->id) }}" class="btn btn-sm btn-warning">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form action="{{ route('admin.fees.terms.destroy', $t->id) }}" method="post" class="d-inline"
                          onsubmit="return confirm('Delete this term?');">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-sm btn-danger">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted">No terms found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          {{ $terms->links() }}
        </div>
      </div>

    </div>
  </div>
</main>
@endsection
