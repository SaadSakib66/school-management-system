@extends('admin.layout.layout')
@section('content')


<main class="app-main">
<div class="app-content-header">
    <div class="container-fluid">
    <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Assign Subject Table</h3></div>
        <div class="col-sm-6">
        <button class="btn btn-primary float-sm-end"><a href="{{ route('admin.assign-subject.add') }}" style="text-decoration: none; color: white;">Add Assign Subject</a></button>
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
                    <h3 class="card-title">Assigned Subject List</h3>
                    </div>

                    <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Class Name</th>
                            <th>Subject Name</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>

                            @foreach($getRecord as $value)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $value->class_name }}</td>
                                <td>{{ $value->subject_name }}</td>
                                <td>{{ $value->status == 1 ? 'Active' : 'Inactive' }}</td>
                                <td>{{ $value->created_by_name }}</td>
                                <td>{{ date('d M Y', strtotime($value->created_at)) }}</td>
                                <td>
                                    <a href="{{ route('admin.assign-subject.edit-subject', $value->id) }}" class="btn btn-success btn-sm">Edit</a>
                                    <a href="{{ route('admin.assign-subject.edit-single-subject', $value->id) }}" class="btn btn-success btn-sm">Edit Single Subject</a>

                                    <form action="{{ route('admin.assign-subject.delete-subject') }}" method="POST" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $value->id }}">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Are you sure you want to delete this Subject?')">Delete</button>
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
