<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  /* Tight page margins like your other PDFs */
  @page { margin: 8px 18px 16px 18px; }
  html, body { margin:0; padding:0; }

  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }

  /* Header + titles */
  .title { text-align:center; font-weight:700; font-size:20px; margin-bottom:8px; }
  .meta  { text-align:center; margin-bottom:10px; color:#444; }

  /* Give the schedule table left/right breathing room */
  .group { padding: 0 16px; }

  table { width:100%; border-collapse:collapse; }
  th, td { border:1px solid #999; padding:6px; vertical-align:top; }
  th { background:#efefef; text-transform:uppercase; font-size:12px; }
  .time-col { width:12%; font-weight:700; background:#f7f7f7; }
  .cell { white-space: pre-line; }
</style>
</head>
<body>

  {{-- ✅ Universal School Header (round logo + name + EIIN + address + website) --}}
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
</body>
</html>
