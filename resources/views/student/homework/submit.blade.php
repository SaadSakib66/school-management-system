@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">{{ $header_title }}</h3>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row g-4">
        <div class="col-md-12">
          @include('admin.message')

          <div class="card card-primary card-outline mb-4">
            <div class="card-header">
              <strong>Subject:</strong> {{ $homework->subject->name ?? '—' }} &nbsp; | &nbsp;
              <strong>Homework Date:</strong> {{ optional($homework->homework_date)->format('d-m-Y') ?? '—' }} &nbsp; | &nbsp;
              <strong>Due:</strong> {{ optional($homework->submission_date)->format('d-m-Y') ?? '—' }}
            </div>

            <form
              action="{{ route('student.homework.submit.store', $homework->id) }}"
              method="POST" enctype="multipart/form-data">
              @csrf

              <div class="card-body">
                @if($isClosed)
                  <div class="alert alert-secondary mb-3">
                    Submission window is closed. You can view your previous submission below.
                  </div>
                @endif

                {{-- Write answer --}}
                <div class="mb-3">
                  <label for="text_content" class="form-label">Write your answer</label>
                  <textarea id="text_content" name="text_content"
                            class="summernote @error('text_content') is-invalid @enderror"
                            {{ $isClosed ? 'disabled' : '' }}>{!! old('text_content', $submission->text_content ?? '') !!}</textarea>
                  @error('text_content') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                {{-- Upload file --}}
                <div class="mb-3">
                  <label for="attachment" class="form-label">Upload file (optional)</label>
                  <input type="file" id="attachment" name="attachment"
                         class="form-control @error('attachment') is-invalid @enderror"
                         accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip"
                         {{ $isClosed ? 'disabled' : '' }}>
                  @error('attachment') <div class="invalid-feedback">{{ $message }}</div> @enderror

                  @if(!empty($submission?->attachment_url))
                    <div class="form-text mt-1">
                      Current file:
                      <a href="{{ route('student.homework.submission.download', $submission->id) }}">Download</a>
                    </div>
                  @endif
                </div>
              </div>

              <div class="card-footer">
                @if(!$isClosed)
                  <button type="submit" class="btn btn-primary">Save Submission</button>
                @else
                  <a href="{{ route('student.homework.list') }}" class="btn btn-outline-secondary">Back</a>
                @endif
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@push('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css">
  <style>
    .note-editor.note-frame { border-radius: .25rem; }
    .note-editor .note-editable { min-height: 240px; }
  </style>
@endpush

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
  <script>
    $(function () {
      $('.summernote').summernote({
        placeholder: 'Write your answer...',
        height: 260,
        toolbar: [
          ['style', ['style']],
          ['font', ['bold','italic','underline','clear']],
          ['para', ['ul','ol','paragraph']],
          ['insert', ['link','picture']],
          ['view', ['codeview','help']]
        ]
      });
    });
  </script>
@endpush
