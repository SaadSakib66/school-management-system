@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Homework Table</h3></div>
        <div class="col-sm-6">
          <a class="btn btn-primary float-sm-end" href="{{ route('admin.homework.add') }}">Add Homework</a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          {{-- FILTERS --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title">Filter</h3></div>
            <div class="card-body">
              <form method="GET" action="{{ route('admin.homework.list') }}">
                <div class="row g-3">

                  <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="class_id" class="form-control">
                      <option value="">-- All Classes --</option>
                      @foreach ($getClass as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
                          {{ $c->name ?? $c->class_name ?? ('Class #'.$c->id) }}
                        </option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Subject</label>
                    <select name="subject_id" class="form-control">
                      <option value="">-- All Subjects --</option>
                      @foreach ($getSubject as $s)
                        <option value="{{ $s->id }}" {{ request('subject_id') == $s->id ? 'selected' : '' }}>
                          {{ $s->name ?? $s->subject_name ?? ('Subject #'.$s->id) }}
                        </option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Teacher</label>
                    <select name="teacher_id" class="form-control">
                        <option value="">-- All Teachers --</option>
                        @foreach ($getTeachers as $t)
                        <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                        @endforeach
                    </select>
                  </div>


                  <div class="col-md-3">
                    <label class="form-label">Homework From</label>
                    <input type="date" name="homework_from" class="form-control" value="{{ request('homework_from') }}">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Homework To</label>
                    <input type="date" name="homework_to" class="form-control" value="{{ request('homework_to') }}">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Submission From</label>
                    <input type="date" name="submission_from" class="form-control" value="{{ request('submission_from') }}">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Submission To</label>
                    <input type="date" name="submission_to" class="form-control" value="{{ request('submission_to') }}">
                  </div>

                  <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="{{ route('admin.homework.list') }}" class="btn btn-outline-secondary">Reset</a>
                  </div>
                </div>
              </form>
            </div>
          </div>

          {{-- LIST --}}
          <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Homework List</h3></div>

            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th>Serial</th>
                      <th>Class</th>
                      <th>Subject</th>
                      <th>Homework Date</th>
                      <th>Submission Date</th>
                      <th>Document</th>
                      <th>Description</th>
                      <th>Created By</th>
                      <th>Created Date</th>
                      <th class="text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($homeworks as $homework)
                      <tr>
                        <td>{{ ($homeworks->currentPage() - 1) * $homeworks->perPage() + $loop->iteration }}</td>
                        <td>{{ $homework->class->name ?? $homework->class->class_name ?? '—' }}</td>
                        <td>{{ $homework->subject->name ?? $homework->subject->subject_name ?? '—' }}</td>
                        <td>{{ optional($homework->homework_date)->format('d-m-Y') ?? '—' }}</td>
                        <td>{{ optional($homework->submission_date)->format('d-m-Y') ?? '—' }}</td>
                        <td>
                          @if($homework->document_file)
                            <a href="{{ route('admin.homework.download', $homework->id) }}" class="btn btn-sm btn-outline-primary">Download</a>
                          @else — @endif
                        </td>
                        <td title="{{ $homework->description_plain  }}">
                            {{ \Illuminate\Support\Str::limit($homework->description_plain , 60) ?: '—' }}
                        </td>
                        <td>{{ $homework->creator->name ?? '—' }}</td>
                        <td>{{ optional($homework->created_at)->format('d-m-Y H:i') ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.homework.submissions.index', $homework->id) }}" class="btn btn-sm btn-info">Submitted Homework</a>
                          <a href="{{ route('admin.homework.edit', $homework->id) }}" class="btn btn-sm btn-success">Edit</a>
                          <form action="{{ route('admin.homework.delete', $homework->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Move this homework to trash?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                          </form>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="10" class="text-center py-4">No records found.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>

              @if ($homeworks->count())
                <div class="px-3 py-2">
                  <p class="text-center mb-1">
                    Showing {{ $homeworks->firstItem() }} to {{ $homeworks->lastItem() }} of {{ $homeworks->total() }} records
                  </p>
                  <div class="d-flex justify-content-center">
                    {{ $homeworks->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                  </div>
                </div>
              @endif
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>
@endsection
