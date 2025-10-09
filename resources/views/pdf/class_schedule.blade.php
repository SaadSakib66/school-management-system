<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
  .title { text-align:center; font-weight:700; font-size:20px; margin-bottom:8px; }
  .meta { text-align:center; margin-bottom:10px; color:#444; }
  table { width:100%; border-collapse:collapse; }
  th, td { border:1px solid #999; padding:6px; vertical-align:top; }
  th { background:#efefef; text-transform:uppercase; font-size:12px; }
  .time-col { width:12%; font-weight:700; background:#f7f7f7; }
  .cell { white-space: pre-line; }
</style>
</head>
<body>
  <div class="title">CLASS SCHEDULE</div>
  <div class="meta">
    <strong>Class:</strong> {{ $class->name }}
    @if($subject)&nbsp;|&nbsp;<strong>Subject:</strong> {{ $subject->name }}@endif
    &nbsp;|&nbsp;<strong>Generated:</strong> {{ $generated }}
  </div>

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
</body>
</html>
