@extends('admin.layout.layout') {{-- or student/teacher/parent layout if different --}}

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">{{ $header_title ?? 'My Notice Board' }}</h3></div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      {{-- Filters --}}
      <form method="GET" class="card card-body mb-3">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">From</label>
            <input type="date" name="from" class="form-control" value="{{ request('from') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">To</label>
            <input type="date" name="to" class="form-control" value="{{ request('to') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Search (subject or text)</label>
            <input type="text" name="q" class="form-control" placeholder="Type to search…" value="{{ request('q') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label">Notice ID</label>
            <input type="number" name="notice_id" class="form-control" value="{{ request('notice_id') }}">
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">Filter</button>
            <a href="{{ url()->current() }}" class="btn btn-outline-secondary">Reset</a>
          </div>
        </div>
      </form>

      {{-- List (cards) --}}
      @forelse ($notices as $n)
        <div class="card shadow-sm mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div class="fw-semibold">{{ $n->title }}</div>
            <small class="text-muted">
              {{ optional($n->publish_date)->format('d M Y') }}
            </small>
          </div>
          <div class="card-body">
            {!! $n->message !!}

            <div class="mt-3 small text-muted">
              Notice Date: {{ optional($n->notice_date)->format('d-m-Y') }}
            </div>
          </div>
        </div>
      @empty
        <div class="card">
          <div class="card-body text-center text-muted">No notices found.</div>
        </div>
      @endforelse

      <div class="mt-3">
        {{ $notices->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</main>
@endsection
