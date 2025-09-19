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
            <form action="{{ route('teacher.update-account') }}" method="POST" enctype="multipart/form-data">
              @csrf

              <div class="card-body">
                <div class="row">

                  <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control"
                           value="{{ old('name', $user->name ?? '') }}" required>
                    <span class="text-danger">{{ $errors->first('name') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="form-control"
                           value="{{ old('last_name', $user->last_name ?? '') }}" required>
                    <span class="text-danger">{{ $errors->first('last_name') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select class="form-select" name="gender" id="gender" required>
                      <option value="" disabled {{ old('gender', $user->gender ?? '') == '' ? 'selected' : '' }}>
                        Select Gender
                      </option>
                      @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $key => $label)
                        <option value="{{ $key }}" {{ old('gender', $user->gender ?? '') == $key ? 'selected' : '' }}>
                          {{ $label }}
                        </option>
                      @endforeach
                    </select>
                    <span class="text-danger">{{ $errors->first('gender') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="mobile_number" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" name="mobile_number" id="mobile_number" class="form-control"
                           value="{{ old('mobile_number', $user->mobile_number ?? '') }}" required>
                    <span class="text-danger">{{ $errors->first('mobile_number') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="email" class="form-control"
                           value="{{ old('email', $user->email ?? '') }}" required>
                    <span class="text-danger">{{ $errors->first('email') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="(leave blank to keep current)">
                    <span class="text-muted small">Leave empty if you don’t want to change it.</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea name="address" id="address" class="form-control" rows="3"
                              placeholder="Enter address">{{ old('address', $user->address ?? '') }}</textarea>
                    <span class="text-danger">{{ $errors->first('address') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="teacher_photo" class="form-label">Teacher Photo</label>
                    <input type="file" name="teacher_photo" id="teacher_photo" class="form-control">
                    @if(!empty($user->teacher_photo))
                      <div class="mt-2">
                        <img src="{{ asset('storage/'.$user->teacher_photo) }}" alt="Teacher Photo" width="100" height="100"
                             style="object-fit:cover; border:1px solid #ccc;">
                      </div>
                    @endif
                    <span class="text-danger">{{ $errors->first('teacher_photo') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control" value="Teacher" disabled>
                    <input type="hidden" name="role" value="teacher">
                  </div>

                  {{-- Only admins/super admins should ever see status; teachers cannot change it --}}
                  @if(!empty($canChangeStatus) && $canChangeStatus)
                    <div class="col-md-6 mb-3">
                      <label for="status" class="form-label">Status</label>
                      <select class="form-select" name="status" id="status">
                        @foreach ([1 => 'Active', 0 => 'Inactive'] as $key => $label)
                          <option value="{{ $key }}" {{ (string)old('status', (string)($user->status ?? '')) === (string)$key ? 'selected' : '' }}>
                            {{ $label }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                  @endif

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
