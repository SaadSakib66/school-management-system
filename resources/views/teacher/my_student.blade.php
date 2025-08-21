@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">{{ $header_title }}</h3></div>
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
              <h3 class="card-title">My Students</h3>
            </div>

            <div class="card-body">
              <form method="GET" class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Class</label>
                  <select name="class_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Classes</option>
                    @foreach($classes as $cls)
                      <option value="{{ $cls->id }}" {{ request('class_id') == $cls->id ? 'selected' : '' }}>
                        {{ $cls->name }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button type="submit" class="btn btn-primary">Filter</button>
                </div>
              </form>
            </div>

            <div class="card-body p-0">
              <table class="table table-striped mb-0">
                <thead>
                  <tr>
                    <th>Serial</th>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Class</th>
                    <th>Roll</th>
                    <th>Admission Number</th>
                    <th>Created Date</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($getRecord as $i => $stu)
                    <tr>
                      <td>{{ ($getRecord->currentPage() - 1) * $getRecord->perPage() + $loop->iteration }}</td>
                      <td>{{ $stu->name }} {{ $stu->last_name }}</td>
                      <td>{{ $stu->email }}</td>
                      <td>{{ $stu->mobile_number }}</td>
                      <td>{{ $stu->class_name }}</td>
                      <td>{{ $stu->roll_number }}</td>
                      <td>{{ $stu->admission_number }}</td>
                      <td>{{ \Carbon\Carbon::parse($stu->created_at)->format('d M Y') }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="8" class="text-center p-4">No students found.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <div class="p-3">
              <p class="text-center mb-2">
                Showing {{ $getRecord->count() }} of {{ $getRecord->total() }} students
              </p>
              {{ $getRecord->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
            </div>

          </div>

        </div>
      </div>
    </div>
  </div>
</main>

@endsection
