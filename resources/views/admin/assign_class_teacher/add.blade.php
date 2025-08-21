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
                <div class="col-md-8">
                    <div class="card card-primary card-outline mb-4">

                        <form action="{{ isset($assignTeacher)
                                ? route('admin.assign-class-teacher.update_teacher', $assignTeacher->id)
                                : route('admin.assign-class-teacher.add_teacher') }}"
                              method="POST">
                            @csrf

                            <div class="card-body">

                                {{-- Class Dropdown --}}
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Class Name <span class="text-danger">*</span></label>
                                    <select class="form-select" name="class_id" id="class_id" required>
                                        <option value="" disabled {{ !isset($assignTeacher) ? 'selected' : '' }}>Select Class</option>
                                        @foreach ($getClass as $class)
                                            <option value="{{ $class->id }}"
                                                {{ old('class_id', $assignTeacher->class_id ?? '') == $class->id ? 'selected' : '' }}>
                                                {{ $class->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('class_id') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>

                                {{-- Teacher Checkboxes --}}
                                <div class="mb-3">
                                    <label class="form-label">Teacher Name <span class="text-danger">*</span></label>

                                    @php
                                        // For edit, pass $selectedTeachers = [ids] from controller.
                                        $preselected = old('teacher_id', $selectedTeachers ?? []);
                                    @endphp

                                    @foreach ($getTeachers as $teacher)
                                        <div class="form-check mb-1">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   id="teacher_{{ $teacher->id }}"
                                                   name="teacher_id[]"
                                                   value="{{ $teacher->id }}"
                                                   {{ in_array($teacher->id, $preselected) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="teacher_{{ $teacher->id }}">
                                                {{ $teacher->name }} {{ $teacher->last_name }}
                                            </label>
                                        </div>
                                    @endforeach

                                    @error('teacher_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
                                    @error('teacher_id.*') <small class="text-danger d-block">{{ $message }}</small> @enderror
                                </div>

                                {{-- Status Dropdown --}}
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status" id="status" required>
                                        <option value="" disabled {{ !isset($assignTeacher) ? 'selected' : '' }}>Select status</option>
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

                            {{-- Submit --}}
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    {{ isset($assignTeacher) ? 'Update' : 'Submit' }}
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@endsection
