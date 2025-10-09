<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Notice PDF</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h2 { margin: 0 0 8px 0; }
    .meta { color:#555; margin-bottom: 12px; }
    .badge { display:inline-block; padding:2px 6px; margin-right:4px; border-radius:3px; background:#eee; }
    .section { margin-top: 12px; }
  </style>
</head>
<body>
  <h2>{{ $notice->title }}</h2>
  <div class="meta">
    Notice Date: {{ $notice->notice_date ? \Illuminate\Support\Carbon::parse($notice->notice_date)->format('d-m-Y') : '—' }} |
    Publish Date: {{ $notice->publish_date ? \Illuminate\Support\Carbon::parse($notice->publish_date)->format('d-m-Y') : '—' }}<br>
    Created By: {{ $notice->creator?->name ?? '—' }}
  </div>

  <div>
    @php
      $tos = collect(explode(',', (string) $notice->message_to))
              ->map(fn($v) => ucfirst(strtolower(trim($v))))
              ->filter();
    @endphp
    <strong>Message To:</strong>
    @forelse($tos as $to)
      <span class="badge">{{ $to }}</span>
    @empty
      <span>—</span>
    @endforelse
  </div>

  <div class="section">
    {!! $notice->message !!}
  </div>
</body>
</html>
