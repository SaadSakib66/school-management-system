@extends('admin.layout.layout')
@section('content')

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">My Class & Subject</h3></div>
                <div class="col-sm-6">
                    {{-- <a href="{{ route('admin.assign-class-teacher.add') }}" class="btn btn-primary float-sm-end">
                        Add Assign Teacher
                    </a> --}}
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
                            <h3 class="card-title">My Class & Subject</h3>
                        </div>

                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Serial</th>
                                        <th>Class Name</th>
                                        <th>Subject Name</th>
                                        <th>Created Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($getRecord as $value)
                                        <tr>

                                            <td>{{ ($getRecord->currentPage() - 1) * $getRecord->perPage() + $loop->iteration }}</td>
                                            <td>{{ $value->class_name }}</td>
                                            <td>
                                                @if($value->subject_name && $value->subject_name !== '-')
                                                    <ul class="mb-0">
                                                        @foreach(explode(',', $value->subject_name) as $sub)
                                                            <li>{{ trim($sub) }}</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ date('d M Y', strtotime($value->created_at)) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center p-4">No records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="p-3">
                            <p class="text-center mb-2">
                                Showing {{ $getRecord->count() }} of {{ $getRecord->total() }} records
                            </p>
                            {{ $getRecord->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
</main>

@endsection
