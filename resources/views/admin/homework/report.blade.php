@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row"><div class="col-sm-6"><h3 class="mb-0">Homework Report</h3></div></div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      {{-- Search / Filter --}}
      <div class="card card-primary card-outline mb-4">
        <div class="card-header"><h3 class="card-title">Search Homework Report</h3></div>
        <div class="card-body">
          <form method="GET" action="{{ route('admin.homework.report') }}">
            <div class="row g-2">
              <div class="col-md-3">
                <label class="form-label">Student First Name</label>
                <input type="text" name="student_first_name" class="form-control"
                       value="{{ request('student_first_name') }}" placeholder="Student First Name">
              </div>
              <div class="col-md-3">
                <label class="form-label">Student Last Name</label>
                <input type="text" name="student_last_name" class="form-control"
                       value="{{ request('student_last_name') }}" placeholder="Student Last Name">
              </div>
              <div class="col-md-3">
                <label class="form-label">Class</label>
                <select name="class_id" class="form-control">
                  <option value="">Select Class</option>
                  @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected(request('class_id')==$c->id)>{{ $c->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Subject</label>
                <select name="subject_id" class="form-control">
                  <option value="">Subject Name</option>
                  @foreach($subjects as $s)
                    <option value="{{ $s->id }}" @selected(request('subject_id')==$s->id)>{{ $s->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="row g-2 mt-2">
              <div class="col-md-2">
                <label class="form-label">From Homework Date</label>
                <input type="date" name="from_homework_date" class="form-control" value="{{ request('from_homework_date') }}">
              </div>
              <div class="col-md-2">
                <label class="form-label">To Homework Date</label>
                <input type="date" name="to_homework_date" class="form-control" value="{{ request('to_homework_date') }}">
              </div>
              <div class="col-md-2">
                <label class="form-label">From Submission Date</label>
                <input type="date" name="from_submission_date" class="form-control" value="{{ request('from_submission_date') }}">
              </div>
              <div class="col-md-2">
                <label class="form-label">To Submission Date</label>
                <input type="date" name="to_submission_date" class="form-control" value="{{ request('to_submission_date') }}">
              </div>
              <div class="col-md-2">
                <label class="form-label">From Submitted Created Date</label>
                <input type="date" name="from_submitted_created_date" class="form-control" value="{{ request('from_submitted_created_date') }}">
              </div>
              <div class="col-md-2">
                <label class="form-label">To Submitted Created Date</label>
                <input type="date" name="to_submitted_created_date" class="form-control" value="{{ request('to_submitted_created_date') }}">
              </div>
            </div>

            <div class="mt-3">
              <button class="btn btn-primary">Search</button>
              <a href="{{ route('admin.homework.report') }}" class="btn btn-success">Reset</a>
            </div>
          </form>
        </div>
      </div>

      {{-- Report Table --}}
      <div class="card card-outline">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Student Name</th>
                  <th>Class</th>
                  <th>Subject</th>
                  <th>Homework Date</th>
                  <th>Submission Date</th>
                  <th>Document</th>
                  <th>Description</th>
                  <th>Created Date</th>
                  <th>Submitted Document</th>
                  <th>Submitted Description</th>
                  <th>Submitted Created Date</th>
                </tr>
              </thead>
              <tbody>
                @forelse($submissions as $idx => $sub)
                  @php
                    $hw = $sub->homework;
                    $student = $sub->student;
                    $submittedDate = $sub->submitted_at ?? $sub->created_at;
                  @endphp
                  <tr>
                    <td>{{ ($submissions->currentPage()-1)*$submissions->perPage() + $idx + 1 }}</td>
                    <td>{{ trim(($student->name ?? '').' '.($student->last_name ?? '')) }}</td>
                    <td>{{ $hw->class->name ?? '' }}</td>
                    <td>{{ $hw->subject->name ?? '' }}</td>
                    <td>{{ optional($hw->homework_date)->format('d-m-Y') }}</td>
                    <td>{{ optional($hw->submission_date)->format('d-m-Y') }}</td>

                    <td>
                      @if($hw->document_file)
                        <a class="btn btn-sm btn-primary"
                           href="{{ route('admin.homework.report.download.homework', $hw->id) }}">
                          Download
                        </a>
                      @endif
                    </td>

                    <td style="max-width:240px">{{ $hw->description_plain }}</td>
                    <td>{{ optional($hw->created_at)->format('d-m-Y') }}</td>

                    <td>
                      @if($sub->attachment)
                        <a class="btn btn-sm btn-primary"
                           href="{{ route('admin.homework.report.download.submission', $sub->id) }}">
                          Download
                        </a>
                      @endif
                    </td>

                    <td style="max-width:240px">{{ $sub->text_plain }}</td>
                    <td>{{ optional($submittedDate)->format('d-m-Y') }}</td>
                  </tr>
                @empty
                  <tr><td colspan="12" class="text-center">No Homework Reports Found</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{ $submissions->links() }}
        </div>
      </div>

    </div>
  </div>
</main>
@endsection
