@extends('admin.layout.layout') {{-- or role layout --}}
@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid"><h3 class="mb-0">{{ $header_title ?? 'View Email' }}</h3></div>
  </div>
  <div class="app-content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <div class="fw-semibold">{{ $log->subject }}</div>
          <div class="small text-muted">{{ optional($log->sent_at)->format('d M Y, H:i') }}</div>
        </div>
        <div class="card-body">
          {!! $log->body_html !!}
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
