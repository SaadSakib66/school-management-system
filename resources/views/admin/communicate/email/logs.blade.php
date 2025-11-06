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
          <table class="table table-striped mb-0 align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>To</th>
                <th>Role</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Sent At</th>
              </tr>
            </thead>
            <tbody>
              @forelse($logs as $i => $log)
                <tr>
                  <td>{{ $logs->firstItem() + $i }}</td>
                  <td>{{ $log->email ?? '—' }}</td>
                  <td>{{ ucfirst($log->role) }}</td>
                  <td class="text-truncate" style="max-width:420px">{{ $log->subject }}</td>
                  <td>
                    @php
                      $map = [
                        'queued'     => 'bg-secondary',
                        'processing' => 'bg-info',
                        'sent'       => 'bg-success',
                        'failed'     => 'bg-danger'
                      ];
                      $class = $map[$log->status] ?? 'bg-secondary';
                    @endphp
                    <span class="badge {{ $class }}">{{ strtoupper($log->status) }}</span>
                    @if($log->status === 'failed' && $log->error)
                      <div class="small text-danger mt-1" style="max-width:420px; white-space:normal">{{ $log->error }}</div>
                    @endif
                  </td>
                  <td>{{ optional($log->sent_at)->format('d-m-Y H:i') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-muted p-4">No email logs yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
          <div class="p-3">{{ $logs->links('pagination::bootstrap-5') }}</div>
        </div>
      </div>

    </div>
  </div>
</main>
@endsection
