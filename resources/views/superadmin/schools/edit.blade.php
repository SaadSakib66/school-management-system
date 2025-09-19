@extends('admin.layout.layout')

@section('content')
<div class="container py-4">
  <h3>Edit School</h3>

  <form method="POST" action="{{ route('superadmin.schools.update', $school) }}" class="row g-3">
    @csrf @method('PUT')
    <div class="col-md-6">
      <label class="form-label">Name</label>
      <input type="text" name="name" class="form-control" required value="{{ old('name',$school->name) }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">Short Name</label>
      <input type="text" name="short_name" class="form-control" value="{{ old('short_name',$school->short_name) }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="1" @selected($school->status)>Active</option>
        <option value="0" @selected(!$school->status)>Inactive</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="{{ old('email',$school->email) }}">
    </div>
    <div class="col-md-4">
      <label class="form-label">Phone</label>
      <input type="text" name="phone" class="form-control" value="{{ old('phone',$school->phone) }}">
    </div>
    <div class="col-md-12">
      <label class="form-label">Address</label>
      <textarea name="address" class="form-control" rows="2">{{ old('address',$school->address) }}</textarea>
    </div>
    <div class="col-12">
      <button class="btn btn-primary">Update</button>
      <a href="{{ route('superadmin.schools.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
@endsection
