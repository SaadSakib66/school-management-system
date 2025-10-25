<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Notice PDF</title>
  @php
    // Embed the font so dompdf never needs to read from disk
    $bnFontPath = public_path('fonts/SolaimanLipi.ttf');   // keep your TTF here
    $bnFontData = base64_encode(@file_get_contents($bnFontPath));
  @endphp
  <style>
    @font-face{
      font-family: 'SolaimanLipi';
      src: url('data:font/truetype;base64,{{ $bnFontData }}') format('truetype');
      font-weight: normal;
      font-style: normal;
    }

    /* IMPORTANT: Put the Bengali-capable font FIRST */
    html, body {
      font-family: 'SolaimanLipi', 'DejaVu Sans', sans-serif;
      font-size: 12px; line-height: 1.5; color:#111;
    }

    h2 { margin:0 0 8px 0; font-weight:700; /* inherits font-family */ }
    .meta { color:#555; margin-bottom:12px; }
    .badge { display:inline-block; padding:2px 6px; margin-right:4px; border-radius:3px; background:#eee; }
    .section { margin-top:12px; }
  </style>
</head>
<body>
  <h2>{{ $notice->title }}</h2>

  <div class="meta">
    Notice Date:
    {{ $notice->notice_date ? \Illuminate\Support\Carbon::parse($notice->notice_date)->format('d-m-Y') : '—' }}
    |
    Publish Date:
    {{ $notice->publish_date ? \Illuminate\Support\Carbon::parse($notice->publish_date)->format('d-m-Y') : '—' }}<br>
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
