<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Exam Schedule</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h1,h2,h3 { margin: 0 0 8px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 6px 8px; vertical-align: top; }
    th { background: #f2f2f2; }
    .muted { color: #666; }
    .mb-8 { margin-bottom: 8px; }
    .mb-16 { margin-bottom: 16px; }
  </style>
</head>
<body>
  <h2 class="mb-8">Exam Schedule</h2>
  <div class="mb-16">
    <div><strong>Student:</strong> {{ trim(($student->name ?? '').' '.($student->last_name ?? '')) }}</div>
    <div><strong>Class:</strong> {{ $class->name ?? '—' }}</div>
    <div><strong>Exam:</strong> {{ $exam->name ?? '—' }}</div>
    <div class="muted"><strong>Generated:</strong> {{ \Carbon\Carbon::parse($generated_at)->format('Y-m-d H:i') }}</div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:32%">Subject</th>
        <th style="width:18%">Date</th>
        <th style="width:18%">Start</th>
        <th style="width:18%">End</th>
        <th style="width:14%">Room</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $row)
        <tr>
          <td>{{ $row->subject?->name ?? '—' }}</td>
          <td>{{ $row->exam_date ? \Carbon\Carbon::parse($row->exam_date)->format('d-m-Y') : '—' }}</td>
          <td>{{ $row->start_time ? \Carbon\Carbon::parse($row->start_time)->format('h:i A') : '—' }}</td>
          <td>{{ $row->end_time ? \Carbon\Carbon::parse($row->end_time)->format('h:i A') : '—' }}</td>
          <td>{{ $row->room_number ?? '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">No schedule found.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
