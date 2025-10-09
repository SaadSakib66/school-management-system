@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">
            {{ $header_title ?? 'My Child’s Timetable' }}
            @if($selectedStudent)
              <small class="text-muted">
                — {{ $selectedStudent->name }} {{ $selectedStudent->last_name }}
                @if($class) (Class: {{ $class->name }}) @endif
              </small>
            @endif
          </h3>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          {{-- Select child --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title">Select Student</h3></div>
            <div class="card-body">
              @if($students->isEmpty())
                <div class="alert alert-warning mb-0">
                  No students are assigned to your account yet.
                </div>
              @else
                <form method="GET" action="{{ route('parent.my-timetable') }}" class="row g-3 align-items-end">
                  <div class="col-md-6">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select">
                      <option value="">Select Student</option>
                      @foreach($students as $s)
                        <option value="{{ $s->id }}" {{ $selectedStudentId == $s->id ? 'selected' : '' }}>
                          {{ $s->name }} {{ $s->last_name }}
                        </option>
                      @endforeach
                    </select>
                  </div>
<div class="col-md-6">
  <button type="submit" class="btn btn-primary">Show</button>
  <a href="{{ route('parent.my-timetable') }}" class="btn btn-success">Reset</a>

  {{-- Download (enabled only if a student is selected) --}}
  <button type="submit"
          name="download"
          value="1"
          formtarget="_blank"
          class="btn btn-danger"
          {{ $selectedStudentId ? '' : 'disabled' }}>
    Download PDF
  </button>
</div>
                </form>
              @endif
            </div>
          </div>

          {{-- Timetable (only after a student is chosen) --}}
          @if($selectedStudent)
            @if(!$selectedStudent->class_id)
              <div class="alert alert-info">This student is not assigned to any class yet.</div>
            @else
              <div class="card mb-4">
                <div class="card-header">
                  <h3 class="card-title">Class Timetable ({{ $class?->name }})</h3>
                </div>

                <div class="card-body p-0">
                  <table class="table table-striped mb-0">
                    <thead>
                      <tr>
                        <th style="width:20%">Day</th>
                        <th style="width:30%">Subject</th>
                        <th style="width:20%">Start</th>
                        <th style="width:20%">End</th>
                        <th style="width:10%">Room</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($weeks as $week)
                        @php $items = $byWeek[$week->id] ?? collect(); @endphp

                        @if($items->isEmpty())
                          <tr>
                            <td class="align-middle"><strong>{{ $week->name }}</strong></td>
                            <td colspan="4" class="text-muted">No classes scheduled.</td>
                          </tr>
                        @else
                          @foreach($items as $i => $row)
                            <tr>
                              @if($i === 0)
                                <td class="align-middle" rowspan="{{ $items->count() }}"><strong>{{ $week->name }}</strong></td>
                              @endif
                              <td>{{ $row->subject?->name ?? '—' }}</td>
                              <td>{{ $row->start_time ? \Carbon\Carbon::createFromFormat('H:i:s',$row->start_time)->format('h:i A') : '—' }}</td>
                              <td>{{ $row->end_time ? \Carbon\Carbon::createFromFormat('H:i:s',$row->end_time)->format('h:i A') : '—' }}</td>
                              <td>{{ $row->room_number ?? '—' }}</td>
                            </tr>
                          @endforeach
                        @endif
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            @endif
          @endif

        </div>
      </div>
    </div>
  </div>
</main>
@endsection
