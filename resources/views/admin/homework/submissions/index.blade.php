@extends('admin.layout.layout')
@section('content')

@php
  use Illuminate\Support\Str;
  $due = $homework->submission_date ? $homework->submission_date->endOfDay() : null;
@endphp

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">Submitted Homework</h3>
          <div class="text-muted small mt-1">
            <strong>Class:</strong> {{ $homework->class->name ?? '—' }} &nbsp; | &nbsp;
            <strong>Subject:</strong> {{ $homework->subject->name ?? '—' }} &nbsp; | &nbsp;
            <strong>Homework Date:</strong> {{ optional($homework->homework_date)->format('d-m-Y') ?? '—' }} &nbsp; | &nbsp;
            <strong>Due:</strong> {{ optional($homework->submission_date)->format('d-m-Y') ?? '—' }}
          </div>
        </div>
        <div class="col-sm-6">
          <a class="btn btn-outline-secondary float-sm-end" href="{{ route('admin.homework.list') }}">Back to List</a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          {{-- FILTERS --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title">Filter</h3></div>
            <div class="card-body">
              <form method="GET" action="{{ route('admin.homework.submissions.index', $homework->id) }}">
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label">Student / Roll</label>
                    <input type="text" name="q" class="form-control" placeholder="Name, email or roll number" value="{{ request('q') }}">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Created From</label>
                    <input type="date" name="created_from" class="form-control" value="{{ request('created_from') }}">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Created To</label>
                    <input type="date" name="created_to" class="form-control" value="{{ request('created_to') }}">
                  </div>
                  <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="{{ route('admin.homework.submissions.index', $homework->id) }}" class="btn btn-outline-secondary">Reset</a>
                  </div>
                </div>
              </form>
            </div>
          </div>

          {{-- TABLE --}}
          <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Students in {{ $homework->class->name ?? 'Class' }}</h3></div>

            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Student Name</th>
                      <th>Roll Number</th>
                      <th>Teacher Document</th>
                      <th>Text Content</th>
                      <th>Student Attachment</th>
                      <th>Submitted At</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($students as $row)
                      @php
                        $first = $row->name ?? $row->first_name ?? '';
                        $last  = $row->last_name ?? '';
                        $full  = trim($first.' '.$last);

                        $submittedAt = $row->submitted_at ? \Carbon\Carbon::parse($row->submitted_at) : null;

                        // Status
                        if ($row->submission_id) {
                          if ($due) {
                            $status = $submittedAt && $submittedAt->gt($due) ? 'Late' : 'On-time';
                          } else {
                            $status = 'On-time';
                          }
                        } else {
                          if ($due) {
                            $status = now()->gt($due) ? 'Late' : 'Open';
                          } else {
                            $status = 'Open';
                          }
                        }

                        // Clean plain text preview from text_content (joined column)
                        $plain = $row->text_content ? trim(Str::limit(strip_tags(html_entity_decode($row->text_content, ENT_QUOTES | ENT_HTML5, 'UTF-8')), 120)) : '—';
                      @endphp
                      <tr>
                        <td>{{ ($students->currentPage()-1)*$students->perPage()+$loop->iteration }}</td>
                        <td>{{ $full !== '' ? $full : '—' }}</td>
                        <td>{{ $row->roll_number ?? '—' }}</td>
                        <td>
                          @if($homework->document_file)
                            <a href="{{ route('admin.homework.download', $homework->id) }}" class="btn btn-sm btn-outline-primary">Download</a>
                          @else — @endif
                        </td>
                        <td title="{{ $plain }}">{{ $plain }}</td>
                        <td>
                          @if($row->submission_id && $row->attachment)
                            <a href="{{ route('admin.homework.submissions.download', $row->submission_id) }}" class="btn btn-sm btn-outline-primary">Download</a>
                          @else — @endif
                        </td>
                        <td>{{ $submittedAt ? $submittedAt->format('d-m-Y H:i') : '—' }}</td>
                        <td>
                          <span class="badge
                            @if($status === 'Open') bg-success
                            @elseif($status === 'On-time') bg-primary
                            @else bg-danger @endif">
                            {{ $status }}
                          </span>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="8" class="text-center py-4">No students found.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>

              @if ($students->count())
                <div class="px-3 py-2">
                  <p class="text-center mb-1">
                    Showing {{ $students->firstItem() }} to {{ $students->lastItem() }} of {{ $students->total() }} students
                  </p>
                  <div class="d-flex justify-content-center">
                    {{ $students->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                  </div>
                </div>
              @endif

            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>

@endsection
