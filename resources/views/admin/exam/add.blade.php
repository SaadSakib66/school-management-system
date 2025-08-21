@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">{{ $header_title ?? 'Exam' }}</h3>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-8">

          @include('admin.message')

          <div class="card card-primary card-outline">
            <div class="card-header">
              <h3 class="card-title">{{ isset($exam) && $exam ? 'Edit Exam' : 'Add Exam' }}</h3>
            </div>

            <form method="POST"
                  action="{{ (isset($exam) && $exam) ? route('admin.exam.update', $exam->id)
                                                     : route('admin.exam.add-exam') }}">
              @csrf

              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label">Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control"
                         value="{{ old('name', $exam->name ?? '') }}" required>
                  @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="mb-3">
                  <label class="form-label">Note</label>
                  <textarea name="note" class="form-control" rows="4">{{ old('note', $exam->note ?? '') }}</textarea>
                  @error('note') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
              </div>

              <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  {{ (isset($exam) && $exam) ? 'Update' : 'Create' }}
                </button>
                <a href="{{ route('admin.exam.list') }}" class="btn btn-secondary">Cancel</a>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>

@endsection
