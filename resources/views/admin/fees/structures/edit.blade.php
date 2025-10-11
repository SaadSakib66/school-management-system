@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0">Edit Fee Structure</h3></div>
        <div class="col-sm-6 text-sm-end">
          <a href="{{ route('admin.fees.structures.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row"><div class="col-md-8">

        @include('admin.message')

        <div class="card card-primary card-outline">
          <div class="card-header"><h3 class="card-title">Fee Structure Details</h3></div>
          <form method="POST" action="{{ route('admin.fees.structures.update', $structure->id) }}">
            @csrf @method('PUT')
            <div class="card-body row g-3">

              <div class="col-md-6">
                <label class="form-label">Class <span class="text-danger">*</span></label>
                <select name="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
                  <option value="">-- Select Class --</option>
                  @foreach($classes as $c)
                    <option value="{{ $c->id }}"
                      {{ old('class_id', $structure->class_id)==$c->id ? 'selected' : '' }}>
                      {{ $c->name }}
                    </option>
                  @endforeach
                </select>
                @error('class_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-6">
                <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                <input type="text" name="academic_year"
                       class="form-control @error('academic_year') is-invalid @enderror"
                       placeholder="2025-2026"
                       value="{{ old('academic_year', $structure->academic_year) }}" required>
                @error('academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              {{-- Annual fee as input (server derives monthly = annual/12) --}}
              <div class="col-md-6">
                <label class="form-label">Annual Fee <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="annual_fee"
                       class="form-control @error('annual_fee') is-invalid @enderror"
                       value="{{ old('annual_fee', isset($annualFee) ? number_format($annualFee, 2, '.', '') : '') }}"
                       required id="annualFee">
                @error('annual_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              {{-- Monthly is preview of annual/12 --}}
              <div class="col-md-6">
                <label class="form-label">Monthly Fee (auto)</label>
                <input type="text" class="form-control" id="monthlyFee"
                       value="{{ number_format($structure->monthly_fee, 2) }}" readonly>
                <div class="form-text">Calculated as Annual ÷ 12 (preview only; server computes on save).</div>
              </div>

              <div class="col-md-3">
                <label class="form-label">Effective From</label>
                <input type="date" name="effective_from"
                       class="form-control @error('effective_from') is-invalid @enderror"
                       value="{{ old('effective_from', $structure->effective_from?->format('Y-m-d')) }}">
                @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-3">
                <label class="form-label">Effective To</label>
                <input type="date" name="effective_to"
                       class="form-control @error('effective_to') is-invalid @enderror"
                       value="{{ old('effective_to', $structure->effective_to?->format('Y-m-d')) }}">
                @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

            </div>
            <div class="card-footer d-flex gap-2">
              <button class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
              <a href="{{ route('admin.fees.structures.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
          </form>
        </div>

      </div></div>
    </div>
  </div>
</main>

@push('scripts')
<script>
  (function () {
    const annual = document.getElementById('annualFee');
    const monthly = document.getElementById('monthlyFee');
    const calc = () => {
      const a = parseFloat(annual.value);
      if (!isNaN(a)) monthly.value = (a / 12).toFixed(2); else monthly.value = '';
    };
    annual.addEventListener('input', calc);
    calc();
  })();
</script>
@endpush
@endsection
