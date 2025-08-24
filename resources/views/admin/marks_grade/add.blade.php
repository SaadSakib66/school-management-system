@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">{{ $grade ? 'Edit Marks Grade' : 'Add Marks Grade' }}</h3>
        </div>
        <div class="col-sm-6">
          <a href="{{ route('admin.marks-grade.list') }}" class="btn btn-secondary float-sm-end">Back to List</a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-8 col-lg-6">

          @include('admin.message')

          <div class="card">
            <div class="card-body">
              <form method="POST"
                    action="{{ $grade ? route('admin.marks-grade.update-grade', $grade->id) : route('admin.marks-grade.add-grade') }}">
                @csrf

                <div class="mb-3">
                  <label class="form-label">Grade Name</label>
                  <input type="text" name="grade_name" class="form-control"
                         value="{{ old('grade_name', $grade->grade_name ?? '') }}" placeholder="Enter Grade Name">
                  @error('grade_name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Percent From</label>
                    <input type="number" name="percent_from" class="form-control" min="0" max="100" step="1"
                           value="{{ old('percent_from', $grade->percent_from ?? '') }}" placeholder="Enter Percent From(Number)">
                    @error('percent_from') <div class="text-danger small">{{ $message }}</div> @enderror
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Percent To</label>
                    <input type="number" name="percent_to" class="form-control" min="0" max="100" step="1"
                           value="{{ old('percent_to', $grade->percent_to ?? '') }}" placeholder="Enter Percent To(Number)">
                    @error('percent_to') <div class="text-danger small">{{ $message }}</div> @enderror
                  </div>
                </div>

                <div class="mt-4">
                  <button type="submit" class="btn btn-primary">{{ $grade ? 'Update' : 'Save' }}</button>
                  <a href="{{ route('admin.marks-grade.list') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>

                <div class="form-text mt-3">
                  Tip: “Percent From” must be ≤ “Percent To”, both between 0 and 100.
                </div>
              </form>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>
@endsection
