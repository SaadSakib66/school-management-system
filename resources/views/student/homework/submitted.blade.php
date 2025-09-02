@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Submitted Homework</h3></div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Your Submissions</h3></div>

            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Subject</th>
                      <th>Homework Date</th>
                      <th>Due Date</th>
                      <th>Submitted At</th>
                      <th>Status</th>
                      <th class="text-end">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($submissions as $s)
                      @php
                        $hw  = $s->homework;
                        $due = $hw?->submission_date ? $hw->submission_date->endOfDay() : null;
                        $status = ($due && $s->submitted_at && $s->submitted_at->gt($due)) ? 'Late' : 'On time';
                        $isClosed = $due ? now()->gt($due) : false;
                      @endphp
                      <tr>
                        <td>{{ ($submissions->currentPage()-1)*$submissions->perPage()+$loop->iteration }}</td>
                        <td>{{ $hw?->subject?->name ?? '—' }}</td>
                        <td>{{ optional($hw?->homework_date)->format('d-m-Y') ?? '—' }}</td>
                        <td>{{ optional($hw?->submission_date)->format('d-m-Y') ?? '—' }}</td>
                        <td>{{ optional($s->submitted_at)->format('d-m-Y H:i') ?? '—' }}</td>
                        <td>
                          <span class="badge {{ $status === 'Late' ? 'bg-danger' : 'bg-success' }}">
                            {{ $status }}
                          </span>
                          @if($isClosed)
                            <span class="badge bg-secondary">Closed</span>
                          @endif
                        </td>
                        <td class="text-end">
                          @if($s->attachment)
                            <a href="{{ route('student.homework.submission.download', $s->id) }}"
                               class="btn btn-sm btn-outline-primary">Download File</a>
                          @endif
                          @if(!$isClosed && $hw)
                            <a href="{{ route('student.homework.submit', $hw->id) }}"
                               class="btn btn-sm btn-warning">Edit</a>
                          @elseif($hw)
                            <a href="{{ route('student.homework.submit', $hw->id) }}"
                               class="btn btn-sm btn-outline-secondary">View</a>
                          @endif
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="7" class="text-center py-4">No submissions yet.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>

              @if ($submissions->count())
                <div class="px-3 py-2">
                  <p class="text-center mb-1">
                    Showing {{ $submissions->firstItem() }} to {{ $submissions->lastItem() }} of {{ $submissions->total() }} records
                  </p>
                  <div class="d-flex justify-content-center">
                    {{ $submissions->links('pagination::bootstrap-5') }}
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
