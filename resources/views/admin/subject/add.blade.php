@extends('admin.layout.layout')
@section('content')


<main class="app-main">
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
        <!--begin::Row-->
        <div class="row">
            <div class="col-sm-6"><h3 class="mb-0">Add Subject</h3></div>
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

                        <form action="{{ isset($subject) ? route('admin.subject.update-subject', $subject->id) : route('admin.subject.add-subject') }}" method="POST">
                            @csrf
                        <!--begin::Body-->
                        <div class="card-body">

                            <div class="mb-3">
                                <label for="name" class="form-label">Subject Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Subject Name" value="{{ old('name', $subject->name ?? '') }}" required />
                            </div>

                            <div class="mb-3">
                                <label for="type" class="form-label">Subject Type</label>
                                <select class="form-select" name="type" id="type" required>
                                    <option value="" disabled {{ !isset($subject) ? 'selected' : '' }}>Select Type</option>
                                    @foreach (['theory', 'practical'] as $type)
                                        <option value="{{ $type }}" {{ (old('type', $subject->type ?? '') == $type) ? 'selected' : '' }}>
                                            {{ ucfirst($type) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" name="status" id="status" required>
                                    <option value="" disabled {{ !isset($data) ? 'selected' : '' }}>Select status</option>
                                    @foreach ([1 => 'Active', 0 => 'Inactive'] as $key => $label)
                                        <option value="{{ $key }}"
                                            {{ (old('status', isset($data) ? (string)$data->status : '') === (string)$key) ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>


                        </div>
                        <!--end::Body-->
                        <!--begin::Footer-->
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">{{ isset($subject) ? 'Update' : 'Submit' }}</button>
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

