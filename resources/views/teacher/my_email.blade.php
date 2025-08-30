@extends('admin.layout.layout') {{-- or your student/teacher/parent layout --}}
@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid"><h3 class="mb-0">{{ $header_title ?? 'My Emails' }}</h3></div>
  </div>
  <div class="app-content">
    <div class="container-fluid">

      <form method="GET" class="card card-body mb-3">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Search</label>
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Subject or text">
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary w-100">Filter</button>
            <a href="{{ url()->current() }}" class="btn btn-outline-secondary w-100">Reset</a>
          </div>
        </div>
      </form>

      @forelse ($logs as $log)
        <a href="{{ route(request()->routeIs('student.*') ? 'student.inbox.show' : (request()->routeIs('teacher.*') ? 'teacher.inbox.show' : 'parent.inbox.show'), $log->id) }}"
           class="card mb-2 text-decoration-none text-body">
          <div class="card-body d-flex justify-content-between">
            <div>
              <div class="fw-semibold">{{ $log->subject }}</div>
              <div class="small text-muted">{{ \Illuminate\Support\Str::limit($log->body_text, 140) }}</div>
            </div>
            <div class="text-end">
              @if(!$log->is_read)
                <span class="badge bg-primary">new</span>
              @endif
              <div class="small text-muted">{{ optional($log->sent_at)->format('d M Y, H:i') }}</div>
            </div>
          </div>
        </a>
      @empty
        <div class="card"><div class="card-body text-center text-muted">No emails found.</div></div>
      @endforelse

      <div class="mt-3">{{ $logs->links('pagination::bootstrap-5') }}</div>
    </div>
  </div>
</main>
@endsection
