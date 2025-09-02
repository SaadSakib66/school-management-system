@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">{{ isset($homework) ? 'Edit Homework' : 'Add New Homework' }}</h3>
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
              action="{{ isset($homework) ? route('teacher.homework.update', $homework->id) : route('teacher.homework.store') }}"
              method="POST" enctype="multipart/form-data">
              @csrf
              @if(isset($homework)) @method('PUT') @endif

              <div class="card-body">
                @php
                  $hwDate   = old('homework_date', isset($homework) ? optional($homework->homework_date)->format('Y-m-d') : '');
                  $subDate  = old('submission_date', isset($homework) ? optional($homework->submission_date)->format('Y-m-d') : '');
                  $selectedClassId = old('class_id', $selectedClassId ?? ($homework->class_id ?? null));
                @endphp

                {{-- Class: only teacher's classes --}}
                <div class="mb-3">
                  <label for="class_id" class="form-label">Class</label>
                  <select id="class_id" name="class_id" class="form-control @error('class_id') is-invalid @enderror" required>
                    <option value="">-- Select Class --</option>
                    @foreach ($getClass as $c)
                      <option value="{{ $c->id }}" {{ (string)$selectedClassId === (string)$c->id ? 'selected' : '' }}>
                        {{ $c->name ?? $c->class_name ?? ('Class #'.$c->id) }}
                      </option>
                    @endforeach
                  </select>
                  @error('class_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Subject (restricted to class subjects) --}}
                <div class="mb-3">
                  <label for="subject_id" class="form-label">Subject</label>
                  <select id="subject_id" name="subject_id" class="form-control @error('subject_id') is-invalid @enderror" required>
                    <option value="">-- Select Subject --</option>
                    @foreach ($getSubject as $s)
                      <option value="{{ $s->id }}" {{ old('subject_id', $homework->subject_id ?? '') == $s->id ? 'selected' : '' }}>
                        {{ $s->name ?? $s->subject_name ?? ('Subject #'.$s->id) }}
                      </option>
                    @endforeach
                  </select>
                  @error('subject_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Homework Date --}}
                <div class="mb-3">
                  <label for="homework_date" class="form-label">Homework Date</label>
                  <input type="date" id="homework_date" name="homework_date"
                         class="form-control @error('homework_date') is-invalid @enderror"
                         value="{{ $hwDate }}" required>
                  @error('homework_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Submission Date --}}
                <div class="mb-3">
                  <label for="submission_date" class="form-label">Submission Date</label>
                  <input type="date" id="submission_date" name="submission_date"
                         class="form-control @error('submission_date') is-invalid @enderror"
                         value="{{ $subDate }}">
                  @error('submission_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Document --}}
                <div class="mb-3">
                  <label for="document_file" class="form-label">Document</label>
                  <input type="file" id="document_file" name="document_file"
                         class="form-control @error('document_file') is-invalid @enderror"
                         accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip">
                  @error('document_file') <div class="invalid-feedback">{{ $message }}</div> @enderror

                  @if(isset($homework) && $homework->document_file)
                    <div class="form-text mt-1">
                      Current: <a href="{{ route('teacher.homework.download', $homework->id) }}">Download file</a>
                    </div>
                    <div class="form-check mt-2">
                      <input class="form-check-input" type="checkbox" value="1" id="remove_file" name="remove_file">
                      <label class="form-check-label" for="remove_file">Remove current file</label>
                    </div>
                  @endif
                </div>

                {{-- Description (Summernote) --}}
                <div class="mb-3">
                  <label for="description" class="form-label">Description</label>
                  <textarea id="description" name="description" class="summernote @error('description') is-invalid @enderror">
                    {!! old('description', $homework->description ?? '') !!}
                  </textarea>
                  @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
              </div>

              <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                  {{ isset($homework) ? 'Update' : 'Submit' }}
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
        placeholder: 'Write homework details...',
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

    // Class -> Subject AJAX
    document.addEventListener('DOMContentLoaded', function () {
      const classSelect   = document.getElementById('class_id');
      const subjectSelect = document.getElementById('subject_id');
      const oldSubjectId  = "{{ old('subject_id', $homework->subject_id ?? '') }}";

      function renderEmpty() {
        subjectSelect.innerHTML = '<option value="">-- Select Subject --</option>';
      }

      function loadSubjects(classId) {
        if (!classId) { renderEmpty(); return; }
        subjectSelect.innerHTML = '<option value="">Loading...</option>';
        fetch("{{ route('teacher.homework.class_subjects') }}?class_id=" + classId, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(rows => {
          renderEmpty();
          rows.forEach(row => {
            const opt = document.createElement('option');
            opt.value = row.id;
            opt.textContent = row.name;
            if (oldSubjectId && oldSubjectId == row.id) opt.selected = true;
            subjectSelect.appendChild(opt);
          });
        })
        .catch(() => renderEmpty());
      }

      if (classSelect.value && subjectSelect.options.length <= 1) {
        loadSubjects(classSelect.value);
      }

      classSelect.addEventListener('change', function () {
        loadSubjects(this.value);
      });
    });
  </script>
@endpush
