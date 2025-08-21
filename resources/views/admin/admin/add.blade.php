@extends('admin.layout.layout')
@section('content')


<main class="app-main">
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
        <!--begin::Row-->
        <div class="row">
            <div class="col-sm-6"><h3 class="mb-0">Add Admin</h3></div>
        </div>
        <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content Header-->
    <!--begin::App Content-->
    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
        <!--begin::Row-->
            <div class="row g-4">

                {{-- @include('admin.message') --}}

                <div class="col-md-8">
                <!--begin::Quick Example-->
                    <div class="card card-primary card-outline mb-4">

                        <form action="{{ isset($user) ? route('admin.admin.update-admin', $user->id) : route('admin.admin.add-admin') }}" method="POST">
                            @csrf
                        <!--begin::Body-->
                        <div class="card-body">

                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Name" value="{{ old('name', $user->name ?? '') }}" required />
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Email" value="{{ old('email', $user->email ?? '') }}" required />
                                <span class="text-danger">{{ $errors->first('email')}}</span>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Password" {{ isset($user) ? '' : 'required' }} />
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" name="role" id="role" required>
                                    <option value="" disabled {{ !isset($user) ? 'selected' : '' }}>Select a role</option>
                                    @foreach (['admin', 'teacher', 'student', 'parent'] as $role)
                                        <option value="{{ $role }}" {{ (old('role', $user->role ?? '') == $role) ? 'selected' : '' }}>
                                            {{ ucfirst($role) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                        <!--end::Body-->
                        <!--begin::Footer-->
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">{{ isset($user) ? 'Update' : 'Submit' }}</button>
                        </div>
                        <!--end::Footer-->
                        </form>
                        <!--end::Form-->
                    </div>
                </div>
            </div>
        <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content-->
</main>



@endsection
