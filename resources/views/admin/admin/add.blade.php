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

                <div class="col-md-6">
                <!--begin::Quick Example-->
                    <div class="card card-primary card-outline mb-4">
                        <!--begin::Header-->
                        {{-- <div class="card-header"><div class="card-title">Quick Example</div></div> --}}
                        <!--end::Header-->
                        <!--begin::Form-->
                        <form>
                        <!--begin::Body-->
                        <div class="card-body">
                            <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                placeholder="Name"
                                required
                            />
                            </div>
                            <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                placeholder="Email"
                                required
                            />
                            </div>
                            <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Password" required />
                            </div>

                        </div>
                        <!--end::Body-->
                        <!--begin::Footer-->
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Submit</button>
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
