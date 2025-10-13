@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">{{ isset($user) ? 'Edit Student' : 'Add Student' }}</h3></div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row g-4">

        <div class="col-md-8">
          <div class="card card-primary card-outline mb-4">
            <form action="{{ isset($user) ? route('admin.student.update-student', $user->id) : route('admin.student.add-student') }}" method="POST" enctype="multipart/form-data">
              @csrf

              <div class="card-body">
                <div class="row">

                  <div class="col-md-6 mb-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required />
                    <span class="text-danger">{{ $errors->first('name') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name ?? '') }}" required />
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Admission Number <span class="text-danger">*</span></label>
                    <input type="text" name="admission_number" class="form-control" value="{{ old('admission_number', $user->admission_number ?? '') }}" required />
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Roll Number <span class="text-danger">*</span></label>
                    <input type="text" name="roll_number" class="form-control" value="{{ old('roll_number', $user->roll_number ?? '') }}" required />
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Class Name <span class="text-danger">*</span></label>
                    <select class="form-select" name="class_id" id="class_id" required>
                      <option value="" disabled {{ old('class_id', $user->class_id ?? '')=='' ? 'selected' : '' }}>Select Class</option>
                      @foreach ($getClass as $class)
                        <option value="{{ $class->id }}" {{ old('class_id', $user->class_id ?? '') == $class->id ? 'selected' : '' }}>
                          {{ $class->name }}
                        </option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
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
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $user->date_of_birth ?? '') }}" required />
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Religion</label>
                    <input type="text" name="religion" class="form-control" value="{{ old('religion', $user->religion ?? '') }}" />
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" name="mobile_number" class="form-control" value="{{ old('mobile_number', $user->mobile_number ?? '') }}" required />
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Admission Date <span class="text-danger">*</span></label>
                    <input type="date" name="admission_date" class="form-control" value="{{ old('admission_date', $user->admission_date ?? '') }}" required />
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required />
                    <span class="text-danger">{{ $errors->first('email') }}</span>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" {{ isset($user) ? '' : 'required' }} />
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Student Photo</label>
                    <input type="file" name="student_photo" class="form-control" />
                    @if(!empty($user->student_photo))
                      <div class="mt-2">
                        <img src="{{ asset('storage/'.$user->student_photo) }}" alt="Student Photo" width="100" height="100" style="object-fit:cover; border:1px solid #ccc;">
                      </div>
                    @endif
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Blood Group</label>
                    <input type="text" name="blood_group" class="form-control" value="{{ old('blood_group', $user->blood_group ?? '') }}" />
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Height</label>
                    <input type="text" name="height" class="form-control" value="{{ old('height', $user->height ?? '') }}" />
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Weight</label>
                    <input type="text" name="weight" class="form-control" value="{{ old('weight', $user->weight ?? '') }}" />
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control" value="Student" disabled>
                    <input type="hidden" name="role" value="student">
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status" id="status" required>
                      <option value="" disabled {{ !isset($user) ? 'selected' : '' }}>Select status</option>
                      @foreach ([1 => 'Active', 0 => 'Inactive'] as $key => $label)
                        <option value="{{ $key }}" {{ (old('status', isset($user) ? (string)$user->status : '') === (string)$key) ? 'selected' : '' }}>
                          {{ $label }}
                        </option>
                      @endforeach
                    </select>
                  </div>

                </div>
              </div>

              {{-- NEW: Parents / Guardians --}}
              <div class="card-body border-top">
                <h5 class="mb-3">Parents / Guardians (optional)</h5>
                <div class="row g-3">

                  {{-- Mother --}}
                  <div class="col-12">
                    <h6 class="mb-2">Mother</h6>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="mother[name]" class="form-control" value="{{ old('mother.name') }}" placeholder="Mother's name">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="mother[email]" class="form-control" value="{{ old('mother.email') }}" placeholder="mother@example.com">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Mobile</label>
                    <input type="text" name="mother[mobile]" class="form-control" value="{{ old('mother.mobile') }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Password (optional)</label>
                    <input type="password" name="mother[password]" class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Occupation</label>
                    <input type="text" name="mother[occupation]" class="form-control" value="{{ old('mother.occupation') }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Address</label>
                    <input type="text" name="mother[address]" class="form-control" value="{{ old('mother.address') }}">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="mother[gender]" class="form-select">
                      <option value="">—</option>
                      <option value="female" {{ old('mother.gender')==='female'?'selected':'' }}>Female</option>
                      <option value="male"   {{ old('mother.gender')==='male'?'selected':'' }}>Male</option>
                      <option value="other"  {{ old('mother.gender')==='other'?'selected':'' }}>Other</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Relationship</label>
                    <input type="text" name="mother[relationship]" class="form-control" value="{{ old('mother.relationship','mother') }}">
                  </div>
                  <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="mother[is_primary]" value="1" id="mother_primary" {{ old('mother.is_primary') ? 'checked' : '' }}>
                      <label for="mother_primary" class="form-check-label">Primary</label>
                    </div>
                  </div>

                  <hr class="mt-2">

                  {{-- Father --}}
                  <div class="col-12">
                    <h6 class="mb-2">Father</h6>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="father[name]" class="form-control" value="{{ old('father.name') }}" placeholder="Father's name">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="father[email]" class="form-control" value="{{ old('father.email') }}" placeholder="father@example.com">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Mobile</label>
                    <input type="text" name="father[mobile]" class="form-control" value="{{ old('father.mobile') }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Password (optional)</label>
                    <input type="password" name="father[password]" class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Occupation</label>
                    <input type="text" name="father[occupation]" class="form-control" value="{{ old('father.occupation') }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Address</label>
                    <input type="text" name="father[address]" class="form-control" value="{{ old('father.address') }}">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="father[gender]" class="form-select">
                      <option value="">—</option>
                      <option value="male"   {{ old('father.gender')==='male'?'selected':'' }}>Male</option>
                      <option value="female" {{ old('father.gender')==='female'?'selected':'' }}>Female</option>
                      <option value="other"  {{ old('father.gender')==='other'?'selected':'' }}>Other</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Relationship</label>
                    <input type="text" name="father[relationship]" class="form-control" value="{{ old('father.relationship','father') }}">
                  </div>
                  <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="father[is_primary]" value="1" id="father_primary" {{ old('father.is_primary') ? 'checked' : '' }}>
                      <label for="father_primary" class="form-check-label">Primary</label>
                    </div>
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
