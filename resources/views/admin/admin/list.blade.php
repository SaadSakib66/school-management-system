@extends('admin.layout.layout')
@section('content')

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><h3 class="mb-0">Admin Table</h3></div>
                <div class="col-sm-6">
                    <button class="btn btn-primary float-sm-end">
                        <a href="{{ route('admin.admin.add') }}" style="text-decoration: none; color: white;">Add Admin</a>
                    </button>
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

                            {{-- 🔍 Filters --}}
                            <div class="p-3 pb-0">
                                <form method="GET" action="{{ route('admin.admin.list') }}">
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <input type="text" name="name" class="form-control"
                                                   value="{{ request('name') }}" placeholder="Search by name">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="email" class="form-control"
                                                   value="{{ request('email') }}" placeholder="Search by email">
                                        </div>
                                        <div class="col-md-2">
                                            <select name="role" class="form-control">
                                                <option value="">All Roles</option>
                                                <option value="admin" {{ request('role')=='admin' ? 'selected' : '' }}>Admin</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select name="status" class="form-control">
                                                <option value="">All Status</option>
                                                <option value="1" {{ request('status')==='1' ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ request('status')==='0' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-primary" type="submit">Search</button>
                                            <a href="{{ route('admin.admin.list') }}" class="btn btn-secondary">Reset</a>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
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
                                    @forelse($getRecord as $value)
                                        <tr>
                                            {{-- keep serial continuous across pages --}}
                                            <td>{{ ($getRecord->currentPage() - 1) * $getRecord->perPage() + $loop->iteration }}</td>
                                            <td>{{ $value->name }}</td>
                                            <td>{{ $value->email }}</td>
                                            <td>{{ ucfirst($value->role) }}</td>
                                            <td>{{ date('d M Y', strtotime($value->created_at)) }}</td>
                                            <td class="d-flex align-items-center gap-1" style="gap:.25rem;">
                                                {{-- PDF Download --}}
                                                <a href="{{ route('admin.admin.download', ['id' => $value->id, 'slug' => \Illuminate\Support\Str::slug($value->name)]) }}"
                                                    target="_blank" rel="noopener"
                                                    class="btn btn-outline-danger btn-sm" title="Download PDF">
                                                        <i class="bi bi-file-earmark-pdf-fill"></i>
                                                </a>

                                                {{-- Edit & Delete --}}

                                                <a href="{{ route('admin.admin.edit-admin', $value->id) }}" class="btn btn-success btn-sm">Edit</a>

                                                <form action="{{ route('admin.admin.delete-admin') }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $value->id }}">
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Are you sure you want to delete this admin?')">Delete</button>
                                                </form>

                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center p-4">No admins found.</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div>
                                <p class="text-center mt-3 mb-2">
                                    Showing {{ $getRecord->count() }} of {{ $getRecord->total() }} records
                                </p>
                            </div>

                            <div class="px-3 pb-3">
                                {{ $getRecord->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>

@endsection
