{{-- resources/views/pdf/class_schedule.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Class Routine</title>
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
  <h2 class="mb-8">Class Routine</h2>
  <div class="mb-16">
    <div><strong>Student:</strong> {{ trim(($student->name ?? '').' '.($student->last_name ?? '')) }}</div>
    <div><strong>Class:</strong> {{ $class->name ?? '—' }}</div>
    <div class="muted"><strong>Generated:</strong> {{ \Carbon\Carbon::parse($generated_at)->format('Y-m-d H:i') }}</div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:20%">Day</th>
        <th style="width:35%">Subject</th>
        <th style="width:20%">Start</th>
        <th style="width:20%">End</th>
        <th style="width:5%">Room</th>
      </tr>
    </thead>
    <tbody>
      @foreach($weeks as $week)
        @php $items = $byWeek[$week->id] ?? collect(); @endphp

        @if($items->isEmpty())
          <tr>
            <td><strong>{{ $week->name }}</strong></td>
            <td colspan="4" class="muted">No classes scheduled.</td>
          </tr>
        @else
          @foreach($items as $i => $row)
            <tr>
              @if($i === 0)
                <td rowspan="{{ $items->count() }}"><strong>{{ $week->name }}</strong></td>
              @endif

              {{-- IMPORTANT: use $row->subject, not $subject --}}
              <td>{{ $row->subject->name ?? '—' }}</td>

              <td>
                @if(!empty($row->start_time))
                  {{ \Carbon\Carbon::createFromFormat('H:i:s', $row->start_time)->format('h:i A') }}
                @else
                  —
                @endif
              </td>
              <td>
                @if(!empty($row->end_time))
                  {{ \Carbon\Carbon::createFromFormat('H:i:s', $row->end_time)->format('h:i A') }}
                @else
                  —
                @endif
              </td>
              <td>{{ $row->room_number ?? '—' }}</td>
            </tr>
          @endforeach
        @endif
      @endforeach
    </tbody>
  </table>
</body>
</html>
