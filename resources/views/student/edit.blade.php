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
      <div class="row g-4">
        <div class="col-md-8">
          <div class="card card-primary card-outline mb-4">
            <form action="{{ route('student.update-account') }}" method="POST" enctype="multipart/form-data">
              @csrf

              <div class="card-body">
                <div class="row">

                  <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="{{ old('name', $user->name ?? '') }}" required>
                    <span class="text-danger">{{ $errors->first('name') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" id="last_name" name="last_name" class="form-control"
                           value="{{ old('last_name', $user->last_name ?? '') }}" required>
                    <span class="text-danger">{{ $errors->first('last_name') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" id="gender" name="gender" required>
                      <option value="" disabled {{ old('gender', $user->gender ?? '') == '' ? 'selected' : '' }}>Select Gender</option>
                      @foreach(['male'=>'Male','female'=>'Female','other'=>'Other'] as $key=>$label)
                        <option value="{{ $key }}" {{ old('gender', $user->gender ?? '') == $key ? 'selected' : '' }}>
                          {{ $label }}
                        </option>
                      @endforeach
                    </select>
                    <span class="text-danger">{{ $errors->first('gender') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                           value="{{ old('date_of_birth', optional($user->date_of_birth ? \Illuminate\Support\Carbon::parse($user->date_of_birth) : null)?->format('Y-m-d')) }}"
                           required>
                    <span class="text-danger">{{ $errors->first('date_of_birth') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="religion" class="form-label">Religion</label>
                    <input type="text" id="religion" name="religion" class="form-control"
                           value="{{ old('religion', $user->religion ?? '') }}">
                    <span class="text-danger">{{ $errors->first('religion') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="mobile_number" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" id="mobile_number" name="mobile_number" class="form-control"
                           value="{{ old('mobile_number', $user->mobile_number ?? '') }}" required>
                    <span class="text-danger">{{ $errors->first('mobile_number') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="{{ old('email', $user->email ?? '') }}" required>
                    <span class="text-danger">{{ $errors->first('email') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="(leave blank to keep current)">
                    <span class="text-muted small">Leave empty if you don’t want to change it.</span>
                    <span class="text-danger d-block">{{ $errors->first('password') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="student_photo" class="form-label">Student Photo</label>
                    <input type="file" id="student_photo" name="student_photo" class="form-control">
                    @if(!empty($user->student_photo))
                      <div class="mt-2">
                        <img src="{{ asset('storage/'.$user->student_photo) }}" alt="Student Photo"
                             width="100" height="100" style="object-fit:cover; border:1px solid #ccc;">
                      </div>
                    @endif
                    <span class="text-danger">{{ $errors->first('student_photo') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="blood_group" class="form-label">Blood Group</label>
                    <input type="text" id="blood_group" name="blood_group" class="form-control"
                           value="{{ old('blood_group', $user->blood_group ?? '') }}">
                    <span class="text-danger">{{ $errors->first('blood_group') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="height" class="form-label">Height</label>
                    <input type="text" id="height" name="height" class="form-control"
                           value="{{ old('height', $user->height ?? '') }}">
                    <span class="text-danger">{{ $errors->first('height') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="weight" class="form-label">Weight</label>
                    <input type="text" id="weight" name="weight" class="form-control"
                           value="{{ old('weight', $user->weight ?? '') }}">
                    <span class="text-danger">{{ $errors->first('weight') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"
                              placeholder="Enter address">{{ old('address', $user->address ?? '') }}</textarea>
                    <span class="text-danger">{{ $errors->first('address') }}</span>
                  </div>

                  {{-- Note: No "status" control here on purpose. Only admin/super_admin can change it. --}}

                </div>
              </div>

              <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
