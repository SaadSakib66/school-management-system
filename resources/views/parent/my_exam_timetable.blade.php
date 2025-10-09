@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">
            {{ $header_title ?? 'My Child’s Exam Schedule' }}
            @if($selectedStudent && $class)
              <small class="text-muted">— {{ $selectedStudent->name }} {{ $selectedStudent->last_name }} (Class: {{ $class->name }})</small>
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

          <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title">Filter</h3></div>
            <div class="card-body">
              @if($students->isEmpty())
                <div class="alert alert-warning mb-0">No students are assigned to your account yet.</div>
              @else
                <form method="GET" action="{{ route('parent.my-exam-timetable') }}" class="row g-3 align-items-end" id="filterForm">
                  <div class="col-md-4">
                    <label class="form-label">Student</label>
                    <select name="student_id" id="student_id" class="form-select">
                      <option value="">Select Student</option>
                      @foreach($students as $s)
                        <option value="{{ $s->id }}" {{ $selectedStudentId == $s->id ? 'selected' : '' }}>
                          {{ $s->name }} {{ $s->last_name }}
                        </option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label">Exam</label>
                    <select name="exam_id" id="exam_id" class="form-select" {{ $selectedStudentId ? '' : 'disabled' }}>
                      <option value="">{{ $selectedStudentId ? 'Select Exam' : 'Select Student first' }}</option>
                      @foreach($exams as $e)
                        <option value="{{ $e->id }}" {{ ($selectedExamId == $e->id) ? 'selected' : '' }}>
                          {{ $e->name }}
                        </option>
                      @endforeach
                    </select>
                  </div>

<div class="col-md-4">
  <button type="submit" class="btn btn-primary">Search</button>
  <a href="{{ route('parent.my-exam-timetable') }}" class="btn btn-success">Reset</a>

  {{-- Download (only enabled when both student & exam chosen) --}}
  <button type="submit"
          name="download"
          value="1"
          class="btn btn-danger"
          formtarget="_blank"
          {{ ($selectedStudentId && $selectedExamId) ? '' : 'disabled' }}>
    Download Exam Schedule
  </button>
</div>
                </form>
              @endif
            </div>
          </div>

          @if($selectedStudentId && $selectedExamId)
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">Exam Schedule</h3>
              </div>
              <div class="card-body p-0">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th style="width:25%">Subject</th>
                      <th style="width:18%">Date</th>
                      <th style="width:15%">Start</th>
                      <th style="width:15%">End</th>
                      <th style="width:15%">Room</th>
                      <th style="width:6%">Full</th>
                      <th style="width:6%">Pass</th>
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
                      <tr><td colspan="7" class="text-center text-muted p-3">No schedule found for this selection.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          @elseif(request()->has('student_id') || request()->has('exam_id'))
            <div class="alert alert-info">Select both a <strong>Student</strong> and an <strong>Exam</strong> to view the schedule.</div>
          @endif

        </div>
      </div>
    </div>
  </div>
</main>

{{-- Load exams via AJAX when student changes --}}
@push('scripts')
<script>
const studentSel = document.getElementById('student_id');
const examSel    = document.getElementById('exam_id');

function populateExams(exams) {
  examSel.disabled = false;
  examSel.innerHTML = '<option value="">Select Exam</option>';
  exams.forEach(e => {
    const opt = document.createElement('option');
    opt.value = e.id;
    opt.textContent = e.name;
    examSel.appendChild(opt);
  });
}

studentSel?.addEventListener('change', async function () {
  const studentId = this.value;
  examSel.disabled = true;
  examSel.innerHTML = '<option>Loading…</option>';

  if (!studentId) {
    examSel.innerHTML = '<option>Select Student first</option>';
    return;
  }

  try {
    const url = @json(route('parent.my-exam-timetable.exams', ['student' => '__STUDENT__'])).replace('__STUDENT__', encodeURIComponent(studentId));
    const res = await fetch(url);
    const data = await res.json();
    if (Array.isArray(data) && data.length) {
      populateExams(data);
    } else {
      examSel.disabled = true;
      examSel.innerHTML = '<option>No exams found</option>';
    }
  } catch (e) {
    console.error(e);
    examSel.disabled = true;
    examSel.innerHTML = '<option>Failed to load</option>';
  }
});
</script>
@endpush

@endsection
