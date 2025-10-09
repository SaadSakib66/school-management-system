<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Exam Result</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h1,h2,h3 { margin: 0 0 8px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 6px 8px; vertical-align: top; }
    th { background: #f2f2f2; }
    .muted { color: #666; }
    .mb-8 { margin-bottom: 8px; }
    .mb-16 { margin-bottom: 16px; }
    .text-success { color: #0a7f27; }
    .text-danger  { color: #c1121f; }
    .text-end { text-align: right; }
  </style>
</head>
<body>
  <h2 class="mb-8">Exam Result</h2>
  <div class="mb-16">
    <div><strong>Student:</strong> {{ trim(($student->name ?? '').' '.($student->last_name ?? '')) }}</div>
    <div><strong>Class:</strong> {{ $class->name ?? '—' }}</div>
    <div><strong>Exam:</strong> {{ $exam_name ?? '—' }}</div>
    <div class="muted"><strong>Generated:</strong> {{ \Carbon\Carbon::parse($generated_at)->format('Y-m-d H:i') }}</div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Subject</th>
        <th>Class Work</th>
        <th>Test Work</th>
        <th>Home Work</th>
        <th>Exam</th>
        <th>Total</th>
        <th>Passing</th>
        <th>Full</th>
        <th>Result</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        <tr>
          <td>{{ $r['subject'] }}</td>
          <td>{{ $r['class_work'] }}</td>
          <td>{{ $r['test_work'] }}</td>
          <td>{{ $r['home_work'] }}</td>
          <td>{{ $r['exam'] }}</td>
          <td><strong>{{ $r['total'] }}</strong></td>
          <td>{{ $r['passing_mark'] }}</td>
          <td>{{ $r['full_mark'] }}</td>
          <td class="{{ ($r['result'] ?? '') === 'Pass' ? 'text-success' : 'text-danger' }}">{{ $r['result'] }}</td>
        </tr>
      @empty
        <tr><td colspan="9" class="muted">No data.</td></tr>
      @endforelse
      <tr>
        <th colspan="5" class="text-end">Grand Total:</th>
        <th>{{ $grandTotal }}/{{ $grandFull }}</th>
        <th colspan="2" class="text-end">Percentage:</th>
        <th>{{ $percentage !== null ? ($percentage.'%') : '—' }}</th>
      </tr>
      <tr>
        <th colspan="8" class="text-end">Overall Result:</th>
        <th class="{{ ($overall ?? '') === 'Pass' ? 'text-success' : 'text-danger' }}">{{ $overall }}</th>
      </tr>
    </tbody>
  </table>
</body>
</html>
