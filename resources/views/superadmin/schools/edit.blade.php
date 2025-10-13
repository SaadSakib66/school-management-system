@extends('admin.layout.layout')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Edit School</h3>
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

  <form method="POST" action="{{ route('superadmin.schools.update', $school) }}" enctype="multipart/form-data" class="card shadow-sm">
    @csrf
    @method('PUT')
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" name="name" value="{{ old('name', $school->name) }}" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Short Code</label>
          <input type="text" name="short_name" value="{{ old('short_name', $school->short_name) }}" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">EIIN</label>
          <input type="text" name="eiin_num" value="{{ old('eiin_num', $school->eiin_num) }}" class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label">Category</label>
          <input type="text" name="category" value="{{ old('category', $school->category) }}" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Email</label>
          <input type="email" name="email" value="{{ old('email', $school->email) }}" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" value="{{ old('phone', $school->phone) }}" class="form-control">
        </div>

        <div class="col-md-8">
          <label class="form-label">Address</label>
          <input type="text" name="address" value="{{ old('address', $school->address) }}" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Website</label>
          <input type="url" name="website" value="{{ old('website', $school->website) }}" class="form-control">
        </div>

        <div class="col-md-6">
          <label class="form-label d-flex align-items-center justify-content-between">
            <span>Logo</span>
            @if($school->logo)
              <span class="small text-muted">Current:</span>
            @endif
          </label>
          <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
          <div class="form-text">Uploading a new file will replace the existing logo.</div>
          @if($school->logo)
            <div class="mt-2">
              <img src="{{ asset('storage/'.$school->logo) }}" alt="Logo" style="max-height:64px">
            </div>
          @endif
        </div>

        <div class="col-md-6">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="1" {{ old('status',$school->status)=='1' ? 'selected':'' }}>Active</option>
            <option value="0" {{ old('status',$school->status)=='0' ? 'selected':'' }}>Inactive</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card-footer d-flex justify-content-end gap-2">
      <a href="{{ route('superadmin.schools.index') }}" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Update</button>
    </div>
  </form>
</div>
@endsection
