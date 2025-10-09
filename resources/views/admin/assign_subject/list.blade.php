@extends('admin.layout.layout')
@section('content')


<main class="app-main">
<div class="app-content-header">
    <div class="container-fluid">
    <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Assign Subject Table</h3></div>
        <div class="col-sm-6">
        <button class="btn btn-primary float-sm-end"><a href="{{ route('admin.assign-subject.add') }}" style="text-decoration: none; color: white;">Add Assign Subject</a></button>
        </div>
    </div>

    </div>
</div>

<div class="app-content">

    <div class="container-fluid">

        <div class="row">
            <div class="col-md-12">

                @include('admin.message')

                <div class="card mb-4">
                    <div class="card-header">
                    <h3 class="card-title">Assigned Subject List</h3>
                    </div>

                    <div class="p-3 pb-0">
                    <form method="GET" action="{{ route('admin.assign-subject.list') }}" class="mb-2" id="assignFilterForm">
                        {{-- always include this; it’s sent only when Search submits the form --}}
                        <input type="hidden" name="did_search" value="1">

                        <div class="row g-2 align-items-end">

                        {{-- Class --}}
                        <div class="col-md-3">
                            <label class="form-label mb-1">Class</label>
                            <select name="class_id" class="form-select">
                            <option value="">All</option>
                            @foreach($getClass as $class)
                                <option value="{{ $class->id }}" {{ request('class_id')==$class->id ? 'selected':'' }}>
                                {{ $class->name }}
                                </option>
                            @endforeach
                            </select>
                        </div>

                        {{-- Subject --}}
                        <div class="col-md-3">
                            <label class="form-label mb-1">Subject</label>
                            <select name="subject_id" class="form-select">
                            <option value="">All</option>
                            @foreach($getSubject as $subject)
                                <option value="{{ $subject->id }}" {{ request('subject_id')==$subject->id ? 'selected':'' }}>
                                {{ $subject->name }}
                                </option>
                            @endforeach
                            </select>
                        </div>

                        {{-- Status --}}
                        <div class="col-md-2">
                            <label class="form-label mb-1">Status</label>
                            <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="1" {{ request('status')==='1' ? 'selected':'' }}>Active</option>
                            <option value="0" {{ request('status')==='0' ? 'selected':'' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-4 d-flex gap-2">
                            <button class="btn btn-primary" type="submit">Search</button>
                            <a href="{{ route('admin.assign-subject.list') }}" class="btn btn-secondary">Reset</a>

                            {{-- Download: enabled only after a Search (did_search=1 present) --}}
                            @if(request('did_search')==='1')
                            <a href="{{ route('admin.assign-subject.download', request()->query()) }}"
                                target="_blank" rel="noopener"
                                class="btn btn-outline-danger">
                                <i class="bi bi-file-earmark-pdf-fill"></i> Download
                            </a>
                            @else
                            <button type="button" class="btn btn-outline-danger"
                                    onclick="alert('Please click Search after setting filters, then press Download.');">
                                <i class="bi bi-file-earmark-pdf-fill"></i> Download
                            </button>
                            @endif
                        </div>

                        </div>
                    </form>
                    </div>

                    <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Class Name</th>
                            <th>Subject Name</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>

                            @foreach($getRecord as $value)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $value->class_name }}</td>
                                <td>{{ $value->subject_name }}</td>
                                <td>{{ $value->status == 1 ? 'Active' : 'Inactive' }}</td>
                                <td>{{ $value->created_by_name }}</td>
                                <td>{{ date('d M Y', strtotime($value->created_at)) }}</td>
                                <td>
                                    <a href="{{ route('admin.assign-subject.edit-subject', $value->id) }}" class="btn btn-success btn-sm">Edit</a>
                                    <a href="{{ route('admin.assign-subject.edit-single-subject', $value->id) }}" class="btn btn-success btn-sm">Edit Single Subject</a>

                                    <form action="{{ route('admin.assign-subject.delete-subject') }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $value->id }}">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Are you sure you want to delete this Subject?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach

                        </tbody>
                    </table>
                    <div>
                        <p class="text-center mt-3">Showing {{ $getRecord->count() }} of {{ $getRecord->total() }} records</p>
                    </div>
                    {{ $getRecord->appends(request()->except('page'))->links('pagination::bootstrap-5') }}

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</main>

@endsection
