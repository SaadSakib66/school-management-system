@extends('admin.layout.layout')
@section('content')

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">{{ $header_title }}</h3></div>
                <div class="col-sm-6 text-end">
                    <a href="{{ route('teacher.edit-account', $user->id) }}" class="btn btn-primary btn-sm">
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
                <div class="col-md-8">
                    <div class="card card-outline card-primary">
                        <div class="card-body">
                            <div class="row">

                                <div class="col-md-4 text-center mb-3">
                                    <img src="{{ !empty($user->teacher_photo)
                                        ? asset('storage/'.$user->teacher_photo)
                                        : asset('images/default-profile.png') }}"
                                        alt="Teacher Photo"
                                        class="img-thumbnail"
                                        width="150" height="150"
                                        style="object-fit: cover;">
                                </div>

                                <div class="col-md-8">
                                    <table class="table table-bordered">
                                        <tr><th>First Name</th><td>{{ $user->name }}</td></tr>
                                        <tr><th>Last Name</th><td>{{ $user->last_name }}</td></tr>
                                        <tr><th>Gender</th><td>{{ ucfirst($user->gender) }}</td></tr>
                                        <tr><th>Email</th><td>{{ $user->email }}</td></tr>
                                        <tr><th>Mobile</th><td>{{ $user->mobile_number }}</td></tr>
                                        <tr><th>Address</th><td>{{ $user->address }}</td></tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                @if($user->status == 1)
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
                            <a href="{{ route('teacher.edit-account', $user->id) }}" class="btn btn-warning">
                                Edit Profile / Change Password
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

@endsection




