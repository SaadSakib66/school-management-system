@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col"><h3 class="mb-0">Edit Fee Component</h3></div>
        <div class="col text-end">
          <a href="{{ route('admin.fees.components.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      @include('admin.message')

      <form action="{{ route('admin.fees.components.update', $component->id) }}" method="post" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="card card-warning card-outline">
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name',$component->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="col-md-6">
                <label class="form-label">Slug (optional)</label>
                <input type="text" name="slug" value="{{ old('slug',$component->slug) }}" class="form-control @error('slug') is-invalid @enderror">
                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-4">
                <label class="form-label">Frequency <span class="text-danger">*</span></label>
                <select name="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
                  @php $freq = old('frequency',$component->frequency); @endphp
                  <option value="one_time" {{ $freq=='one_time'?'selected':'' }}>One time</option>
                  <option value="monthly" {{ $freq=='monthly'?'selected':'' }}>Monthly</option>
                  <option value="term" {{ $freq=='term'?'selected':'' }}>Per term</option>
                  <option value="annual" {{ $freq=='annual'?'selected':'' }}>Annual</option>
                </select>
                @error('frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-4">
                <label class="form-label">Calc Type <span class="text-danger">*</span></label>
                <select name="calc_type" class="form-select @error('calc_type') is-invalid @enderror" required>
                  @php $ct = old('calc_type',$component->calc_type); @endphp
                  <option value="fixed" {{ $ct=='fixed'?'selected':'' }}>Fixed</option>
                  <option value="percent_of_base" {{ $ct=='percent_of_base'?'selected':'' }}>% of base</option>
                </select>
                @error('calc_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-4">
                <label class="form-label">Default Amount (optional)</label>
                <input type="number" step="0.01" min="0" name="default_amount"
                       value="{{ old('default_amount',$component->default_amount) }}"
                       class="form-control @error('default_amount') is-invalid @enderror">
                @error('default_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">If “% of base”, 10 means 10% of base tuition.</div>
              </div>

              <div class="col-md-12">
                <label class="form-label">Notes (optional)</label>
                <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes',$component->notes) }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-md-12">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="status" name="status" value="1" {{ old('status',$component->status)?'checked':'' }}>
                  <label class="form-check-label" for="status">Active</label>
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer d-flex gap-2">
            <button class="btn btn-primary">Update</button>
            <a href="{{ route('admin.fees.components.index') }}" class="btn btn-secondary">Cancel</a>
          </div>
        </div>
      </form>

    </div>
  </div>
</main>
@endsection
