@extends('admin.layout.layout')
@section('content')


<main class="app-main">
<div class="app-content-header">
    <div class="container-fluid">
    <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Teacher Table</h3></div>
        <div class="col-sm-6">
        <button class="btn btn-primary float-sm-end"><a href="{{ route('admin.teacher.add') }}" style="text-decoration: none; color: white;">Add Teacher</a></button>
        </div>
    </div>

    </div>
</div>

<div class="app-content">

    <div class="container-fluid">

        <div class="row">
            <div class="col-md-12">

                @include('admin.message')

                <div class="card mb-4">
                    <div class="card-header">
                    <h3 class="card-title">Teachers List</h3>
                    </div>

                    <div class="p-3 pb-3">
                        <form method="GET" action="{{ route('admin.teacher.list') }}" class="mb-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                            <label class="form-label mb-1">Name</label>
                            <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="Name">
                            </div>
                            <div class="col-md-2">
                            <label class="form-label mb-1">Email</label>
                            <input type="text" name="email" value="{{ request('email') }}" class="form-control" placeholder="Email">
                            </div>
                            <div class="col-md-2">
                            <label class="form-label mb-1">Mobile</label>
                            <input type="text" name="mobile" value="{{ request('mobile') }}" class="form-control" placeholder="Mobile">
                            </div>
                            <div class="col-md-2">
                            <label class="form-label mb-1">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">All</option>
                                <option value="male"   {{ request('gender')=='male'?'selected':'' }}>Male</option>
                                <option value="female" {{ request('gender')=='female'?'selected':'' }}>Female</option>
                                <option value="other"  {{ request('gender')=='other'?'selected':'' }}>Other</option>
                            </select>
                            </div>
                            <div class="col-md-2">
                            <label class="form-label mb-1">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="1" {{ request('status')==='1'?'selected':'' }}>Active</option>
                                <option value="0" {{ request('status')==='0'?'selected':'' }}>Inactive</option>
                            </select>
                            </div>
                            <div class="col-md-1 d-flex gap-2">
                            <button class="btn btn-primary" type="submit">Search</button>
                            <a href="{{ route('admin.teacher.list') }}" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                        </form>
                    </div>

                    <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>

                            @foreach($getRecord as $value)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if($value->teacher_photo)
                                            <img src="{{ asset('storage/' . $value->teacher_photo) }}" alt="Photo" width="50" height="50" style="border-radius: 5px;">
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $value->name }} {{ $value->last_name }}</td>
                                    <td>{{ $value->gender }}</td>
                                    <td>{{ $value->email }}</td>
                                    <td>{{ $value->mobile_number }}</td>
                                    <td>{{ $value->address }}</td>
                                    <td>{{ $value->status == 1 ? 'Active' : 'Inactive' }}</td>
                                    <td>{{ date('d M Y', strtotime($value->created_at)) }}</td>
                                    <td>
                                        <a href="{{ route('admin.teacher.download', ['id' => $value->id, 'slug' => \Illuminate\Support\Str::slug($value->name)]) }}"
                                                    target="_blank" rel="noopener"
                                                    class="btn btn-outline-danger btn-sm" title="Download PDF">
                                                        <i class="bi bi-file-earmark-pdf-fill"></i>
                                        </a>
                                        <a href="{{ route('admin.teacher.edit-teacher', $value->id) }}" class="btn btn-success btn-sm">Edit</a>

                                        <form action="{{ route('admin.teacher.delete-teacher') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $value->id }}">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Are you sure you want to delete this admin?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                    <div>
                        <p class="text-center mt-3">Showing {{ $getRecord->count() }} of {{ $getRecord->total() }} records</p>
                    </div>
                    {{ $getRecord->appends(request()->except('page'))->links('pagination::bootstrap-5') }}

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</main>

@endsection
