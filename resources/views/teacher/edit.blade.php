@extends('admin.layout.layout')
@section('content')


<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h3 class="mb-0">Update My Profile</h3></div>
        </div>

        </div>

    </div>


    <div class="app-content">

        <div class="container-fluid">
        <!--begin::Row-->
            <div class="row g-4">

                <div class="col-md-8">
                    <div class="card card-primary card-outline mb-4">

                        <form action="{{ route('teacher.update-account') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                        <div class="card-body">
                            <div class="row">

                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">First Name <span style="color: red;">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="Name" value="{{ old('name', $user->name ?? '') }}" required />
                                    <span class="text-danger">{{ $errors->first('name')}}</span>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name <span style="color: red;">*</span></label>
                                    <input type="text" name="last_name" class="form-control" placeholder="LastName" value="{{ old('last_name', $user->last_name ?? '') }}" required />
                                    <span class="text-danger">{{ $errors->first('last_name')}}</span>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Gender <span style="color: red;">*</span></label>
                                    <select class="form-select" name="gender" id="gender" required>
                                        <option value="" disabled {{ old('gender', $user->gender ?? '') == '' ? 'selected' : '' }}>Select Gender</option>
                                        @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $key => $label)
                                            <option value="{{ $key }}" {{ old('gender', $user->gender ?? '') == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="mobile_number" class="form-label">Mobile Number<span style="color: red;">*</span></label>
                                    <input type="text" name="mobile_number" class="form-control" placeholder="Name" value="{{ old('mobile_number', $user->mobile_number ?? '') }}" required />
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email<span style="color: red;">*</span></label>
                                    <input type="email" name="email" class="form-control" placeholder="Email" value="{{ old('email', $user->email ?? '') }}" required />
                                    <span class="text-danger">{{ $errors->first('email')}}</span>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="Password" {{ isset($user) ? '' : 'required' }} />
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea
                                        name="address"
                                        id="address"
                                        class="form-control"
                                        placeholder="Enter address"
                                        {{ isset($user) ? '' : 'required' }}
                                    >{{ old('address', $user->address ?? '') }}</textarea>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="teacher_photo" class="form-label">Teacher Photo</label>
                                    <input type="file" name="teacher_photo" class="form-control" />

                                    @if(!empty($user->teacher_photo))
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/'.$user->teacher_photo) }}"
                                                alt="Teacher Photo" width="100" height="100"
                                                style="object-fit:cover; border:1px solid #ccc;">
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" class="form-control" value="Teacher" disabled>
                                    <input type="hidden" name="role" value="teacher">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status" required>
                                        <option value="" disabled {{ !isset($user) ? 'selected' : '' }}>Select status</option>
                                        @foreach ([1 => 'Active', 0 => 'Inactive'] as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ (old('status', isset($user) ? (string)$user->status : '') === (string)$key) ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>


                            </div>

                        </div>


                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">{{ isset($user) ? 'Update' : 'Submit' }}</button>
                        </div>

                        </form>

                    </div>
                </div>
            </div>

        </div>

    </div>

</main>


@endsection
