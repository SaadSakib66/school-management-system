@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col"><h3 class="mb-0">Fee Components</h3></div>
        <div class="col text-end">
          <a href="{{ route('admin.fees.components.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Component
          </a>
        </div>
      </div>
      <form class="mt-3" method="get" action="{{ route('admin.fees.components.index') }}">
        <div class="input-group">
          <input type="text" class="form-control" name="q" value="{{ $q }}" placeholder="Search by name...">
          <button class="btn btn-outline-secondary">Search</button>
        </div>
      </form>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      @include('admin.message')

      <div class="card card-primary card-outline">
        <div class="table-responsive">
          <table class="table table-hover table-bordered mb-0">
            <thead>
              <tr>
                <th>Name</th>
                <th>Frequency</th>
                <th>Calc Type</th>
                <th class="text-end">Default Amount</th>
                <th>Status</th>
                <th style="width:220px">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($components as $c)
                <tr>
                  <td>{{ $c->name }}</td>
                  <td class="text-nowrap">{{ ucfirst(str_replace('_',' ',$c->frequency)) }}</td>
                  <td>{{ $c->calc_type === 'fixed' ? 'Fixed' : '% of base' }}</td>
                  <td class="text-end">{{ $c->default_amount !== null ? number_format($c->default_amount,2) : '-' }}</td>
                  <td>
                    <span class="badge {{ $c->status?'bg-success':'bg-secondary' }}">{{ $c->status ? 'Active' : 'Inactive' }}</span>
                  </td>
                  <td>
                    <a href="{{ route('admin.fees.components.edit',$c->id) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('admin.fees.components.toggle',$c->id) }}" method="post" class="d-inline">
                      @csrf @method('PATCH')
                      <button class="btn btn-sm btn-outline-info">{{ $c->status?'Deactivate':'Activate' }}</button>
                    </form>
                    <form action="{{ route('admin.fees.components.destroy',$c->id) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this component?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted p-4">No components.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          {{ $components->links() }}
        </div>
      </div>

    </div>
  </div>
</main>
@endsection
