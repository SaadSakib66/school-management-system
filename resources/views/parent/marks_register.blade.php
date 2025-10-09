{{-- resources/views/parent/marks_register.blade.php --}}
@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Child Exam Results</h3></div>
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
              {{-- NOTE: removed align-items-end --}}
              <form method="GET" action="{{ route('parent.marks-register.list') }}" class="row g-3">

                {{-- Student --}}
                <div class="col-md-6">
                  <label class="form-label">Student</label>
                  <select name="student_id" id="student_id" class="form-select">
                    <option value="">Select Student</option>
                    @foreach($children as $ch)
                      <option value="{{ $ch->id }}" {{ (string)($selectedStudentId ?? '') === (string)$ch->id ? 'selected' : '' }}>
                        {{ $ch->name }} {{ $ch->last_name }}
                      </option>
                    @endforeach
                  </select>
                  {{-- reserve the same space as exam help for perfect alignment --}}
                  <div class="form-text help-reserve">&nbsp;</div>
                  @if($children->isEmpty())
                    <div class="form-text text-danger">No student is assigned to your account.</div>
                  @endif
                </div>

                {{-- Exam --}}
                <div class="col-md-6">
                  <label class="form-label">Exam</label>
                  <select name="exam_id" id="exam_id" class="form-select" {{ empty($selectedStudentId) ? 'disabled' : '' }}>
                    <option value="">Select Exam</option>
                    @foreach($exams as $e)
                      <option value="{{ $e->id }}" {{ (string)($selectedExamId ?? '') === (string)$e->id ? 'selected' : '' }}>
                        {{ $e->name }}
                      </option>
                    @endforeach
                  </select>
                  <div id="exam_help" class="form-text help-reserve">
                    {{ empty($selectedStudentId) ? "Select a student first." : "" }}
                  </div>
                </div>

<div class="col-12">
  <button type="submit" class="btn btn-primary" {{ $children->isEmpty() ? 'disabled' : '' }}>
    Search
  </button>
  <a href="{{ route('parent.marks-register.list') }}" class="btn btn-success">Reset</a>

  {{-- Open PDF in a new tab (inline) --}}
  <button type="submit"
          name="download"
          value="1"
          class="btn btn-danger"
          formtarget="_blank"
          {{ ($selectedStudentId && $selectedExamId) ? '' : 'disabled' }}>
    Download Result
  </button>
</div>


              </form>
            </div>
          </div>

          {{-- Results --}}
          @if($section)
            <div class="card mb-4">
              <div class="card-header"><strong>{{ $section['exam']->name }}</strong></div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-striped mb-0">
                    <thead>
                      <tr>
                        <th>Subject</th>
                        <th>Class Work</th>
                        <th>Test Work</th>
                        <th>Home Work</th>
                        <th>Exam</th>
                        <th>Total Score</th>
                        <th>Passing Marks</th>
                        <th>Full Marks</th>
                        <th>Result</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($section['rows'] as $r)
                        <tr>
                          <td>{{ $r['subject'] }}</td>
                          <td>{{ $r['class_work'] }}</td>
                          <td>{{ $r['test_work'] }}</td>
                          <td>{{ $r['home_work'] }}</td>
                          <td>{{ $r['exam'] }}</td>
                          <td><strong>{{ $r['total'] }}</strong></td>
                          <td>{{ $r['passing_mark'] }}</td>
                          <td>{{ $r['full_mark'] }}</td>
                          <td class="{{ $r['result'] === 'Pass' ? 'text-success' : 'text-danger' }}">{{ $r['result'] }}</td>
                        </tr>
                      @endforeach
                      <tr>
                        <th colspan="5" class="text-end">Grand Total:</th>
                        <th>{{ $section['grandTotal'] }}/{{ $section['grandFull'] }}</th>
                        <th colspan="2" class="text-end">Percentage:</th>
                        <th>{{ $section['percentage'] !== null ? $section['percentage'].'%' : '-' }}</th>
                      </tr>
                      <tr>
                        <th colspan="8" class="text-end">Overall Result:</th>
                        <th class="{{ $section['overall'] === 'Pass' ? 'text-success' : 'text-danger' }}">{{ $section['overall'] }}</th>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
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
  /* Reserve identical space under both selects to keep columns the same height */
  .help-reserve { min-height: 1.25rem; } /* ~20px, matches .form-text line-height */
</style>
@endpush

@push('scripts')
<script>
(function(){
  const examsByStudent = @json($examsByStudent ?? []);
  const studentSel = document.getElementById('student_id');
  const examSel    = document.getElementById('exam_id');
  const help       = document.getElementById('exam_help');
  const preselectedExam = String(@json($selectedExamId ?? '') || '');

  function clearExamOptions(){
    examSel.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = 'Select Exam';
    examSel.appendChild(opt0);
  }

  function populateExams(studentId){
    clearExamOptions();

    if (!studentId || !examsByStudent[studentId] || examsByStudent[studentId].length === 0){
      examSel.disabled = true;
      if (help) help.textContent = studentId ? "No exams are scheduled for this student's class." : "Select a student first.";
      return;
    }

    examsByStudent[studentId].forEach(e => {
      const opt = document.createElement('option');
      opt.value = e.id;
      opt.textContent = e.name;
      examSel.appendChild(opt);
    });

    if (preselectedExam && examsByStudent[studentId].some(e => String(e.id) === preselectedExam)){
      examSel.value = preselectedExam;
    } else {
      examSel.value = '';
    }

    examSel.disabled = false;
    if (help) help.textContent = '';
  }

  // Initial
  populateExams(studentSel.value);

  // On change
  studentSel.addEventListener('change', function(){
    populateExams(this.value);
  });
})();
</script>
@endpush
