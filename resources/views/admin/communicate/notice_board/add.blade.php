@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">{{ isset($notice) ? 'Edit Notice Board' : 'Add New Notice Board' }}</h3>
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
            <form
              action="{{ isset($notice)
                        ? route('admin.notice-board.update', $notice->id)
                        : route('admin.notice-board.store') }}"
              method="POST">
              @csrf
              @if(isset($notice)) @method('PUT') @endif

              <div class="card-body">
                {{-- Title --}}
                <div class="mb-3">
                  <label for="title" class="form-label">Title</label>
                  <input
                    type="text"
                    id="title"
                    name="title"
                    class="form-control @error('title') is-invalid @enderror"
                    placeholder="Title"
                    value="{{ old('title', $notice->title ?? '') }}"
                    required>
                  @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                @php
                  // Model casts handle Carbon, we just format for <input type="date">
                  $noticeDate  = old('notice_date', isset($notice) ? optional($notice->notice_date)->format('Y-m-d') : '');
                  $publishDate = old('publish_date', isset($notice) ? optional($notice->publish_date)->format('Y-m-d') : '');

                  $selectedRecipients = collect(old('message_to', isset($notice)
                      ? (is_array($notice->message_to) ? $notice->message_to : explode(',', (string)$notice->message_to))
                      : []));
                @endphp

                {{-- Notice Date --}}
                <div class="mb-3">
                  <label for="notice_date" class="form-label">Notice Date</label>
                  <input
                    type="date"
                    id="notice_date"
                    name="notice_date"
                    class="form-control @error('notice_date') is-invalid @enderror"
                    value="{{ $noticeDate }}"
                    required>
                  @error('notice_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                {{-- Publish Date --}}
                <div class="mb-3">
                  <label for="publish_date" class="form-label">Publish Date</label>
                  <input
                    type="date"
                    id="publish_date"
                    name="publish_date"
                    class="form-control @error('publish_date') is-invalid @enderror"
                    value="{{ $publishDate }}"
                    required>
                  @error('publish_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                {{-- Message To --}}
                <div class="mb-3">
                  <label class="form-label d-block">Message To</label>

                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="to_student"
                           name="message_to[]" value="student"
                           {{ $selectedRecipients->contains('student') ? 'checked' : '' }}>
                    <label class="form-check-label" for="to_student">Student</label>
                  </div>

                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="to_teacher"
                           name="message_to[]" value="teacher"
                           {{ $selectedRecipients->contains('teacher') ? 'checked' : '' }}>
                    <label class="form-check-label" for="to_teacher">Teacher</label>
                  </div>

                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="to_parent"
                           name="message_to[]" value="parent"
                           {{ $selectedRecipients->contains('parent') ? 'checked' : '' }}>
                    <label class="form-check-label" for="to_parent">Parent</label>
                  </div>

                  @error('message_to')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                  @enderror
                </div>

                {{-- Message (Summernote) --}}
                <div class="mb-3">
                  <label for="message" class="form-label">Message</label>
                  <textarea id="message" name="message"
                            class="summernote @error('message') is-invalid @enderror">{!! old('message', $notice->message ?? '') !!}</textarea>
                  @error('message')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                  {{ isset($notice) ? 'Update' : 'Submit' }}
                </button>
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
  {{-- Summernote (Bootstrap 5 build) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css">
  <style>
    .note-editor.note-frame { border-radius: .25rem; }
    .note-editor .note-editable { min-height: 240px; }
  </style>
@endpush

@push('scripts')
  {{-- Summernote (Bootstrap 5 build) --}}
  <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
  <script>
    $(function () {
      $('.summernote').summernote({
        placeholder: 'Write your notice...',
        height: 260,
        toolbar: [
          ['style', ['style']],
          ['font', ['bold','italic','underline','clear']],
          ['fontname', ['fontname']],
          ['para', ['ul','ol','paragraph']],
          ['insert', ['link','picture']],
          ['view', ['codeview','help']]
        ],
        fontNames: ['Source Sans Pro','Arial','Helvetica','Times New Roman','Courier New'],
        fontNamesIgnoreCheck: ['Source Sans Pro']
      });
    });
  </script>
@endpush
