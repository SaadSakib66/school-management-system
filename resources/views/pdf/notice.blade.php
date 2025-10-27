{{-- <!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $notice->title }} — Notice</title>


  <style>
    @font-face{
      font-family: 'SolaimanLipi';
      src:
        url('{{ asset('fonts/SolaimanLipi.woff') }}') format('woff2'),
        url('{{ asset('fonts/SolaimanLipi.ttf') }}') format('truetype');
      font-weight: 400;
      font-style: normal;
      font-display: swap;
    }

    html, body {
      font-family: 'SolaimanLipi', 'Noto Sans Bengali', 'Noto Sans', system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, sans-serif;
      font-size: 13px;
      line-height: 1.55;
      color: #111;
      background: #fff;
    }

    .container { max-width: 800px; margin: 24px auto; padding: 0 16px; }
    .actions.no-print { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 16px; }
    .btn { border: 1px solid #ccc; padding: 6px 10px; border-radius: 6px; background: #f7f7f7; cursor: pointer; }
    .btn:hover { background: #eee; }

    header h1 { font-size: 22px; margin: 0 0 6px 0; }
    .meta { color:#555; margin: 6px 0 14px 0; font-size: 12px; }
    .badges { margin: 8px 0 0 0; }
    .badge { display:inline-block; padding:2px 8px; margin:0 6px 6px 0; border-radius: 12px; background:#e9eefb; border:1px solid #cfd9f0; font-size: 12px; }

    .body { margin-top: 16px; }
    .hr { height:1px; background:#eee; margin: 12px 0; }

    /* Print rules */
    @page { size: A4; margin: 12mm; }
    @media print {
      .no-print { display: none !important; }
      body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .container { max-width: 100%; margin: 0; padding: 0; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="actions no-print">
      <button class="btn" onclick="window.print()">🖨️ Print</button>
      <a class="btn" href="{{ url()->previous() }}">← Back</a>
    </div>

    <header>
      <h1>{{ $notice->title }}</h1>
      <div class="meta">
        Notice Date:
        {{ $notice->notice_date ? \Illuminate\Support\Carbon::parse($notice->notice_date)->format('d-m-Y') : '—' }}
        |
        Publish Date:
        {{ $notice->publish_date ? \Illuminate\Support\Carbon::parse($notice->publish_date)->format('d-m-Y') : '—' }}
        <br>
        Created By: {{ $notice->creator?->name ?? '—' }}
      </div>
      <div class="badges">
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
      <div class="hr"></div>
    </header>

    <main class="body">

      {!! $notice->message !!}
    </main>
  </div>
</body>
</html> --}}


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $notice->title }} — Notice</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    @font-face{
      font-family:'SolaimanLipi';
      src:
        url('{{ asset('fonts/SolaimanLipi.woff') }}') format('woff2'),
        url('{{ asset('fonts/SolaimanLipi.ttf') }}') format('truetype');
      font-weight:400; font-style:normal; font-display:swap;
    }
    html,body{
      font-family:'SolaimanLipi','Noto Sans Bengali','Noto Sans',system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,sans-serif;
      font-size:13px; line-height:1.55; color:#111; background:#fff;
    }
    .container{ max-width:820px; margin:22px auto; padding:0 16px; }

    /* simple buttons */
    .actions.no-print{ display:flex; gap:8px; justify-content:flex-end; margin-bottom:8px; }
    .btn{ border:1px solid #cfd1d4; padding:6px 10px; border-radius:8px; background:#f7f7f9; cursor:pointer; }
    .btn:hover{ background:#eee; }

    /* keep header centered: wrapper is block + centered text, no flex */
    .header-wrap{ text-align:center; margin:0 auto 8px auto; }

    .date{ text-align:right; font-size:12px; color:#444; margin-top:4px; }
    .notice-head{ text-align:center; margin:18px 0 10px; }
    .notice-title{
      display:inline-block; font-size:26px; font-weight:800; letter-spacing:.3px;
      border-bottom:3px solid #111; padding:0 6px 2px;
    }
    .content{ margin-top:18px; font-size:14px; }
    .content b,.content strong{ font-weight:800; }

    .sign-block{ margin-top:60px; display:inline-block; }
    .sign-name{ font-weight:800; font-size:14px; }
    .sign-title,.sign-org{ color:#444; }

    @page{ size:A4; margin:12mm; }
    @media print{
      .no-print{ display:none !important; }
      body{ -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      .container{ max-width:100%; margin:0; padding:0; }
    }
  </style>
</head>
<body>
  <div class="container">

    {{-- actions – outside header so they don’t influence centering --}}
    <div class="actions no-print">
      <button class="btn" onclick="window.print()">🖨️ Print</button>
      <a class="btn" href="{{ url()->previous() }}">← Back</a>
    </div>

    {{-- HEADER (centered) --}}
    <div class="header-wrap">
      @include('pdf.partials.school_header')
    </div>

    {{-- Date on the right, placed AFTER the header so it can’t push it --}}
    <div class="date">
      @php $dt = $notice->publish_date ?? $notice->notice_date ?? now(); @endphp
      Date: {{ \Illuminate\Support\Carbon::parse($dt)->format('d.m.Y') }}
    </div>

    <div class="notice-head">
      <span class="notice-title">Notice</span>
    </div>

    <div class="content">
      {!! $notice->message !!}
    </div>

    @php
      $signName  = $notice->sign_name  ?? $notice->creator?->name ?? null;
      $signTitle = $notice->sign_title ?? null;
      $signOrg   = $schoolPrint['name'] ?? null;
    @endphp

    @if($signName || $signTitle || $signOrg)
      <div class="sign-block">
        <div class="sign-name">{{ $signName }}</div>
        @if($signTitle)<div class="sign-title">{{ $signTitle }}</div>@endif
        @if($signOrg)  <div class="sign-org">{{ $signOrg }}</div>@endif
      </div>
    @endif

  </div>
</body>
</html>

