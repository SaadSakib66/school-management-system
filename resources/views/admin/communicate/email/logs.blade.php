@extends('admin.layout.layout')
@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid"><h3 class="mb-0">{{ $header_title ?? 'Email Logs' }}</h3></div>
  </div>
  <div class="app-content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-body p-0">
          <table class="table table-striped mb-0">
            <thead>
              <tr><th>#</th><th>To</th><th>Role</th><th>Subject</th><th>Status</th><th>Sent At</th></tr>
            </thead>
            <tbody>
              @foreach($logs as $i => $log)
                <tr>
                  <td>{{ $logs->firstItem() + $i }}</td>
                  <td>{{ $log->email }}</td>
                  <td>{{ ucfirst($log->role) }}</td>
                  <td>{{ $log->subject }}</td>
                  <td><span class="badge {{ $log->status==='sent'?'bg-success':'bg-danger' }}">{{ $log->status }}</span></td>
                  <td>{{ optional($log->sent_at)->format('d-m-Y H:i') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
          <div class="p-3">{{ $logs->links('pagination::bootstrap-5') }}</div>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
