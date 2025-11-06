{{-- resources/views/admin/communicate/email/send.blade.php --}}
@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">{{ $header_title ?? 'Send Email' }}</h3>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      @include('admin.message')

      @if(session('error_details'))
        <div class="alert alert-warning" style="white-space:pre-wrap">{{ session('error_details') }}</div>
      @endif

      <div class="card card-primary card-outline">
        <form action="{{ route('admin.email.send') }}" method="POST" enctype="multipart/form-data">
          @csrf

          <div class="card-body">

            {{-- ======= Row: Role | Recipients ======= --}}
            <div class="row g-3 align-items-start">
              {{-- Role (left) --}}
              <div class="col-lg-3 col-md-4 col-12">
                <label class="form-label mb-1">Role</label>
                <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                  <option value="">-- Select --</option>
                  <option value="student" {{ old('role') == 'student' ? 'selected' : '' }}>Student</option>
                  <option value="teacher" {{ old('role') == 'teacher' ? 'selected' : '' }}>Teacher</option>
                  <option value="parent" {{ old('role') == 'parent' ? 'selected' : '' }}>Parent</option>
                </select>
                @error('role')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <div class="form-check form-switch mt-3">
                  <input class="form-check-input" type="checkbox" id="send_all" name="send_all" value="1"
                         {{ old('send_all') ? 'checked' : '' }}>
                  <label class="form-check-label" for="send_all">Send to all in this role</label>
                </div>
              </div>

              {{-- Recipients (right) --}}
              <div class="col-lg-9 col-md-8 col-12">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label mb-0">Recipients</label>
                  <small class="text-muted" id="recipients-count">0 selected</small>
                </div>
                <select id="recipients" name="recipients[]"
                        class="form-select @error('recipients') is-invalid @enderror"
                        multiple></select>
                @error('recipients')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @error('recipients.*')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted d-block mt-1">
                  Type to search; you can select multiple. Disabled when "Send to all in this role" is ON or no role is selected.
                </small>
              </div>
            </div>

            <hr class="my-4">

            {{-- Subject --}}
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label mb-1">Subject</label>
                <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                       value="{{ old('subject') }}" required>
                @error('subject')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Message --}}
              <div class="col-12">
                <label class="form-label mb-1">Message</label>
                <textarea name="message" id="message" class="summernote @error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                @error('message')
                  <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
              </div>

              {{-- Attachments --}}
              <div class="col-12">
                <label class="form-label mb-1">Attachments (optional)</label>
                <input type="file" name="attachments[]" class="form-control" multiple>
                <small class="text-muted">Max 5MB per file.</small>
              </div>
            </div>
          </div>

          <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-paper-plane me-1"></i> Send
            </button>
            <a href="{{ route('admin.email.logs') }}" class="btn btn-outline-secondary">
              <i class="fas fa-list me-1"></i> Logs
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
@endsection

@push('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css">
  <style>
    /* Summernote editor min height */
    .note-editor .note-editable {
      min-height: 280px;
    }

    /* Select2 recipients styling - compact when empty/disabled */
    .select2-container--default .select2-selection--multiple {
      min-height: 38px !important;
      padding: 4px 8px;
      border-color: var(--bs-border-color, #ced4da);
    }

    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
      display: flex;
      flex-wrap: wrap;
      gap: 0.25rem;
      align-items: center;
    }

    /* Show the search input */
    .select2-container--default .select2-selection--multiple .select2-search--inline {
      display: inline-block !important;
    }

    .select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
      min-width: 200px !important;
      height: 28px !important;
      margin: 0 !important;
      padding: 0 4px !important;
    }

    /* Ensure proper alignment and spacing */
    .select2-container {
      max-width: 100%;
    }

    /* Keep select2 dropdown compact */
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
      margin: 2px 0;
      padding: 2px 8px;
      font-size: 0.875rem;
    }
  </style>
@endpush

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
  <script>
    $(function () {
      // Initialize Summernote
      $('.summernote').summernote({
        height: 280,
        toolbar: [
          ['style', ['style']],
          ['font', ['bold', 'underline', 'clear']],
          ['color', ['color']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['table', ['table']],
          ['insert', ['link', 'picture']],
          ['view', ['fullscreen', 'codeview', 'help']]
        ]
      });

      // Cache DOM elements
      const $role = $('#role');
      const $recs = $('#recipients');
      const $all = $('#send_all');
      const $count = $('#recipients-count');
      const url = "{{ route('admin.email.recipients') }}";

      /**
       * Update the recipient count display
       */
      function updateCount() {
        const val = $recs.val() || [];
        $count.text((val.length || 0) + ' selected');
      }

      /**
       * Initialize Select2 for recipients
       */
      $recs.select2({
        width: '100%',
        placeholder: 'Select recipients…',
        multiple: true,
        allowClear: true,
        dropdownParent: $('body'),
        minimumInputLength: 0,
        ajax: {
          url: url,
          dataType: 'json',
          delay: 200,
          data: function(params) {
            return {
              q: params.term || '',
              role: $role.val() || ''
            };
          },
          processResults: function(data) {
            return {
              results: (data && data.results) ? data.results : []
            };
          },
          cache: true
        }
      }).on('change', updateCount);

      /**
       * Enable/disable recipients based on role selection and "send all" checkbox
       */
      function refreshRecipientsState() {
        const roleVal = $role.val();
        const disable = $all.is(':checked') || !roleVal;

        $recs.prop('disabled', disable);

        if (disable) {
          // Clear selection when disabled
          $recs.val(null).trigger('change');
        }

        updateCount();
      }

      // Event listeners
      $role.on('change', refreshRecipientsState);
      $all.on('change', refreshRecipientsState);

      // Initialize state on page load
      refreshRecipientsState();
    });
  </script>
@endpush
