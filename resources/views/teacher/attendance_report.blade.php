@extends('admin.layout.layout') {{-- or teacher layout if you have one --}}
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
              {{-- Keep controls in one line on large screens; wrap on smaller --}}
              <form id="attendanceFilterForm" method="GET" action="{{ route('teacher.attendance-report.view') }}" class="row row-cols-lg-auto g-3 align-items-end">

                <div class="col">
                  <label class="form-label mb-1">Class</label>
                  <select name="class_id" class="form-select minw-220">
                    <option value="">Select Class</option>
                    @foreach($classes as $c)
                      <option value="{{ $c->id }}" {{ ($selectedClassId ?? null) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col">
                  <label class="form-label mb-1">Single Date</label>
                  <input type="date" name="attendance_date" class="form-control"
                         value="{{ $selectedDate ?? '' }}" max="{{ $today }}">
                </div>

                <div class="col">
                  <label class="form-label mb-1">From</label>
                  <input type="date" name="date_from" class="form-control"
                         value="{{ $dateFrom ?? '' }}" max="{{ $today }}">
                </div>

                <div class="col">
                  <label class="form-label mb-1">To</label>
                  <input type="date" name="date_to" class="form-control"
                         value="{{ $dateTo ?? '' }}" max="{{ $today }}">
                  {{-- <div class="form-text">Use either Single Date or From–To.</div> --}}
                </div>

                <div class="col">
                  <label class="form-label mb-1">Attendance Type</label>
                  <select name="attendance_type" class="form-select">
                    <option value="">All</option>
                    <option value="1" {{ ($selectedType ?? '')==='1' ? 'selected' : '' }}>Present</option>
                    <option value="2" {{ ($selectedType ?? '')==='2' ? 'selected' : '' }}>Late</option>
                    <option value="3" {{ ($selectedType ?? '')==='3' ? 'selected' : '' }}>Half Day</option>
                    <option value="4" {{ ($selectedType ?? '')==='4' ? 'selected' : '' }}>Absent</option>
                  </select>
                </div>

                <div class="col">
                  <label class="form-label mb-1">Student (optional)</label>
                  <select name="student_id" class="form-select minw-220">
                    <option value="">All Students</option>
                    @foreach(($students ?? collect()) as $stu)
                      <option value="{{ $stu->id }}" {{ ($selectedStudent ?? null) == $stu->id ? 'selected' : '' }}>
                        {{ $stu->roll_number ? $stu->roll_number.' — ' : '' }}{{ $stu->name }} {{ $stu->last_name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col d-flex gap-2">
                  <button type="submit" class="btn btn-primary">Search</button>
                  <a href="{{ route('teacher.attendance-report.view') }}" class="btn btn-success">Reset</a>
                  <button type="button" id="btnDownload" class="btn btn-outline-secondary">Download</button>
                </div>

              </form>
            </div>
          </div>

          {{-- Results --}}
          @if(($selectedClassId ?? null) && (($selectedDate ?? null) || (($dateFrom ?? null) && ($dateTo ?? null))))
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Results</h3>
              </div>

              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-striped mb-0 align-middle">
                    <thead>
                      <tr>
                        <th style="width:140px">Student ID</th>
                        <th>Student Name</th>
                        <th style="width:160px">Attendance Type</th>
                        <th style="width:140px">Date</th>
                        <th style="width:200px">Created By</th>
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
          @elseif(request()->has('class_id') || request()->has('attendance_date') || request()->has('date_from') || request()->has('date_to') || request()->has('attendance_type') || request()->has('student_id'))
            <div class="alert alert-info">
              Please select <strong>Class</strong> and either a <strong>Single Date</strong> or a valid <strong>From–To</strong> range, then click <strong>Search</strong>.
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@push('styles')
<style>
  .minw-220 { min-width: 220px; }
</style>
@endpush

@push('scripts')
<script>
(function(){
  const form = document.getElementById('attendanceFilterForm');
  const btn  = document.getElementById('btnDownload');

  if(btn && form){
    btn.addEventListener('click', function(){
      const cls = form.querySelector('[name=class_id]')?.value;
      const d   = form.querySelector('[name=attendance_date]')?.value;
      const df  = form.querySelector('[name=date_from]')?.value;
      const dt  = form.querySelector('[name=date_to]')?.value;

      if(!cls){
        alert('Please select a Class first.');
        return;
      }
      if(!d && !(df && dt)){
        alert('Select a Single Date OR a From–To date range.');
        return;
      }

      const params = new URLSearchParams(new FormData(form));
      const url = "{{ route('teacher.attendance-report.download') }}" + "?" + params.toString();
      window.open(url, '_blank');
    });
  }
})();
</script>
@endpush
