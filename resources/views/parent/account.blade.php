@extends('admin.layout.layout')
@section('content')

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ $header_title }}</h3></div>
                <div class="col-sm-6 text-end">
                    <a href="{{ route('parent.edit-account', $user->id) }}" class="btn btn-primary btn-sm">
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('admin.message')

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-4">

                {{-- Profile Card --}}
                <div class="col-md-8">
                    <div class="card card-outline card-primary">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center mb-3">
                                    <img src="{{ !empty($user->parent_photo)
                                        ? asset('storage/'.$user->parent_photo)
                                        : asset('images/default-profile.png') }}"
                                        alt="Parent Photo"
                                        class="img-thumbnail"
                                        width="150" height="150"
                                        style="object-fit: cover;">
                                </div>

                                <div class="col-md-8">
                                    <table class="table table-bordered">
                                        <tr><th>First Name</th><td>{{ $user->name }}</td></tr>
                                        <tr><th>Last Name</th><td>{{ $user->last_name }}</td></tr>
                                        <tr><th>Gender</th><td>{{ $user->gender ? ucfirst($user->gender) : '' }}</td></tr>
                                        <tr><th>Email</th><td>{{ $user->email }}</td></tr>
                                        <tr><th>Mobile</th><td>{{ $user->mobile_number }}</td></tr>
                                        <tr><th>Occupation</th><td>{{ $user->occupation }}</td></tr>
                                        <tr><th>Address</th><td>{{ $user->address }}</td></tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                @if((int) $user->status === 1)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr><th>Role</th><td>{{ ucfirst($user->role) }}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <a href="{{ route('parent.edit-account', $user->id) }}" class="btn btn-warning">
                                Edit Profile / Change Password
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Assigned Students Card --}}
                <div class="col-md-12">
                    <div class="card card-outline card-secondary">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">
                                Assigned Students
                                @if(isset($assignedStudents) && method_exists($assignedStudents, 'total'))
                                    <small class="text-muted">({{ $assignedStudents->total() }} total)</small>
                                @elseif(!empty($assignedStudents))
                                    <small class="text-muted">({{ count($assignedStudents) }})</small>
                                @endif
                            </h3>
                        </div>

                        <div class="card-body p-0">
                            @if(!empty($assignedStudents) && $assignedStudents->count())
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 70px;">Photo</th>
                                                <th>Name</th>
                                                <th>Class</th>
                                                <th>Subjects</th>
                                                <th>Roll / Admission</th>
                                                <th>Mobile</th>
                                                <th>Email</th>
                                                <th style="width: 100px;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($assignedStudents as $s)
                                                <tr>
                                                    <td class="text-center">
                                                        <img src="{{ !empty($s->student_photo)
                                                            ? asset('storage/'.$s->student_photo)
                                                            : asset('images/default-profile.png') }}"
                                                            alt="Student" width="50" height="50"
                                                            style="object-fit:cover; border:1px solid #ccc; border-radius:6px;">
                                                    </td>

                                                    <td>{{ $s->name }} {{ $s->last_name }}</td>

                                                    <td>{{ $s->class->name ?? '-' }}</td>

                                                    {{-- Subjects under the student's class --}}
                                                    <td>
                                                        @if($s->class && $s->class->subjects && $s->class->subjects->count())
                                                            <ul class="mb-0 ps-3">
                                                                @foreach($s->class->subjects as $subject)
                                                                    <li>{{ $subject->name }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <span class="text-muted">No subjects assigned to this class.</span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        {{ $s->roll_number ?? '-' }}
                                                        @if(!empty($s->admission_number))
                                                            / {{ $s->admission_number }}
                                                        @endif
                                                    </td>

                                                    <td>{{ $s->mobile_number ?? '-' }}</td>
                                                    <td>{{ $s->email ?? '-' }}</td>

                                                    <td>
                                                        @if((int) $s->status === 1)
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-danger">Inactive</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Pagination --}}
                                @if(method_exists($assignedStudents, 'links'))
                                    <div class="p-3">
                                        {{ $assignedStudents->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="p-3">No students assigned yet.</div>
                            @endif
                        </div>
                    </div>
                </div>

            </div> {{-- /.row --}}
        </div> {{-- /.container-fluid --}}
    </div> {{-- /.app-content --}}
</main>

@endsection

