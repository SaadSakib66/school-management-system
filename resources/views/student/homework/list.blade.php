@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">My Homework</h3></div>
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
              <form method="GET" action="{{ route('student.homework.list') }}">
                <div class="row g-3">

                  {{-- Subject: ONLY subjects assigned to the student's class (controller passes $subjects) --}}
                  <div class="col-md-3">
                    <label class="form-label">Subject</label>
                    <select name="subject_id" class="form-control">
                      <option value="">-- All Subjects --</option>
                      @foreach ($subjects as $s)
                        <option value="{{ $s->id }}" {{ request('subject_id') == $s->id ? 'selected' : '' }}>
                          {{ $s->name ?? $s->subject_name ?? ('Subject #'.$s->id) }}
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

                  <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="{{ route('student.homework.list') }}" class="btn btn-outline-secondary">Reset</a>
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
                      <th>#</th>
                      <th>Subject</th>
                      <th>Homework Date</th>
                      <th>Submission Date</th>
                      <th>Given By</th>
                      <th>Status</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($homeworks as $hw)
                      @php
                        $sub = $hw->submissions->first();
                        $due = $hw->submission_date ? $hw->submission_date->endOfDay() : null;
                        $isClosed = $due ? now()->gt($due) : false;

                        if ($sub) {
                          if ($hw->submission_date && $sub->submitted_at && $sub->submitted_at->gt($hw->submission_date->endOfDay())) {
                            $status = 'Submitted (Late)';
                          } else {
                            $status = 'Submitted (On time)';
                          }
                        } else {
                          $status = $isClosed ? 'Closed' : 'Open';
                        }

                        $creatorFirst = $hw->creator->name ?? $hw->creator->first_name ?? '';
                        $creatorLast  = $hw->creator->last_name ?? '';
                        $creatorFull  = trim($creatorFirst.' '.$creatorLast);
                      @endphp

                      <tr>
                        <td>{{ ($homeworks->currentPage()-1)*$homeworks->perPage()+$loop->iteration }}</td>
                        <td>{{ $hw->subject->name ?? '—' }}</td>
                        <td>{{ optional($hw->homework_date)->format('d-m-Y') ?? '—' }}</td>
                        <td>{{ optional($hw->submission_date)->format('d-m-Y') ?? '—' }}</td>
                        <td>{{ $creatorFull !== '' ? $creatorFull : '—' }}</td>
                        <td>
                          <span class="badge
                            @if(\Illuminate\Support\Str::contains($status,'Open')) bg-success
                            @elseif(\Illuminate\Support\Str::contains($status,'Closed')) bg-secondary
                            @elseif(\Illuminate\Support\Str::contains($status,'Late')) bg-danger
                            @else bg-primary @endif">
                            {{ $status }}
                          </span>
                        </td>
                        <td class="text-end">
                          @if($hw->document_file)
                            <a href="{{ route('student.homework.download',$hw->id) }}" class="btn btn-sm btn-outline-primary">Download</a>
                          @endif

                          @if(!$isClosed)
                            <a href="{{ route('student.homework.submit',$hw->id) }}"
                               class="btn btn-sm btn-warning">
                              {{ $sub ? 'Edit Submission' : 'Submit' }}
                            </a>
                          @elseif($sub)
                            <a href="{{ route('student.homework.submit',$hw->id) }}"
                               class="btn btn-sm btn-outline-secondary">View</a>
                          @endif
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="7" class="text-center py-4">No homework found.</td></tr>
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
