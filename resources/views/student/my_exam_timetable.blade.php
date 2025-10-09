@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">
            {{ $header_title ?? 'My Exam Timetable' }}
            @if($studentClassName)
              <small class="text-muted">— Class: {{ $studentClassName }}</small>
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

          {{-- Exam selector (class is fixed by student) --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title">Select Exam</h3></div>
            <div class="card-body">
              @if($exams->isEmpty())
                <div class="alert alert-info mb-0">
                  No exam schedules are available for your class yet.
                </div>
              @else
                <form method="GET" action="{{ route('student.my-exam-timetable') }}" class="row g-3 align-items-end">
                  <div class="col-md-6">
                    <label class="form-label">Exam</label>
                    <select name="exam_id" class="form-select">
                      @foreach($exams as $e)
                        <option value="{{ $e->id }}" {{ $selectedExamId == $e->id ? 'selected' : '' }}>
                          {{ $e->name }}
                        </option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Show</button>

                    <a href="{{ route('student.my-exam-timetable') }}" class="btn btn-success">Reset</a>

                    {{-- Download beside Reset (opens PDF in new tab). Uses current select value. --}}
                    <button type="submit"
                            class="btn btn-danger"
                            formaction="{{ route('student.exam-calendar.download') }}"
                            formmethod="GET"
                            formtarget="_blank">
                      Download Exam Schedule
                    </button>
                  </div>
                </form>
              @endif
            </div>
          </div>

          {{-- Timetable --}}
          @if($selectedExamId && $exams->isNotEmpty())
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">
                  Exam Schedule
                  @if($selectedExam) — <span class="text-muted">{{ $selectedExam->name }}</span>@endif
                </h3>
              </div>

              <div class="card-body p-0">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th style="width:25%">Subject</th>
                      <th style="width:20%">Date</th>
                      <th style="width:15%">Start</th>
                      <th style="width:15%">End</th>
                      <th style="width:15%">Room</th>
                      <th style="width:10%">Full</th>
                      <th style="width:10%">Pass</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($rows as $row)
                      <tr>
                        <td>{{ $row->subject?->name ?? '—' }}</td>
                        <td>{{ $row->exam_date ? \Carbon\Carbon::parse($row->exam_date)->format('d-m-Y') : '—' }}</td>
                        <td>{{ $row->start_time ? \Carbon\Carbon::parse($row->start_time)->format('h:i A') : '—' }}</td>
                        <td>{{ $row->end_time ? \Carbon\Carbon::parse($row->end_time)->format('h:i A') : '—' }}</td>
                        <td>{{ $row->room_number ?? '—' }}</td>
                        <td>{{ $row->full_mark ?? '—' }}</td>
                        <td>{{ $row->passing_mark ?? '—' }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="7" class="text-center text-muted p-3">No schedule found for this exam.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</main>
@endsection
