@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Marks Register</h3></div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          {{-- Search / Filter --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title">Search Marks Register</h3></div>
            <div class="card-body">
              {{-- Use row-cols-lg-auto to keep all controls in one line on large screens --}}
              <form id="marksFilterForm" method="GET" action="{{ route('admin.marks-register.list') }}" class="row row-cols-lg-auto g-3 align-items-end">

                <div class="col">
                  <label class="form-label mb-1">Exam</label>
                  <select name="exam_id" id="exam_id" class="form-select minw-220">
                    <option value="">Select Exam</option>
                    @foreach($exams as $e)
                      <option value="{{ $e->id }}" {{ ($selectedExamId ?? null) == $e->id ? 'selected' : '' }}>
                        {{ $e->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col">
                  <label class="form-label mb-1">Class</label>
                  <select name="class_id" id="class_id" class="form-select minw-220">
                    <option value="">Select Class</option>
                    @foreach($classes as $c)
                      <option value="{{ $c->id }}" {{ ($selectedClassId ?? null) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                {{-- Student (optional) --}}
                <div class="col">
                  <label class="form-label mb-1">Student (optional)</label>
                  <select name="student_id" id="student_id" class="form-select minw-240">
                    <option value="">All Students</option>
                    @if(($selectedClassId ?? null) && ($students ?? collect())->count())
                      @foreach($students as $stu)
                        <option value="{{ $stu->id }}" {{ request('student_id') == $stu->id ? 'selected' : '' }}>
                          {{ $stu->name }} {{ $stu->last_name }}
                        </option>
                      @endforeach
                    @endif
                  </select>
                </div>

                {{-- Buttons in the same row, side by side --}}
                <div class="col d-flex gap-2">
                  <button type="submit" class="btn btn-primary">Search</button>
                  <a href="{{ route('admin.marks-register.list') }}" class="btn btn-success">Reset</a>
                  <button type="button" id="btnDownload" class="btn btn-outline-secondary">Download Result</button>
                </div>

              </form>
            </div>
          </div>

          {{-- Register table only when both selected --}}
          @if(($selectedExamId ?? null) && ($selectedClassId ?? null))
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title">Marks Register</h3>
            </div>

            <div class="card-body p-0">
              <form method="POST" action="{{ route('admin.marks-register.save') }}">
                @csrf
                <input type="hidden" name="exam_id"  value="{{ $selectedExamId }}">
                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                {{-- Used to detect per-row Save --}}
                <input type="hidden" name="only_student_id" id="only_student_id" value="">

                <div class="table-responsive">
                  <table class="table table-striped mb-0 align-middle">
                    <thead>
                      <tr>
                        <th style="min-width:220px">Student Name</th>
                        @foreach($subjects as $s)
                          @php $sch = $scheduleMap[$s->id] ?? null; @endphp
                          <th class="text-uppercase" style="min-width:260px">
                            {{ $s->name }}
                            <div class="small text-muted">
                              ({{ $sch?->passing_mark ?? '-' }} / {{ $sch?->full_mark ?? '-' }})
                            </div>
                          </th>
                        @endforeach
                        <th style="width:110px" class="text-end">Action</th>
                      </tr>
                    </thead>

                    <tbody>
                      @forelse($students as $stu)
                        {{-- MAIN INPUT ROW --}}
                        <tr class="js-student-row">
                          <td><strong>{{ $stu->name }} {{ $stu->last_name }}</strong></td>

                          @foreach($subjects as $s)
                            @php
                              $m   = $marks[$stu->id][$s->id] ?? null;
                              $sch = $scheduleMap[$s->id] ?? null;
                            @endphp
                            <td class="js-subject-cell" data-full="{{ $sch?->full_mark }}" data-pass="{{ $sch?->passing_mark }}">
                              <div class="row g-2">
                                <div class="col-12">
                                  <small>Class Work</small>
                                  <input type="number" min="0" step="1" class="form-control js-mark"
                                         name="marks[{{ $stu->id }}][{{ $s->id }}][class_work]"
                                         value="{{ old("marks.{$stu->id}.{$s->id}.class_work", $m?->class_work) }}"
                                         placeholder="Enter Marks">
                                </div>
                                <div class="col-12">
                                  <small>Home Work</small>
                                  <input type="number" min="0" step="1" class="form-control js-mark"
                                         name="marks[{{ $stu->id }}][{{ $s->id }}][home_work]"
                                         value="{{ old("marks.{$stu->id}.{$s->id}.home_work", $m?->home_work) }}"
                                         placeholder="Enter Marks">
                                </div>
                                <div class="col-12">
                                  <small>Test Work</small>
                                  <input type="number" min="0" step="1" class="form-control js-mark"
                                         name="marks[{{ $stu->id }}][{{ $s->id }}][test_work]"
                                         value="{{ old("marks.{$stu->id}.{$s->id}.test_work", $m?->test_work) }}"
                                         placeholder="Enter Marks">
                                </div>
                                <div class="col-12">
                                  <small>Exam</small>
                                  <input type="number" min="0" step="1" class="form-control js-mark"
                                         name="marks[{{ $stu->id }}][{{ $s->id }}][exam]"
                                         value="{{ old("marks.{$stu->id}.{$s->id}.exam", $m?->exam_mark) }}"
                                         placeholder="Enter Marks">
                                </div>
                                <div class="col-12">
                                  <small>Total</small>
                                  <input type="number" class="form-control js-total" value="{{ $m?->total }}" disabled>
                                  <div class="form-text js-total-hint"></div>
                                </div>
                              </div>
                            </td>
                          @endforeach

                          {{-- Action (kept) --}}
                          <td class="text-end">
                            <button type="button" class="btn btn-success btn-sm"
                                    onclick="document.getElementById('only_student_id').value='{{ $stu->id }}'; this.closest('form').submit();">
                              Save
                            </button>
                          </td>
                        </tr>

                        {{-- SUMMARY ROW (under subjects, before Save All) --}}
                        <tr class="js-student-summary bg-light">
                          <td></td>
                          <td colspan="{{ $subjects->count() }}" class="p-2">
                            <div class="d-flex flex-wrap justify-content-end gap-3 m-3">
                              <div><strong>Grand Total:</strong>
                                <span class="js-sum-grand">0</span> /
                                <span class="js-sum-full">0</span>
                              </div>
                              <div><strong>Percentage:</strong>
                                <span class="js-sum-pct">–</span>
                              </div>
                              <div><strong>Overall:</strong>
                                <span class="badge js-sum-overall bg-secondary">–</span>
                              </div>
                              <div><strong>Grade:</strong>
                                <span class="badge js-sum-grade bg-secondary">–</span>
                              </div>
                            </div>
                          </td>
                          <td></td> {{-- spacer under Action column --}}
                        </tr>
                      @empty
                        <tr>
                          <td colspan="{{ $subjects->count() + 2 }}" class="text-center text-muted">
                            No students found for this class.
                          </td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>

                <div class="p-3 text-center">
                  <button type="button" class="btn btn-primary"
                          onclick="document.getElementById('only_student_id').value=''; this.closest('form').submit();">
                    Save All
                  </button>
                </div>
              </form>
            </div>
          </div>
          @elseif(request()->has('exam_id') || request()->has('class_id'))
            <div class="alert alert-info">
              Select both an <strong>Exam</strong> and a <strong>Class</strong>, then click <strong>Search</strong>.
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
  /* Keep controls from shrinking too small */
  .minw-220 { min-width: 220px; }
  .minw-240 { min-width: 240px; }
  .js-student-summary .badge { font-size: .85rem; }
</style>
@endpush

@push('scripts')
<script>
(function(){
  const GRADES = @json($grades ?? []);

  function toNum(v){ const n = parseFloat(v); return isNaN(n) ? 0 : n; }
  function formatPercent(n){ return (n === null || isNaN(n)) ? '–' : (Math.round(n*100)/100).toFixed(2) + '%'; }
  function pickGrade(percent){
    if (!Array.isArray(GRADES) || GRADES.length === 0 || percent === null || isNaN(percent)) return '–';
    for (const g of GRADES){
      const from = Number(g.percent_from), to = Number(g.percent_to);
      if (percent >= from && percent <= to) return g.grade_name || '–';
    }
    return '–';
  }

  function updateCellTotal(cell){
    let sum = 0;
    cell.querySelectorAll('.js-mark').forEach(inp => { sum += toNum(inp.value); });
    const totalInput = cell.querySelector('.js-total');
    if (totalInput) totalInput.value = sum ? sum : '';

    const full = parseFloat(cell.dataset.full || '');
    const hint = cell.querySelector('.js-total-hint');
    totalInput && totalInput.classList.remove('is-invalid','is-valid');

    if (!isNaN(full)){
      if (sum > full){
        totalInput && totalInput.classList.add('is-invalid');
        if (hint) hint.textContent = 'Total exceeds full mark (' + full + ')';
      } else {
        totalInput && totalInput.classList.add('is-valid');
        if (hint) hint.textContent = '';
      }
    } else if (hint){ hint.textContent = ''; }

    const row = cell.closest('tr.js-student-row');
    if (row) updateRowSummary(row);
  }

  function updateRowSummary(row){
    const summary = row.nextElementSibling && row.nextElementSibling.classList.contains('js-student-summary')
      ? row.nextElementSibling : null;
    if (!summary) return;

    const cells = row.querySelectorAll('.js-subject-cell');
    let grandTotal = 0, grandFull = 0, anyFail = false;

    cells.forEach(cell => {
      const total = toNum(cell.querySelector('.js-total')?.value);
      const full  = parseFloat(cell.dataset.full || '0');
      const pass  = parseFloat(cell.dataset.pass || '');
      grandTotal += total;
      if (!isNaN(full)) grandFull += full;
      if (!isNaN(pass) && total < pass) anyFail = true;
    });

    summary.querySelector('.js-sum-grand').textContent = grandTotal || 0;
    summary.querySelector('.js-sum-full').textContent  = grandFull  || 0;

    const pct = grandFull > 0 ? (grandTotal * 100 / grandFull) : null;
    summary.querySelector('.js-sum-pct').textContent = formatPercent(pct);

    const overallEl = summary.querySelector('.js-sum-overall');
    overallEl.classList.remove('bg-success','bg-danger','bg-secondary');
    if (grandFull <= 0 && grandTotal === 0){
      overallEl.textContent = '–';
      overallEl.classList.add('bg-secondary');
    } else if (anyFail){
      overallEl.textContent = 'Fail';
      overallEl.classList.add('bg-danger');
    } else {
      overallEl.textContent = 'Pass';
      overallEl.classList.add('bg-success');
    }

    const gradeEl = summary.querySelector('.js-sum-grade');
    const g = pickGrade(pct);
    gradeEl.textContent = g;
    gradeEl.classList.remove('bg-success','bg-danger','bg-secondary','bg-info','bg-warning','bg-primary');
    if (g === '–'){
      gradeEl.classList.add('bg-secondary');
    } else {
      gradeEl.classList.add(anyFail ? 'bg-danger' : 'bg-primary');
    }
  }

  // Initial + live updates
  document.querySelectorAll('.js-subject-cell').forEach(cell => {
    updateCellTotal(cell);
    cell.querySelectorAll('.js-mark').forEach(inp => {
      inp.addEventListener('input', () => updateCellTotal(cell));
    });
  });
  document.querySelectorAll('tr.js-student-row').forEach(updateRowSummary);

  // Download handler (next to Reset)
  const form = document.getElementById('marksFilterForm');
  const btn  = document.getElementById('btnDownload');
  if(btn && form){
    btn.addEventListener('click', function(){
      const exam  = form.querySelector('#exam_id')?.value;
      const klass = form.querySelector('#class_id')?.value;
      if(!exam || !klass){
        alert('Please select both Exam and Class before downloading.');
        return;
      }
      const params = new URLSearchParams(new FormData(form));
      const url = "{{ route('admin.marks-register.download') }}" + "?" + params.toString();
      window.open(url, '_blank');
    });
  }
})();

(function(){
  const classSel   = document.getElementById('class_id');
  const studentSel = document.getElementById('student_id');
  if (!classSel || !studentSel) return;

  const STUDENTS_URL_BASE = @json(route('admin.marks-register.students', ['class' => 0])); // "/admin/marks_register/students/0"

  function resetStudents(placeholder) {
    studentSel.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder || 'All Students';
    studentSel.appendChild(opt);
  }

  async function loadStudentsForClass(classId, preselectId) {
    resetStudents('Loading...');
    if (!classId) { resetStudents('All Students'); return; }

    const url = STUDENTS_URL_BASE.replace(/\/0$/, '/' + encodeURIComponent(classId));
    try {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      resetStudents('All Students');
      (data || []).forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        studentSel.appendChild(opt);
      });

      // restore selection (if we came back from Search)
      if (preselectId) {
        const o = [...studentSel.options].find(o => String(o.value) === String(preselectId));
        if (o) studentSel.value = String(preselectId);
      }
    } catch (e) {
      console.error('Failed to load students:', e);
      resetStudents('Failed to load');
    }
  }

  // On page load: if a class is already selected, fetch its students and preselect the current student_id (if any)
  const initialClassId   = classSel.value;
  const initialStudentId = new URLSearchParams(location.search).get('student_id') || '';
  if (initialClassId) loadStudentsForClass(initialClassId, initialStudentId);

  // When class changes, fetch students
  classSel.addEventListener('change', () => {
    loadStudentsForClass(classSel.value, '');
  });
})();
</script>
@endpush
