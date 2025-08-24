@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Marks Grade Table</h3></div>
        <div class="col-sm-6">
          <a href="{{ route('admin.marks-grade.add') }}" class="btn btn-primary float-sm-end">Add Marks Grade</a>
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
              <h3 class="card-title">Marks Grade List</h3>
            </div>

            <div class="card-body p-0">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Serial</th>
                    <th>Grade Name</th>
                    <th>Percent From</th>
                    <th>Percent To</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($getRecord as $i => $row)
                    <tr>
                      <td>{{ $getRecord->firstItem() + $i }}</td>
                      <td><strong>{{ $row->grade_name }}</strong></td>
                      <td>{{ $row->percent_from }}%</td>
                      <td>{{ $row->percent_to }}%</td>
                      <td>{{ optional($row->creator)->name }} {{ optional($row->creator)->last_name }}</td>
                      <td>{{ $row->created_at?->format('d M Y') }}</td>
                      <td>
                        <a href="{{ route('admin.marks-grade.edit-grade', $row->id) }}" class="btn btn-sm btn-warning">Edit</a>

                        <form action="{{ route('admin.marks-grade.delete-grade') }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this grade?');">
                          @csrf
                          <input type="hidden" name="id" value="{{ $row->id }}">
                          <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="7" class="text-center text-muted">No records found.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>

              <div class="px-3 pb-3">
                <p class="text-center mt-3">
                  Showing {{ $getRecord->count() ? $getRecord->firstItem() : 0 }}–
                  {{ $getRecord->count() ? $getRecord->lastItem() : 0 }}
                  of {{ $getRecord->total() }} records
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
