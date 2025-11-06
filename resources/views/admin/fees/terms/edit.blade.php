@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col"><h3 class="mb-0">Edit Fee Term</h3></div>
        <div class="col text-end">
          <a href="{{ route('admin.fees.terms.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      @include('admin.message')

      {{-- Validation errors --}}
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('admin.fees.terms.update', $term->id) }}" method="post" class="card" autocomplete="off">
        @csrf
        @method('PUT')
        <div class="card-body row g-3">
          <div class="col-md-4">
            <label class="form-label">Academic Year <span class="text-danger">*</span></label>
            <input type="text" name="academic_year" value="{{ old('academic_year', $term->academic_year) }}"
                   class="form-control @error('academic_year') is-invalid @enderror" required>
            @error('academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Term Name <span class="text-danger">*</span></label>
            <input type="text" name="name" value="{{ old('name', $term->name) }}"
                   class="form-control @error('name') is-invalid @enderror" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="start_date" value="{{ old('start_date', $term->start_date?->format('Y-m-d')) }}"
                   class="form-control @error('start_date') is-invalid @enderror" required>
            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-2">
            <label class="form-label">End Date <span class="text-danger">*</span></label>
            <input type="date" name="end_date" value="{{ old('end_date', $term->end_date?->format('Y-m-d')) }}"
                   class="form-control @error('end_date') is-invalid @enderror" required>
            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-12">
            <div class="form-check">
              <input type="hidden" name="status" value="0">
              <input type="checkbox" class="form-check-input" id="status" name="status" value="1"
                     {{ old('status', $term->status) ? 'checked' : '' }}>
              <label class="form-check-label" for="status">Active</label>
            </div>
          </div>
        </div>

        <div class="card-footer d-flex gap-2">
          <button class="btn btn-primary"><i class="bi bi-check2-circle"></i> Update</button>
          <a href="{{ route('admin.fees.terms.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
      </form>

    </div>
  </div>
</main>
@endsection
