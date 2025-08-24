@extends('admin.layout.layout') {{-- or use "teacher.layout.layout" if you have one --}}
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Marks Register (Teacher)</h3></div>
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
              <form method="GET" action="{{ route('teacher.marks-register.list') }}" class="row g-3 align-items-end">

                <div class="col-md-4">
                  <label class="form-label">Exam</label>
                  <select name="exam_id" id="exam_id" class="form-select">
                    <option value="">Select Exam</option>
                    @foreach($exams as $e)
                      <option value="{{ $e->id }}" {{ ($selectedExamId ?? null) == $e->id ? 'selected' : '' }}>
                        {{ $e->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Class (Assigned)</label>
                  <select name="class_id" id="class_id" class="form-select">
                    <option value="">Select Class</option>
                    @foreach($classes as $c)
                      <option value="{{ $c->id }}" {{ ($selectedClassId ?? null) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-4">
                  <button type="submit" class="btn btn-primary">Search</button>
                  <a href="{{ route('teacher.marks-register.list') }}" class="btn btn-success">Reset</a>
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
              <form method="POST" action="{{ route('teacher.marks-register.save') }}">
                @csrf
                <input type="hidden" name="exam_id"  value="{{ $selectedExamId }}">
                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                <input type="hidden" name="only_student_id" id="only_student_id" value="">

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
                      <tr>
                        <td>
                          <strong>{{ $stu->name }} {{ $stu->last_name }}</strong>
                        </td>

                        @foreach($subjects as $s)
                          @php
                            $m   = $marks[$stu->id][$s->id] ?? null;
                            $sch = $scheduleMap[$s->id] ?? null;
                          @endphp
                          <td class="js-subject-cell" data-full="{{ $sch?->full_mark }}">
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

                        <td class="text-end">
                          <button type="button" class="btn btn-success btn-sm"
                                  onclick="document.getElementById('only_student_id').value='{{ $stu->id }}'; this.closest('form').submit();">
                            Save
                          </button>
                        </td>

                        
                      </tr>
                    @empty
                      <tr>
                        <td colspan="{{ 2 + $subjects->count() }}" class="text-center text-muted">
                          No students found for this class.
                        </td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>

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

@push('scripts')
<script>
(function(){
  function toNum(v){ var n = parseFloat(v); return isNaN(n) ? 0 : n; }
  function updateCellTotal(cell){
    var sum = 0;
    cell.querySelectorAll('.js-mark').forEach(function(inp){ sum += toNum(inp.value); });
    var total = cell.querySelector('.js-total');
    if (total){ total.value = sum ? sum : ''; }
    var full = parseFloat(cell.dataset.full || '');
    var hint = cell.querySelector('.js-total-hint');
    if (total){ total.classList.remove('is-invalid','is-valid'); }
    if (!isNaN(full)){
      if (sum > full){ total && total.classList.add('is-invalid'); if (hint) hint.textContent = 'Total exceeds full mark (' + full + ')'; }
      else { total && total.classList.add('is-valid'); if (hint) hint.textContent = ''; }
    } else if (hint){ hint.textContent = ''; }
  }
  document.querySelectorAll('.js-subject-cell').forEach(function(cell){
    updateCellTotal(cell);
    cell.querySelectorAll('.js-mark').forEach(function(inp){
      inp.addEventListener('input', function(){ updateCellTotal(cell); });
    });
  });
})();
</script>
@endpush
