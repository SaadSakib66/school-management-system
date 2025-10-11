@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0">Generate Invoices</h3></div>
        <div class="col-sm-6 text-sm-end">
          <a href="{{ route('admin.fees.invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Invoices
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      @include('admin.message')

      {{-- Show validation errors clearly --}}
      @if ($errors->any())
        <div class="alert alert-danger">
          <div class="fw-semibold mb-1">Please fix the following:</div>
          <ul class="mb-0">
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="post" action="{{ route('admin.fees.invoices.generate') }}" class="card card-body">
        @csrf
        <div class="row g-3">

          {{-- Class --}}
          <div class="col-md-4">
            <label class="form-label">Class <span class="text-danger">*</span></label>
            <select name="class_id" class="form-select" required>
              <option value="">-- Select Class --</option>
              @foreach($classes as $c)
                <option value="{{ $c->id }}" {{ old('class_id') == $c->id ? 'selected' : '' }}>
                  {{ $c->name }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Academic Year --}}
          <div class="col-md-3">
            <label class="form-label">Academic Year <span class="text-danger">*</span></label>
            <input
              type="text"
              name="academic_year"
              class="form-control"
              placeholder="2025-2026"
              value="{{ old('academic_year') }}"
              required
            >
          </div>

          {{-- Month (select by name) --}}
          <div class="col-md-2">
            <label class="form-label">Month <span class="text-danger">*</span></label>
            <select name="month" class="form-select" required>
              <option value="">-- Month --</option>
              @foreach($months as $num => $label)
                <option value="{{ $num }}" {{ (int)old('month') === (int)$num ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Due Date (optional) --}}
          <div class="col-md-3">
            <label class="form-label">Due Date (optional)</label>
            <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
            <div class="form-text">
              Leave empty, or pick a valid date (e.g., September has 30 days, November has 30).
            </div>
          </div>

        </div>

        <div class="mt-3 d-flex gap-2">
          <button class="btn btn-primary"><i class="bi bi-gear"></i> Generate</button>
          <button type="reset" class="btn btn-warning"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
        </div>
      </form>
    </div>
  </div>
</main>
@endsection
