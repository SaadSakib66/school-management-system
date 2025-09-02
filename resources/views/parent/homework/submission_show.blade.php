@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">Child Submission</h3>
          <div class="text-muted small mt-1">
            @php
              $studentName = trim(($student->name ?? $student->first_name ?? '').' '.($student->last_name ?? ''));
            @endphp
            <strong>Student:</strong> {{ $studentName !== '' ? $studentName : '—' }} &nbsp; | &nbsp;
            <strong>Class:</strong> {{ $homework->class->name ?? '—' }} &nbsp; | &nbsp;
            <strong>Subject:</strong> {{ $homework->subject->name ?? '—' }} &nbsp; | &nbsp;
            <strong>Homework:</strong> {{ optional($homework->homework_date)->format('d-m-Y') ?? '—' }} &nbsp; | &nbsp;
            <strong>Due:</strong> {{ optional($homework->submission_date)->format('d-m-Y') ?? '—' }}
          </div>
        </div>
        <div class="col-sm-6">
          <a class="btn btn-outline-secondary float-sm-end" href="{{ $backUrl }}">Back</a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row g-4">
        <div class="col-md-12">

          @include('admin.message')

          <div class="card card-primary card-outline">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0">Submission</h3>
              <span class="badge
                @if(str_contains($status,'On time')) bg-primary
                @elseif(str_contains($status,'Late')) bg-danger
                @elseif(str_contains($status,'Open')) bg-success
                @else bg-secondary @endif">
                {{ $status }}
              </span>
            </div>

            <div class="card-body">
              @if($submission)
                <div class="mb-3">
                  <strong>Submitted At:</strong>
                  {{ optional($submission->submitted_at)->format('d-m-Y H:i') ?? '—' }}
                </div>

                {{-- Text answer --}}
                <div class="mb-4">
                  <label class="form-label fw-semibold">Written Answer</label>
                  <div class="border rounded p-3" style="min-height: 120px;">
                    {!! $submission->text_content ?? '<span class="text-muted">— No text content —</span>' !!}
                  </div>
                </div>

                {{-- File attachment --}}
                <div class="mb-2">
                  <label class="form-label fw-semibold">Attachment</label>
                  <div>
                    @if($submission->attachment)
                      <a href="{{ route('parent.child.submissions.download', $submission->id) }}"
                         class="btn btn-sm btn-outline-primary">Download Attachment</a>
                    @else
                      <span class="text-muted">— No attachment —</span>
                    @endif
                  </div>
                </div>

              @else
                <div class="alert alert-warning mb-0">
                  This student hasn’t submitted an answer for this homework yet.
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
