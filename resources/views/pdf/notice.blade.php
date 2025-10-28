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
        url('{{ asset('fonts/SolaimanLipi.woff') }}') format('woff'),
        url('{{ asset('fonts/SolaimanLipi.ttf') }}') format('truetype');
      font-weight:400; font-style:normal; font-display:swap;
    }

    /* Base sizing bumped up */
    html,body{
      font-family:'SolaimanLipi','Noto Sans Bengali','Noto Sans',system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,sans-serif;
      font-size:20px;          /* was 13px */
      line-height:1.65;        /* was 1.55 */
      color:#111; background:#fff;
    }

    .container{ max-width:900px; margin:28px auto; padding:0 18px; }  /* a bit wider */

    /* Buttons (unchanged except bigger text) */
    .actions.no-print{ display:flex; gap:10px; justify-content:flex-end; margin-bottom:12px; }
    .btn{ border:1px solid #cfd1d4; padding:8px 12px; border-radius:8px; background:#f7f7f9; cursor:pointer; font-size:15px; }
    .btn:hover{ background:#eee; }

    /* Keep header centered */
    .header-wrap{ text-align:center; margin:0 auto 10px auto; }

    /* Date */
    .date{ text-align:right; font-size:14px; color:#444; margin-top:6px; }

    /* Notice title bigger */
    .notice-head{ text-align:center; margin:26px 0 14px; }
    .notice-title{
      display:inline-block;
      font-size:36px;          /* was 30px */
      font-weight:900;
      letter-spacing:.3px;
      border-bottom:4px solid #111;
      padding:0 8px 4px;
    }

    /* Content bigger */
    .content{
      margin-top:28px;
      font-size:25px;          /* was 14px */
    }
    .content p{ margin:0 0 12px 0; }
    .content b, .content strong{ font-weight:800; }
    .content ul, .content ol{ margin:8px 0 12px 24px; }

    /* Signature block larger */
    .sign-block{ margin-top:80px; display:inline-block; }
    .sign-name{ font-weight:900; font-size:18px; }
    .sign-title,.sign-org{ color:#444; font-size:16px; }

    /* Print */
    @page{ size:A4; margin:14mm; }     /* a touch more margin for bigger text */
    @media print{
      .no-print{ display:none !important; }
      body{ -webkit-print-color-adjust:exact; print-color-adjust:exact; }
      .container{ max-width:100%; margin:0; padding:0 10mm; } /* keep readable margins on print */
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
