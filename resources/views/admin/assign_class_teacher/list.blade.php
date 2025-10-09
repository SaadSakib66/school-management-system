{{-- @extends('admin.layout.layout')
@section('content')

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">Assign Class Teacher Table</h3></div>
                <div class="col-sm-6">
                    <a href="{{ route('admin.assign-class-teacher.add') }}" class="btn btn-primary float-sm-end">
                        Add Assign Teacher
                    </a>
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
                        <div class="card-header">
                            <h3 class="card-title">Assigned Class Teacher List</h3>
                        </div>

                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Serial</th>
                                        <th>Class Name</th>
                                        <th>Teacher Name</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th>Created Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($getRecord as $value)
                                        <tr>

                                            <td>{{ ($getRecord->currentPage() - 1) * $getRecord->perPage() + $loop->iteration }}</td>
                                            <td>{{ $value->class_name }}</td>
                                            <td>{{ $value->teacher_name }}</td>
                                            <td>{{ $value->status == 1 ? 'Active' : 'Inactive' }}</td>
                                            <td>{{ $value->created_by_name }}</td>
                                            <td>{{ date('d M Y', strtotime($value->created_at)) }}</td>

                                            <td>
                                                <a href="{{ route('admin.assign-class-teacher.edit_teacher', ['id' => $value->id]) }}"
                                                   class="btn btn-success btn-sm">
                                                    Edit
                                                </a>
                                                <a href="{{ route('admin.assign-class-teacher.edit-single-teacher', ['id' => $value->id]) }}"
                                                   class="btn btn-success btn-sm">
                                                    Edit Single Teacher
                                                </a>
                                                <form action="{{ route('admin.assign-class-teacher.delete_teacher') }}"
                                                      method="POST" style="display:inline;">
                                                    @csrf

                                                    <input type="hidden" name="id" value="{{ $value->id }}">
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Are you sure you want to delete this assignment?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center p-4">No records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="p-3">
                            <p class="text-center mb-2">
                                Showing {{ $getRecord->count() }} of {{ $getRecord->total() }} records
                            </p>
                            {{ $getRecord->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
</main>

@endsection --}}
@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Assign Class Teacher Table</h3></div>
        <div class="col-sm-6">
          <a href="{{ route('admin.assign-class-teacher.add') }}" class="btn btn-primary float-sm-end">
            Add Assign Teacher
          </a>
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
            <div class="card-header">
              <h3 class="card-title">Assigned Class Teacher List</h3>
            </div>

            {{-- 🔎 Filters --}}
            <div class="p-3 pb-0">
              <form method="GET" action="{{ route('admin.assign-class-teacher.list') }}" id="filterForm" class="mb-2">
                <input type="hidden" name="did_search" value="1">
                <div class="row g-2 align-items-end">

                  <div class="col-md-3">
                    <label class="form-label mb-1">Class</label>
                    <select name="class_id" class="form-select">
                      <option value="">All</option>
                      @foreach($getClass as $c)
                        <option value="{{ $c->id }}" {{ request('class_id')==$c->id ? 'selected' : '' }}>
                          {{ $c->name }}
                        </option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label mb-1">Teacher</label>
                    <select name="teacher_id" class="form-select">
                      <option value="">All</option>
                      @foreach($getTeachers as $t)
                        <option value="{{ $t->id }}" {{ request('teacher_id')==$t->id ? 'selected' : '' }}>
                          {{ $t->name }}
                        </option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select">
                      <option value="">All</option>
                      <option value="1" {{ request('status')==='1' ? 'selected':'' }}>Active</option>
                      <option value="0" {{ request('status')==='0' ? 'selected':'' }}>Inactive</option>
                    </select>
                  </div>

                  <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="{{ route('admin.assign-class-teacher.list') }}" class="btn btn-secondary">Reset</a>

                    {{-- Download requires a prior Search (did_search=1) --}}
                    @if(request('did_search')==='1')
                      <a href="{{ route('admin.assign-class-teacher.download', request()->query()) }}"
                         target="_blank" rel="noopener"
                         class="btn btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Download
                      </a>
                    @else
                      <button type="button" class="btn btn-outline-danger"
                              onclick="alert('Please click Search after setting filters, then press Download.');">
                        <i class="bi bi-file-earmark-pdf-fill"></i> Download
                      </button>
                    @endif
                  </div>

                </div>
              </form>
            </div>

            {{-- Table --}}
            <div class="card-body p-0">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Serial</th>
                    <th>Class Name</th>
                    <th>Teacher Name</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($getRecord as $value)
                    <tr>
                      <td>{{ ($getRecord->currentPage() - 1) * $getRecord->perPage() + $loop->iteration }}</td>
                      <td>{{ $value->class_name }}</td>
                      <td>{{ $value->teacher_name }}</td>
                      <td>{{ $value->status == 1 ? 'Active' : 'Inactive' }}</td>
                      <td>{{ $value->created_by_name }}</td>
                      <td>{{ date('d M Y', strtotime($value->created_at)) }}</td>
                      <td>
                        <a href="{{ route('admin.assign-class-teacher.edit_teacher', ['id' => $value->id]) }}" class="btn btn-success btn-sm">Edit</a>
                        <a href="{{ route('admin.assign-class-teacher.edit-single-teacher', ['id' => $value->id]) }}" class="btn btn-success btn-sm">Edit Single Teacher</a>
                        <form action="{{ route('admin.assign-class-teacher.delete_teacher') }}" method="POST" style="display:inline;">
                          @csrf
                          <input type="hidden" name="id" value="{{ $value->id }}">
                          <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this assignment?')">Delete</button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="7" class="text-center p-4">No records found.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div class="p-3">
              <p class="text-center mb-2">Showing {{ $getRecord->count() }} of {{ $getRecord->total() }} records</p>
              {{ $getRecord->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
            </div>

          </div>

        </div>
      </div>
    </div>
  </div>
</main>

@endsection

