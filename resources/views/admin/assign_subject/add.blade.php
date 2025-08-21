@extends('admin.layout.layout')
@section('content')

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ $header_title }}</h3></div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card card-primary card-outline mb-4">

                        <form action="{{ isset($assignSubject)
                                ? route('admin.assign-subject.update-subject', $assignSubject->id)
                                : route('admin.assign-subject.add-subject') }}" method="POST">
                            @csrf

                            <div class="card-body">

                                {{-- Class Dropdown --}}
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Class Name</label>
                                    <select class="form-select" name="class_id" id="class_id" required>
                                        <option value="" disabled {{ !isset($assignSubject) ? 'selected' : '' }}>Select Class</option>
                                        @foreach ($getClass as $class)
                                            <option value="{{ $class->id }}"
                                                {{ old('class_id', $assignSubject->class_id ?? '') == $class->id ? 'selected' : '' }}>
                                                {{ $class->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Subject Checkboxes --}}
                                <div class="mb-3">
                                    <label for="subject_id" class="form-label">Subject Name</label>
                                    @foreach ($getSubject as $subject)
                                        <div>
                                            <label>
                                                <input type="checkbox" name="subject_id[]" value="{{ $subject->id }}"
                                                    {{ in_array($subject->id, old('subject_id', $selectedSubjects ?? [])) ? 'checked' : '' }}>
                                                {{ $subject->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Status Dropdown --}}
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status" required>
                                        <option value="" disabled {{ !isset($assignSubject) ? 'selected' : '' }}>Select status</option>
                                        @foreach ([1 => 'Active', 0 => 'Inactive'] as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ (old('status', $assignSubject->status ?? '') == $key) ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>

                            {{-- Submit Button --}}
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    {{ isset($assignSubject) ? 'Update' : 'Submit' }}
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
