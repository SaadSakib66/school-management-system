@extends('admin.layout.layout')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Add School</h3>
    <a href="{{ route('superadmin.schools.index') }}" class="btn btn-outline-secondary">Back</a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger">
      <div class="fw-semibold mb-1">Please fix the following:</div>
      <ul class="mb-0">
        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('superadmin.schools.store') }}" enctype="multipart/form-data" class="card shadow-sm">
    @csrf
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Short Code</label>
          <input type="text" name="short_name" value="{{ old('short_name') }}" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">EIIN</label>
          <input type="text" name="eiin_num" value="{{ old('eiin_num') }}" class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label">Category</label>
          <input type="text" name="category" value="{{ old('category') }}" class="form-control" placeholder="e.g., Government / Non-Government / Private">
        </div>
        <div class="col-md-4">
          <label class="form-label">Email</label>
          <input type="email" name="email" value="{{ old('email') }}" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
        </div>

        <div class="col-md-8">
          <label class="form-label">Address</label>
          <input type="text" name="address" value="{{ old('address') }}" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Website</label>
          <input type="url" name="website" value="{{ old('website') }}" class="form-control" placeholder="https://example.com">
        </div>

        <div class="col-md-6">
          <label class="form-label">Logo</label>
          <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
          <div class="form-text">Max 2MB. JPG/PNG/WEBP.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="1" {{ old('status','1')=='1' ? 'selected':'' }}>Active</option>
            <option value="0" {{ old('status')=='0' ? 'selected':'' }}>Inactive</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-end gap-2">
      <a href="{{ route('superadmin.schools.index') }}" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save</button>
    </div>
  </form>
</div>
@endsection
