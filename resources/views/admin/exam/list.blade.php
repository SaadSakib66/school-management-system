@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Exam Table</h3></div>
        <div class="col-sm-6">
          <a href="{{ route('admin.exam.add') }}" class="btn btn-primary float-sm-end">Add Exam</a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          {{-- Filters --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header">
              <h3 class="card-title">Filter Exams</h3>
            </div>
            <div class="card-body">
              <form method="GET" action="{{ route('admin.exam.list') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                  <label class="form-label">Exam Name / Note</label>
                  <input type="text" name="name" class="form-control" value="{{ old('name', $name ?? request('name')) }}" placeholder="Search by name or note">
                </div>

                <div class="col-md-4">
                  <label class="form-label">Created By</label>
                  <input type="text" name="created_by" class="form-control" value="{{ old('created_by', $created_by ?? request('created_by')) }}" placeholder="Creator name">
                </div>

                <div class="col-md-2">
                  <label class="form-label">Per Page</label>
                  <select name="per_page" class="form-select">
                    @foreach([10,15,20,30,50,100] as $pp)
                      <option value="{{ $pp }}" {{ (int)($per_page ?? request('per_page', 15)) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                  <button type="submit" class="btn btn-primary w-100">Search</button>
                  <a href="{{ route('admin.exam.list') }}" class="btn btn-success w-100">Reset</a>
                </div>
              </form>
            </div>
          </div>

          {{-- List --}}
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title">Exam List</h3>
            </div>

            <div class="card-body p-0">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th style="width:80px">Serial</th>
                    <th>Name</th>
                    <th>Note</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th style="width:160px">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($getRecord as $value)
                    <tr>
                      <td>{{ ($getRecord->currentPage()-1)*$getRecord->perPage() + $loop->iteration }}</td>
                      <td>{{ $value->name }}</td>
                      <td>{{ $value->note }}</td>
                      <td>{{ $value->created_by_name ?: '-' }}</td>
                      <td>{{ optional($value->created_at)->format('d M Y') }}</td>
                      <td>
                        <a href="{{ route('admin.exam.edit', $value->id) }}" class="btn btn-success btn-sm">Edit</a>
                        <form action="{{ route('admin.exam.delete') }}" method="POST" style="display:inline;">
                          @csrf
                          <input type="hidden" name="id" value="{{ $value->id }}">
                          <button type="submit" class="btn btn-danger btn-sm"
                            onclick="return confirm('Are you sure you want to delete this exam?')">
                            Delete
                          </button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="text-center p-4">No exams found.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>

              <div>
                <p class="text-center mt-3">
                  Showing {{ $getRecord->count() }} of {{ $getRecord->total() }} records
                </p>
              </div>

              {{ $getRecord->links('pagination::bootstrap-5') }}
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>

@endsection
