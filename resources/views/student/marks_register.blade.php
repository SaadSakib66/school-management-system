@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">My Exam Result</h3></div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          {{-- No class warning --}}
          @if(!empty($noClass) && $noClass)
            <div class="alert alert-info">
              You are not assigned to any class yet.
            </div>
          @endif

          {{-- Filter --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title">Filter</h3></div>
            <div class="card-body">
              <form method="GET" action="{{ route('student.marks-register.list') }}" class="row g-3 align-items-end">
                <div class="col-md-6">
                  <label class="form-label">Exam (assigned to your class)</label>
                  <select name="exam_id" class="form-select">
                    <option value="">All Exams</option>
                    @foreach($exams as $e)
                      <option value="{{ $e->id }}" {{ ($selectedExamId ?? null) == $e->id ? 'selected' : '' }}>
                        {{ $e->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-6">
                  <button type="submit" class="btn btn-primary">Search</button>
                  <a href="{{ route('student.marks-register.list') }}" class="btn btn-success">Reset</a>
                </div>
              </form>
            </div>
          </div>

          {{-- Results --}}
          @forelse($sections as $sec)
            <div class="card mb-4">
              <div class="card-header">
                <strong>{{ $sec['exam']->name }}</strong>
              </div>

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
                      @foreach($sec['rows'] as $r)
                        <tr>
                          <td>{{ $r['subject'] }}</td>
                          <td>{{ $r['class_work'] }}</td>
                          <td>{{ $r['test_work'] }}</td>
                          <td>{{ $r['home_work'] }}</td>
                          <td>{{ $r['exam'] }}</td>
                          <td><strong>{{ $r['total'] }}</strong></td>
                          <td>{{ $r['passing_mark'] }}</td>
                          <td>{{ $r['full_mark'] }}</td>
                          <td class="{{ $r['result'] === 'Pass' ? 'text-success' : 'text-danger' }}">
                            {{ $r['result'] }}
                          </td>
                        </tr>
                      @endforeach

                      {{-- Footer row (grand) --}}
                      <tr>
                        <th colspan="5" class="text-end">Grand Total:</th>
                        <th>{{ $sec['grandTotal'] }}/{{ $sec['grandFull'] }}</th>
                        <th colspan="2" class="text-end">Percentage:</th>
                        <th>{{ $sec['percentage'] !== null ? $sec['percentage'].'%' : '-' }}</th>
                      </tr>
                      <tr>
                        <th colspan="8" class="text-end">Overall Result:</th>
                        <th class="{{ $sec['overall'] === 'Pass' ? 'text-success' : 'text-danger' }}">
                          {{ $sec['overall'] }}
                        </th>
                      </tr>
                      <tr>
                        <th colspan="8" class="text-end">Grade:</th>
                        <th>
                            {{ (isset($sec['overall']) && $sec['overall'] === 'Fail')
                                ? 'F'
                                : ($sec['grade'] ?? '-') }}
                        </th>
                      </tr>

                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          @empty
            <div class="alert alert-secondary">
              No results to show. Choose an exam from the filter above.
            </div>
          @endforelse

        </div>
      </div>
    </div>
  </div>
</main>
@endsection
