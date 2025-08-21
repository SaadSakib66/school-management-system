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
                <div class="col-md-6">
                    <div class="card card-primary card-outline mb-4">

                        <form action="{{ route('admin.assign-subject.update-single-subject', $assignSubject->id) }}" method="POST">
                            @csrf

                            <div class="card-body">

                                {{-- Show Class --}}
                                <div class="mb-3">
                                    <label class="form-label">Class</label>
                                    <input type="text" class="form-control" value="{{ $assignSubject->class->name }}" readonly>
                                </div>

                                {{-- Show Subject --}}
                                <div class="mb-3">
                                    <label class="form-label">Subject</label>
                                    <input type="text" class="form-control" value="{{ $assignSubject->subject->name }}" readonly>
                                </div>

                                {{-- Status Dropdown --}}
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status" required>
                                        <option value="1" {{ $assignSubject->status == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ $assignSubject->status == 0 ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>

                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('admin.assign-subject.list') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@endsection
