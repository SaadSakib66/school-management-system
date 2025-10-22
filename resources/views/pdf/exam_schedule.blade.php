<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    /* Tight, consistent margins like your other PDFs */
    @page { margin: 8px 18px 16px 18px; }
    html, body { margin:0; padding:0; }

    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    .title { text-align:center; font-weight:700; font-size:20px; margin-bottom:8px; }
    .meta { text-align:center; margin-bottom:10px; color:#444; }

    /* Give the table left/right breathing room */
    .group { padding: 0 16px; }

    table { width:100%; border-collapse: collapse; }
    th, td { border:1px solid #999; padding:6px; vertical-align:middle; }
    th { background:#efefef; text-transform:uppercase; font-size:12px; }
    .w-subj { width:28%; }
    .w-date { width:14%; }
    .w-time { width:12%; }
    .w-room { width:12%; }
    .w-mrk  { width:12%; }
  </style>
</head>
<body>

  {{-- ✅ Universal School Header --}}
  @include('pdf.partials.school_header')

  <div class="title">EXAM SCHEDULE</div>
  <div class="meta">
    <strong>Exam:</strong> {{ $exam->name }}
    &nbsp;|&nbsp; <strong>Class:</strong> {{ $class->name }}
    &nbsp;|&nbsp; <strong>Generated:</strong> {{ $generated }}
  </div>

  <div class="group">
    <table>
      <thead>
        <tr>
          <th class="w-subj">Subject</th>
          <th class="w-date">Date</th>
          <th class="w-time">Start</th>
          <th class="w-time">End</th>
          <th class="w-room">Room</th>
          <th class="w-mrk">Full</th>
          <th class="w-mrk">Passing</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $r)
          <tr>
            <td>{{ $r['subject'] }}</td>
            <td>{{ $r['exam_date'] }}</td>
            <td>{{ $r['start_time'] }}</td>
            <td>{{ $r['end_time'] }}</td>
            <td>{{ $r['room_number'] }}</td>
            <td>{{ $r['full_mark'] }}</td>
            <td>{{ $r['passing_mark'] }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align:center; padding:12px;">No rows to show.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</body>
</html>
