@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Class Table</h3></div>
        <div class="col-sm-6">
          <a href="{{ route('admin.class.add') }}" class="btn btn-primary float-sm-end">Add Class</a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h3 class="card-title mb-0">Class List</h3>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.class.list') }}" class="border-bottom bg-light p-3">
              <div class="row g-2 align-items-end">
                <div class="col-md-4">
                  <label class="form-label mb-1">Class Name</label>
                  <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="Search class name">
                </div>
                <div class="col-md-3">
                  <label class="form-label mb-1">Status</label>
                  <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="1" {{ request('status')==='1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status')==='0' ? 'selected' : '' }}>Inactive</option>
                  </select>
                </div>
                <div class="col-md-5 text-md-end mt-2 mt-md-0">
                  <button type="submit" class="btn btn-primary me-2">Filter</button>
                  <a href="{{ route('admin.class.list') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
              </div>
            </form>

            <div class="card-body p-0">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Serial</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($getRecord as $value)
                    <tr>
                      {{-- Serial across pages --}}
                      <td>{{ ($getRecord->currentPage() - 1) * $getRecord->perPage() + $loop->iteration }}</td>

                      <td>{{ $value->name }}</td>

                      <td>
                        @if($value->status == 1)
                          <span class="badge bg-success">Active</span>
                        @else
                          <span class="badge bg-secondary">Inactive</span>
                        @endif
                      </td>

                      {{-- From eager loaded relation --}}
                      <td>
                        @if($value->creator)
                            {{ trim($value->creator->name . ' ' . ($value->creator->last_name ?? '')) }}
                        @endif
                      </td>

                      <td>{{ optional($value->created_at)->format('d M Y') }}</td>

                      <td class="d-flex gap-1">
                        <a href="{{ route('admin.class.edit-class', $value->id) }}" class="btn btn-success btn-sm">Edit</a>

                        <form action="{{ route('admin.class.delete-class') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this Class?')">
                          @csrf
                          <input type="hidden" name="id" value="{{ $value->id }}">
                          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="text-center text-muted py-4">No classes found.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>

              <div class="px-3 pb-3">
                <p class="text-center mt-3 mb-2">
                  Showing {{ $getRecord->count() }} of {{ $getRecord->total() }} records
                </p>
                {{ $getRecord->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
              </div>
            </div>

          </div>

        </div>
      </div>

    </div>
  </div>
</main>

@endsection
