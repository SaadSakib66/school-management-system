@extends('admin.layout.layout')

@section('content')
<div class="container py-4">
  <h3>Switch School Context</h3>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

  <form method="POST" action="{{ route('superadmin.schools.switch.set') }}" class="row g-3">
    @csrf
    <div class="col-md-6">
      <label class="form-label">Select School</label>
      <select name="school_id" class="form-select" required>
        <option value="">— Select —</option>
        @foreach($schools as $s)
          <option value="{{ $s->id }}" {{ (int)$currentId === (int)$s->id ? 'selected' : '' }}>
            {{ $s->name }} {{ $s->short_name ? "({$s->short_name})" : '' }} {{ $s->status ? '' : ' — INACTIVE' }}
          </option>
        @endforeach
      </select>
    </div>
    <div class="col-12">
      <button class="btn btn-primary">Set Context</button>
      <button class="btn btn-outline-secondary" form="clear-context">Clear</button>
    </div>
  </form>

  <form id="clear-context" method="POST" action="{{ route('superadmin.schools.switch.clear') }}">
    @csrf
  </form>
</div>
@endsection
