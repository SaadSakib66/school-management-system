<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { margin: 8px 18px 16px 18px; }
  html, body { margin:0; padding:0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }

  .title { text-align:center; font-weight:700; font-size:18px; margin-bottom:6px; }
  .meta  { text-align:center; margin-bottom:8px; color:#444; }

  .group { padding: 0 12px; }

  table { width:100%; border-collapse:collapse; table-layout: fixed; }
  th, td { border:1px solid #999; padding:4px 5px; vertical-align:top; }
  th { background:#efefef; text-transform:uppercase; font-size:11px; }
  .time-col { width:14%; font-weight:700; background:#f7f7f7; }
  .cell { white-space: pre-line; word-wrap: break-word; }

  .page-break { page-break-after: always; }
</style>
</head>
<body>

@php
  $multi = isset($pages);
@endphp

@if($multi)
  @foreach($pages as $i => $p)
    {{-- ✅ School header on every page --}}
    @include('pdf.partials.school_header')

    <div class="title">CLASS SCHEDULE</div>
    <div class="meta">
      <strong>Class:</strong> {{ $p['class']->name }}
      @if($p['subject'])&nbsp;|&nbsp;<strong>Subject:</strong> {{ $p['subject']->name }}@endif
      &nbsp;|&nbsp;<strong>Generated:</strong> {{ $generated ?? now()->format('d M Y g:i A') }}
    </div>

    <div class="group">
      <table>
        <thead>
          <tr>
            <th class="time-col">TIME</th>
            @foreach($p['weeks'] as $w)
              <th>{{ strtoupper($w->name) }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($p['slots'] as $slotKey => $slotLabel)
            <tr>
              <td class="time-col">{{ $slotLabel }}</td>
              @foreach($p['weeks'] as $w)
                <td class="cell">{{ $p['grid'][$slotKey][$w->id] ?? '' }}</td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if($i < count($pages)-1)
      <div class="page-break"></div>
    @endif
  @endforeach
@else
  {{-- ✅ School header --}}
  @include('pdf.partials.school_header')

  <div class="title">CLASS SCHEDULE</div>
  <div class="meta">
    <strong>Class:</strong> {{ $class->name }}
    @if($subject)&nbsp;|&nbsp;<strong>Subject:</strong> {{ $subject->name }}@endif
    &nbsp;|&nbsp;<strong>Generated:</strong> {{ $generated }}
  </div>

  <div class="group">
    <table>
      <thead>
        <tr>
          <th class="time-col">TIME</th>
          @foreach($weeks as $w)
            <th>{{ strtoupper($w->name) }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($slots as $slotKey => $slotLabel)
          <tr>
            <td class="time-col">{{ $slotLabel }}</td>
            @foreach($weeks as $w)
              <td class="cell">{{ $grid[$slotKey][$w->id] ?? '' }}</td>
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
</body>
</html>
