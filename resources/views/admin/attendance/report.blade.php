@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Attendance Report</h3></div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          {{-- Filter --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title">Search Attendance Report</h3></div>
            <div class="card-body">
              <form method="GET" action="{{ route('admin.attendance-report.view') }}" class="row g-3 align-items-end">

                <div class="col-md-4">
                  <label class="form-label">Class</label>
                  <select name="class_id" class="form-select">
                    <option value="">Select Class</option>
                    @foreach($classes as $c)
                      <option value="{{ $c->id }}" {{ ($selectedClassId ?? null) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Attendance Date</label>
                  <input type="date" name="attendance_date" class="form-control"
                         value="{{ $selectedDate ?? '' }}" max="{{ $today }}">
                </div>

                <div class="col-md-4">
                  <label class="form-label">Attendance Type</label>
                  <select name="attendance_type" class="form-select">
                    <option value="">All</option>
                    <option value="1" {{ ($selectedType ?? '')==='1' ? 'selected' : '' }}>Present</option>
                    <option value="2" {{ ($selectedType ?? '')==='2' ? 'selected' : '' }}>Late</option>
                    <option value="3" {{ ($selectedType ?? '')==='3' ? 'selected' : '' }}>Half Day</option>
                    <option value="4" {{ ($selectedType ?? '')==='4' ? 'selected' : '' }}>Absent</option>
                  </select>
                </div>

                <div class="col-12">
                  <button type="submit" class="btn btn-primary">Search</button>
                  <a href="{{ route('admin.attendance-report.view') }}" class="btn btn-success">Reset</a>
                </div>

              </form>
            </div>
          </div>

          {{-- Results --}}
          @if(($selectedClassId ?? null) && ($selectedDate ?? null))
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Results</h3>
              </div>

              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-striped mb-0 align-middle">
                    <thead>
                      <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Attendance Type</th>
                        <th>Date</th>
                        <th>Created By</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse(($records?->items() ?? []) as $row)
                        <tr>
                          <td>{{ $row->student?->roll_number ?? $row->student_id }}</td>
                          <td>{{ $row->student?->name }} {{ $row->student?->last_name }}</td>
                          <td>
                            @php
                              $label = $typeMap[$row->attendance_type] ?? 'Unknown';
                              $badge = match((int)$row->attendance_type) {
                                1 => 'bg-success',
                                2 => 'bg-warning',
                                3 => 'bg-info',
                                4 => 'bg-danger',
                                default => 'bg-secondary',
                              };
                            @endphp
                            <span class="badge {{ $badge }}">{{ $label }}</span>
                          </td>
                          <td>{{ \Carbon\Carbon::parse($row->attendance_date)->format('d-m-Y') }}</td>
                          <td>{{ $row->creator?->name }} {{ $row->creator?->last_name }}</td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="5" class="text-center text-muted">No records found.</td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>

                @if($records)
                  <div class="px-3 pb-3">
                    <p class="text-center mt-3">
                      Showing {{ $records->count() ? $records->firstItem() : 0 }}–
                      {{ $records->count() ? $records->lastItem() : 0 }}
                      of {{ $records->total() }} records
                    </p>
                    {{ $records->links('pagination::bootstrap-5') }}
                  </div>
                @endif
              </div>
            </div>
          @elseif(request()->has('class_id') || request()->has('attendance_date') || request()->has('attendance_type'))
            <div class="alert alert-info">
              Please select at least <strong>Class</strong> and <strong>Attendance Date</strong>, then click <strong>Search</strong>.
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</main>
@endsection
