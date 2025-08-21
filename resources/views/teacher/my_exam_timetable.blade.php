@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">{{ $header_title ?? 'My Exam Schedule' }}</h3></div>
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
              <form method="GET" action="{{ route('teacher.my-exam-timetable') }}" class="row g-3 align-items-end" id="filterForm">
                <div class="col-md-4">
                  <label class="form-label">Class</label>
                  <select name="class_id" class="form-select" id="class_id">
                    <option value="">Select Class</option>
                    @foreach($classes as $c)
                      <option value="{{ $c->id }}" {{ ($selectedClassId == $c->id) ? 'selected' : '' }}>
                        {{ $c->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Exam</label>
                  <select name="exam_id" class="form-select" id="exam_id" {{ $selectedClassId ? '' : 'disabled' }}>
                    <option value="">{{ $selectedClassId ? 'Select Exam' : 'Select Class first' }}</option>
                    @foreach($exams as $e)
                      <option value="{{ $e->id }}" {{ ($selectedExamId == $e->id) ? 'selected' : '' }}>
                        {{ $e->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-4">
                  <button type="submit" class="btn btn-primary">Search</button>
                  <a href="{{ route('teacher.my-exam-timetable') }}" class="btn btn-success">Reset</a>
                </div>
              </form>
            </div>
          </div>

          @if($selectedClassId && $selectedExamId)
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
          @elseif(request()->has('class_id') || request()->has('exam_id'))
            <div class="alert alert-info">Select both a <strong>Class</strong> and an <strong>Exam</strong> to view the schedule.</div>
          @endif

        </div>
      </div>
    </div>
  </div>
</main>

{{-- Load exams via AJAX when class changes --}}
@push('scripts')
<script>
  const classSel = document.getElementById('class_id');
  const examSel  = document.getElementById('exam_id');
  const baseExamsUrl = @json(url('teacher/my_exam_timetable/exams')); // => "/teacher/my_exam_timetable/exams"

  classSel?.addEventListener('change', async function () {
    const classId = this.value;
    examSel.disabled = true;
    examSel.innerHTML = '<option>Loading…</option>';

    if (!classId) {
      examSel.innerHTML = '<option>Select Class first</option>';
      return;
    }

    const url = `${baseExamsUrl}/${encodeURIComponent(classId)}`;
    try {
      const res = await fetch(url);
      const data = await res.json();
      examSel.disabled = false;
      examSel.innerHTML = '<option value="">Select Exam</option>';
      (data || []).forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.id;
        opt.textContent = e.name;
        examSel.appendChild(opt);
      });
      if (!data || !data.length) {
        examSel.disabled = true;
        examSel.innerHTML = '<option>No exams found</option>';
      }
    } catch (err) {
      console.error(err);
      examSel.disabled = true;
      examSel.innerHTML = '<option>Failed to load</option>';
    }
  });
</script>
@endpush

@endsection
