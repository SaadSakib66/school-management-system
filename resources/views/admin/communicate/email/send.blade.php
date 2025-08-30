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

      <div class="card card-primary card-outline">
        <form action="{{ route('admin.email.send') }}" method="POST" enctype="multipart/form-data">
          @csrf

          <div class="card-body">
            <div class="row g-3">
              {{-- Role --}}
              <div class="col-md-3">
                <label class="form-label">Role</label>
                <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
                  <option value="">-- Select --</option>
                  <option value="student" {{ old('role')=='student'?'selected':'' }}>Student</option>
                  <option value="teacher" {{ old('role')=='teacher'?'selected':'' }}>Teacher</option>
                  <option value="parent"  {{ old('role')=='parent'?'selected':'' }}>Parent</option>
                </select>
                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Recipient (Select2 AJAX) --}}
              <div class="col-md-9">
                <label class="form-label">Recipient</label>
                <select id="recipient" name="recipient" class="form-select @error('recipient') is-invalid @enderror" required></select>
                @error('recipient') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Subject --}}
              <div class="col-12">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                       value="{{ old('subject') }}" required>
                @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              {{-- Message --}}
              <div class="col-12">
                <label class="form-label">Message</label>
                <textarea name="message" id="message" class="summernote @error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                @error('message') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
              </div>

              {{-- Attachments --}}
              <div class="col-12">
                <label class="form-label">Attachments (optional)</label>
                <input type="file" name="attachments[]" class="form-control" multiple>
                <small class="text-muted">Max 5MB per file.</small>
              </div>
            </div>
          </div>

          <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary">Send</button>
            <a href="{{ route('admin.email.logs') }}" class="btn btn-outline-secondary">Logs</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
@endsection

@push('styles')
  {{-- Select2 + Summernote (Bootstrap 5 build) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css">
  <style>
    .note-editor .note-editable { min-height: 260px; }
  </style>
@endpush

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
  <script>
    $(function () {
      // Summernote
      $('.summernote').summernote({ height: 260 });

      const $role   = $('#role');
      const $rec    = $('#recipient');
      const url     = "{{ route('admin.email.recipients') }}";
      const oldRec  = "{{ old('recipient') }}";

      function populatePlainSelect(options) {
        $rec.empty();
        if (!options || !options.length) {
          $rec.append(new Option('No recipients found for this role', '', false, false));
          return;
        }
        options.forEach(o => $rec.append(new Option(o.text, o.id, false, false)));
      }

      function initRecipients() {
        // tear down any previous Select2 instance
        if ($.fn.select2) { try { $rec.select2('destroy'); } catch(e){} }
        $rec.empty();

        const role = $role.val();
        if (!role) { return; }

        // Show loading placeholder
        $rec.append(new Option('Loading recipients…', '', true, true)).prop('disabled', true);

        // Preload first page
        $.ajax({
          url: url,
          data: { role, q: '' },
          dataType: 'json'
        })
        .done(function (data) {
          const results = (data && data.results) ? data.results : [];
          populatePlainSelect(results);
          $rec.prop('disabled', false);

          // Enhance with Select2 if available
          if ($.fn.select2) {
            $rec.select2({
              width: '100%',
              placeholder: 'Select a recipient…',
              allowClear: true,
              minimumInputLength: 0,
              dropdownParent: $('body'),
              ajax: {
                url: url,
                dataType: 'json',
                delay: 200,
                data: params => ({ q: params.term || '', role }),
                processResults: data => ({ results: data.results }),
                cache: true
              }
            });
          }

          // Reselect previous recipient after validation error
          if (oldRec) { $rec.val(oldRec).trigger('change'); }
        })
        .fail(function (xhr) {
          console.error('Recipients load failed:', xhr.status, xhr.responseText);
          $rec.empty()
              .append(new Option('Error loading recipients — check console/network', '', true, true))
              .prop('disabled', true);
        });
      }

      if ($role.val()) { initRecipients(); }  // restore after validation errors
      $role.on('change', initRecipients);
    });
  </script>
@endpush

