@extends('admin.layout.layout')
@section('content')

@php
  use Illuminate\Support\Str;
@endphp

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">My Child Homework</h3></div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          {{-- CHILD PICKER --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title">Select Student</h3></div>
            <div class="card-body">
              <form method="GET" action="{{ route('parent.child.homework.list') }}">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-control" onchange="this.form.submit()">
                      <option value="">-- Choose a Student --</option>
                      @foreach ($children as $child)
                        @php
                          $full = trim(($child->name ?? $child->first_name ?? '').' '.($child->last_name ?? ''));
                        @endphp
                        <option value="{{ $child->id }}" {{ (string)$selectedStudentId === (string)$child->id ? 'selected' : '' }}>
                          {{ $full !== '' ? $full : ('Student #'.$child->id) }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Load</button>
                    <a href="{{ route('parent.child.homework.list') }}" class="btn btn-outline-secondary">Reset</a>
                  </div>
                </div>
              </form>
            </div>
          </div>

          {{-- LIST (only when a student is selected) --}}
          @if($selectedStudent)
            <div class="card mb-4">
              <div class="card-header">
                @php
                  $full = trim(($selectedStudent->name ?? $selectedStudent->first_name ?? '').' '.($selectedStudent->last_name ?? ''));
                @endphp
                <h3 class="card-title">Homework for {{ $full !== '' ? $full : 'Selected Student' }}</h3>
              </div>

              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-striped mb-0">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Subject</th>
                        <th>Homework</th>
                        <th>Homework Date</th>
                        <th>Submission Date</th>
                        <th>Document</th>
                        <th>Student Submission Status</th>
                        <th class="text-center">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($homeworks as $hw)
                        @php
                          $sub = $hw->submissions->first(); // this student's submission (if any)
                          $due = $hw->submission_date ? $hw->submission_date->endOfDay() : null;
                          $isClosed = $due ? now()->gt($due) : false;

                          // Status label
                          if ($sub) {
                            $status = ($due && $sub->submitted_at && $sub->submitted_at->gt($due))
                                      ? 'Submitted (Late)' : 'Submitted (On time)';
                          } else {
                            $status = $isClosed ? 'Not submitted (Closed)' : 'Not submitted (Open)';
                          }
                        @endphp
                        <tr>
                          <td>{{ ($homeworks->currentPage()-1)*$homeworks->perPage()+$loop->iteration }}</td>
                          <td>{{ $hw->subject->name ?? '—' }}</td>
                          <td title="{{ $hw->description_plain ?? Str::limit(strip_tags($hw->description), 120) }}">
                            {{ \Illuminate\Support\Str::limit($hw->description_plain ?? strip_tags($hw->description), 60) ?: '—' }}
                          </td>
                          <td>{{ optional($hw->homework_date)->format('d-m-Y') ?? '—' }}</td>
                          <td>{{ optional($hw->submission_date)->format('d-m-Y') ?? '—' }}</td>
                          <td>
                            @if($hw->document_file)
                              <a href="{{ route('parent.child.homework.download', $hw->id) }}" class="btn btn-sm btn-outline-primary">Download</a>
                            @else — @endif
                          </td>
                          <td>
                            <span class="badge
                              @if(Str::contains($status,'On time')) bg-primary
                              @elseif(Str::contains($status,'Late')) bg-danger
                              @elseif(Str::contains($status,'Open')) bg-success
                              @else bg-secondary @endif">
                              {{ $status }}
                            </span>
                          </td>
                          <td class="text-center">
                            @if($sub)
                                <a href="{{ route('parent.child.homework.submission.show', [$hw->id, $selectedStudentId]) }}"
                                class="btn btn-sm btn-outline-info">View Submission</a>
                            @else
                                {{-- no submission yet --}}
                            @endif
                          </td>
                        </tr>
                      @empty
                        <tr><td colspan="8" class="text-center py-4">No homework found.</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>

                @if ($homeworks instanceof \Illuminate\Pagination\LengthAwarePaginator && $homeworks->count())
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
          @endif

          {{-- No children case --}}
          @if(!$children->count())
            <div class="alert alert-warning">No students are linked to this guardian account.</div>
          @endif

        </div>
      </div>
    </div>
  </div>
</main>
@endsection
