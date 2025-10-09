{{-- resources/views/pdf/exam_calendar.blade.php --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { size: A4 portrait; margin: 12mm; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#111; }
  .title { text-align:center; font-weight:700; font-size:20px; margin-bottom:8px; }
  .meta  { text-align:center; margin-bottom:12px; color:#444; }
  table { width:100%; border-collapse:collapse; }
  th, td { border:1px solid #999; padding:6px 8px; vertical-align:top; }
  th { background:#efefef; text-transform:uppercase; font-size:12px; }
  .num { text-align:center; width:40px; }
  .time { white-space:nowrap; }
</style>
</head>
<body>
  <div class="title">{{ $title }}</div>
  <div class="meta">
    <strong>Class:</strong> {{ $class->name }}
    &nbsp;|&nbsp;<strong>Exam:</strong> {{ $exam->name }}
    &nbsp;|&nbsp;<strong>Generated:</strong> {{ $generated }}
  </div>

  <table>
    <thead>
      <tr>
        <th class="num">#</th>
        <th>Date</th>
        <th>Day</th>
        <th>Time</th>
        <th>Subject</th>
        <th>Room</th>
        <th>Full</th>
        <th>Pass</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $i => $r)
        <tr>
          <td class="num">{{ $i+1 }}</td>
          <td>{{ $r['date'] }}</td>
          <td>{{ $r['day'] }}</td>
          <td class="time">{{ $r['time'] }}</td>
          <td>{{ $r['subject'] }}</td>
          <td>{{ $r['room'] }}</td>
          <td>{{ $r['full_mark'] }}</td>
          <td>{{ $r['passing_mark'] }}</td>
        </tr>
      @empty
        <tr><td colspan="8" style="text-align:center; color:#777;">No exams scheduled.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
