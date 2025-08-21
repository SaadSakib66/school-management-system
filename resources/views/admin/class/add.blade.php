@extends('admin.layout.layout')
@section('content')


<main class="app-main">
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
        <!--begin::Row-->
        <div class="row">
            <div class="col-sm-6"><h3 class="mb-0">Add Class</h3></div>
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

                        <form action="{{ isset($class) ? route('admin.class.update-class', $class->id) : route('admin.class.add-class') }}" method="POST">
                            @csrf
                        <!--begin::Body-->
                        <div class="card-body">

                            <div class="mb-3">
                                <label for="name" class="form-label">Class Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Class Name" value="{{ old('name', $class->name ?? '') }}" required />
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
                            <button type="submit" class="btn btn-primary">{{ isset($class) ? 'Update' : 'Submit' }}</button>
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

