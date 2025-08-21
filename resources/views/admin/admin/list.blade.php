@extends('admin.layout.layout')
@section('content')


<main class="app-main">
<div class="app-content-header">
    <div class="container-fluid">
    <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Admin Table</h3></div>
        <div class="col-sm-6">
        <button class="btn btn-primary float-sm-end"><a href="{{ route('admin.admin.add') }}" style="text-decoration: none; color: white;">Add Admin</a></button>
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
                    <h3 class="card-title">Admin List</h3>
                    </div>

                    <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>

                            @foreach($getRecord as $value)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $value->name }}</td>
                                    <td>{{ $value->email }}</td>
                                    <td>{{ $value->role }}</td>
                                    <td>{{ date('d M Y', strtotime($value->created_at)) }}</td>
                                    <td>
                                        <a href="{{ route('admin.admin.edit-admin', $value->id) }}" class="btn btn-success btn-sm">Edit</a>

                                        <form action="{{ route('admin.admin.delete-admin') }}" method="POST" style="display:inline;">
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
