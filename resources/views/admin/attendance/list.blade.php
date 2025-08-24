@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Student Attendance</h3></div>
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
            <div class="card-header"><h3 class="card-title">Search Student Attendance</h3></div>
            <div class="card-body">
              <form method="GET" action="{{ route('admin.student-attendance.view') }}" class="row g-3 align-items-end">

                <div class="col-md-6">
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

                <div class="col-md-2">
                  <button type="submit" class="btn btn-primary">Search</button>
                  <a href="{{ route('admin.student-attendance.view') }}" class="btn btn-success">Reset</a>
                </div>

              </form>
            </div>
          </div>

          {{-- Student list --}}
          @if(($selectedClassId ?? null) && ($selectedDate ?? null))
            <div class="card">
              <div class="card-header"><h3 class="card-title">Student List</h3></div>
              <div class="card-body p-0">
                <form method="POST" action="{{ route('admin.student-attendance.save') }}">
                  @csrf
                  <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                  <input type="hidden" name="attendance_date" value="{{ $selectedDate }}">

                  <table class="table table-striped mb-0 align-middle">
                    <thead>
                      <tr>
                        <th style="width:200px">Student ID</th>
                        <th>Student Name</th>
                        <th style="min-width:420px">Attendance</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($students as $stu)
                        @php $val = optional($existing->get($stu->id))->attendance_type; @endphp
                        <tr>
                          <td>{{ $stu->roll_number ?? '-' }}</td>
                          <td>{{ $stu->name }} {{ $stu->last_name }}</td>
                          <td>
                            <div class="d-flex align-items-center gap-4">
                              <label class="form-check-label">
                                <input class="form-check-input me-1" type="radio"
                                       name="attendance[{{ $stu->id }}]" value="1" {{ $val == 1 ? 'checked' : '' }}>
                                Present
                              </label>

                              <label class="form-check-label">
                                <input class="form-check-input me-1" type="radio"
                                       name="attendance[{{ $stu->id }}]" value="2" {{ $val == 2 ? 'checked' : '' }}>
                                Late
                              </label>

                              <label class="form-check-label">
                                <input class="form-check-input me-1" type="radio"
                                       name="attendance[{{ $stu->id }}]" value="4" {{ $val == 4 ? 'checked' : '' }}>
                                Absent
                              </label>

                              <label class="form-check-label">
                                <input class="form-check-input me-1" type="radio"
                                       name="attendance[{{ $stu->id }}]" value="3" {{ $val == 3 ? 'checked' : '' }}>
                                Half Day
                              </label>
                            </div>
                          </td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="3" class="text-center text-muted">No students found for this class.</td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>

                    @php
                    $presentCount = $existing->where('attendance_type', 1)->count();
                    $absentCount  = $existing->where('attendance_type', 4)->count();
                    @endphp

                    <div class="px-3 py-2 bg-light border-top d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div><strong>Total students:</strong> {{ $students->count() }}</div>
                    <div class="d-flex gap-3">
                        <span class="badge bg-success p-2">Present: <span id="countPresent">{{ $presentCount }}</span></span>
                        <span class="badge bg-danger p-2">Absent: <span id="countAbsent">{{ $absentCount }}</span></span>
                    </div>
                    </div>

                    @if($students->count())
                    <div class="p-3">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                    @endif
                </form>
              </div>
            </div>
          @elseif(request()->has('class_id') || request()->has('attendance_date'))
            <div class="alert alert-info">
              Please select both a <strong>Class</strong> and an <strong>Attendance Date</strong>, then click <strong>Search</strong>.
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</main>
@endsection


@push('scripts')
<script>
(function(){
  function recalc() {
    let present = 0, absent = 0;

    document.querySelectorAll("input.form-check-input[name^='attendance[']:checked")
      .forEach(function(inp){
        if (inp.value === '1') present++;
        else if (inp.value === '4') absent++;
      });

    const p = document.getElementById('countPresent');
    const a = document.getElementById('countAbsent');
    if (p) p.textContent = present;
    if (a) a.textContent = absent;
  }

  // initial
  recalc();

  // live updates
  document.addEventListener('change', function(e){
    if (e.target && e.target.matches("input.form-check-input[name^='attendance[']")) {
      recalc();
    }
  });
})();
</script>
@endpush

