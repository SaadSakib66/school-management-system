@extends('admin.layout.layout')
@section('content')

<main class="app-main">
    {{-- Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ $header_title }}</h3></div>
            </div>
        </div>
    </div>

    {{-- Body --}}
    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card card-primary card-outline mb-4">

                        {{-- IMPORTANT: include the {id} --}}
                        <form action="{{ route('admin.assign-class-teacher.update-single-teacher', $assignTeacher->id) }}" method="POST">
                            @csrf

                            <div class="card-body">

                                {{-- Show Class (read-only) --}}
                                <div class="mb-3">
                                    <label class="form-label">Class</label>
                                    <input type="text" class="form-control"
                                           value="{{ $assignTeacher->class->name ?? 'N/A' }}" readonly>
                                </div>

                                {{-- Teacher dropdown (change single teacher) --}}
                                <div class="mb-3">
                                    <label class="form-label">Teacher</label>
                                    <input type="text" class="form-control" value="{{ $assignTeacher->teacher->name }} {{ $assignTeacher->teacher->last_name }}" readonly>
                                </div>

                                {{-- Status Dropdown --}}
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status" id="status" required>
                                        @foreach ([1 => 'Active', 0 => 'Inactive'] as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ (string)old('status', $assignTeacher->status ?? '') === (string)$key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>

                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('admin.assign-class-teacher.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@endsection
