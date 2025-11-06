@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Parent Table</h3></div>
        <div class="col-sm-6">
          <a href="{{ route('admin.parent.add') }}" class="btn btn-primary float-sm-end">Add Parent</a>
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
              <h3 class="card-title">Parents List</h3>
            </div>

            {{-- 🔎 Filters --}}
            <div class="p-3 pb-3">
              <form method="GET" action="{{ route('admin.parent.list') }}" class="mb-2">
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
                    <label class="form-label mb-1">Student Id</label>
                    <input type="number" name="student_id" value="{{ request('student_id') }}" class="form-control" placeholder="Student Id">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select">
                      <option value="">All</option>
                      <option value="1" {{ request('status')==='1'?'selected':'' }}>Active</option>
                      <option value="0" {{ request('status')==='0'?'selected':'' }}>Inactive</option>
                    </select>
                  </div>
                  <div class="col-md-12 mt-2 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Search</button>
                    <a href="{{ route('admin.parent.list') }}" class="btn btn-secondary">Reset</a>
                  </div>
                </div>
              </form>
            </div>

            <div class="card-body p-0">
              <style>
                /* Ensure cells align vertically and actions don't expand row height */
                table.parents-table td, table.parents-table th { vertical-align: middle !important; }
                .action-stack { display:inline-flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
                .action-stack form { display:inline-block; margin:0; padding:0; }
                .badge-id { font-weight:600; }
              </style>

              <table class="table table-striped parents-table">
                <thead>
                  <tr>
                    <th>Serial</th>
                    <th>Student IDs</th>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Occupation</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Created Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($getRecord as $value)
                    @php
                      $ids = ($assignedByParent[$value->id] ?? []);
                    @endphp
                    <tr>
                      <td class="align-middle">
                        {{ ($getRecord->currentPage()-1) * $getRecord->perPage() + $loop->iteration }}
                      </td>

                      <td class="align-middle">
                        @if(!empty($ids))
                          @foreach($ids as $sid)
                            <span class="badge bg-info text-dark badge-id">{{ $sid }}</span>
                          @endforeach
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>

                      <td class="align-middle">
                        @if($value->parent_photo)
                          <img src="{{ asset('storage/' . $value->parent_photo) }}" alt="Photo" width="50" height="50" style="border-radius:5px;">
                        @else
                          N/A
                        @endif
                      </td>

                      <td class="align-middle">{{ $value->name }} {{ $value->last_name }}</td>
                      <td class="align-middle">{{ $value->gender }}</td>
                      <td class="align-middle">{{ $value->email }}</td>
                      <td class="align-middle">{{ $value->mobile_number }}</td>
                      <td class="align-middle">{{ $value->occupation }}</td>
                      <td class="align-middle">{{ $value->address }}</td>
                      <td class="align-middle">{{ $value->status == 1 ? 'Active' : 'Inactive' }}</td>
                      <td class="align-middle">{{ \Carbon\Carbon::parse($value->created_at)->format('d M Y') }}</td>

                      <td class="align-middle">
                        <div class="action-stack">
                          <a href="{{ route('admin.parent.download', ['id' => $value->id, 'slug' => \Illuminate\Support\Str::slug($value->name)]) }}"
                             target="_blank" rel="noopener"
                             class="btn btn-outline-danger btn-sm" title="Download PDF">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                          </a>

                          <a href="{{ route('admin.parent.edit-parent', $value->id) }}" class="btn btn-success btn-sm">Edit</a>

                          <a href="{{ route('admin.parent.add-my-student', $value->id) }}" class="btn btn-info btn-sm">My Student</a>

                          <form action="{{ route('admin.parent.delete-parent') }}" method="POST">
                            @csrf
                            <input type="hidden" name="id" value="{{ $value->id }}">
                            <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete this parent?')">
                              Delete
                            </button>
                          </form>
                        </div>
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
