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

            {{-- Search Form --}}
            <div class="row mb-3">
                <div class="col-md-12">
                    <form method="GET" action="{{ route('admin.parent.add-my-student', $parent_id) }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <input type="text" name="name" class="form-control"
                                       placeholder="Search by Name"
                                       value="{{ request()->get('name') }}">
                            </div>
                            <div class="col-md-3">
                                <input type="email" name="email" class="form-control"
                                       placeholder="Search by Email"
                                       value="{{ request()->get('email') }}">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="mobile" class="form-control"
                                       placeholder="Search by Mobile"
                                       value="{{ request()->get('mobile') }}">
                            </div>
                            <div class="col-md-3 d-flex">
                                <button type="submit" class="btn btn-success me-2">Search</button>
                                <a href="{{ route('admin.parent.add-my-student', $parent_id) }}" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Students Table --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Search Results</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Serial</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Class</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($getRecord as $student)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if($student->student_photo)
                                            <img src="{{ asset('storage/' . $student->student_photo) }}"
                                                 width="50" height="50" style="border-radius:5px;">
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $student->name }} {{ $student->last_name }}</td>
                                    <td>{{ $student->email }}</td>
                                    <td>{{ $student->mobile_number }}</td>
                                    <td>{{ $student->class_name ?? 'N/A' }}</td>
                                    <td>{{ $student->status == 1 ? 'Active' : 'Inactive' }}</td>
                                    <td>
                                        @if($student->parent_id == $parent_id)
                                            <button class="btn btn-success btn-sm" disabled>Assigned</button>
                                        @else
                                            <form action="{{ route('admin.parent.assign-student') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                <input type="hidden" name="parent_id" value="{{ $parent_id }}">
                                                <button type="submit" class="btn btn-primary btn-sm">Assign</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No students found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="p-3">
                        {{ $getRecord->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>

            {{-- Already Assigned Students --}}
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Assigned Students</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Serial</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignedStudents as $student)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if($student->student_photo)
                                            <img src="{{ asset('storage/' . $student->student_photo) }}"
                                                 width="50" height="50" style="border-radius:5px;">
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $student->name }} {{ $student->last_name }}</td>
                                    <td>{{ $student->email }}</td>
                                    <td>{{ $student->class->name ?? 'N/A' }}</td>
                                    <td>
                                        <form action="{{ route('admin.parent.remove-student') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <input type="hidden" name="parent_id" value="{{ $parent_id }}">
                                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No students assigned yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</main>

@endsection
