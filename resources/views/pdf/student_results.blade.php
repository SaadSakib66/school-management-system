<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { size: A4 portrait; margin: 12mm; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#111; }
  .title { text-align:center; font-weight:700; font-size:20px; margin-bottom:8px; }
  .meta  { text-align:center; margin-bottom:12px; color:#444; }
  .section-title { font-weight:700; font-size:14px; margin: 14px 0 6px; }
  table { width:100%; border-collapse:collapse; }
  th, td { border:1px solid #999; padding:6px 8px; vertical-align:top; }
  th { background:#efefef; text-transform:uppercase; font-size:12px; }
  .right { text-align:right; }
  .pass { color: #1a7f37; font-weight:700; }
  .fail { color: #c11; font-weight:700; }
</style>
</head>
<body>
  <div class="title">{{ $title }}</div>
  <div class="meta">
    <strong>Student:</strong> {{ $studentName }}
    &nbsp;|&nbsp;<strong>Class:</strong> {{ $className }}
    &nbsp;|&nbsp;<strong>Generated:</strong> {{ $generated }}
  </div>

  @forelse($sections as $sec)
    <div class="section-title">{{ $sec['exam']->name }}</div>
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
        @foreach($sec['rows'] as $r)
          <tr>
            <td>{{ $r['subject'] }}</td>
            <td class="right">{{ $r['class_work'] }}</td>
            <td class="right">{{ $r['test_work'] }}</td>
            <td class="right">{{ $r['home_work'] }}</td>
            <td class="right">{{ $r['exam'] }}</td>
            <td class="right"><strong>{{ $r['total'] }}</strong></td>
            <td class="right">{{ $r['passing_mark'] }}</td>
            <td class="right">{{ $r['full_mark'] }}</td>
            <td class="{{ $r['result'] === 'Pass' ? 'pass' : 'fail' }}">{{ $r['result'] }}</td>
          </tr>
        @endforeach

        {{-- Summary rows --}}
        <tr>
          <th colspan="5" class="right">Grand Total:</th>
          <th class="right">{{ $sec['grandTotal'] }}/{{ $sec['grandFull'] }}</th>
          <th colspan="2" class="right">Percentage:</th>
          <th>{{ $sec['percentage'] !== null ? $sec['percentage'].'%' : '-' }}</th>
        </tr>
        <tr>
          <th colspan="8" class="right">Overall Result:</th>
          <th class="{{ $sec['overall'] === 'Pass' ? 'pass' : 'fail' }}">{{ $sec['overall'] }}</th>
        </tr>
        <tr>
          <th colspan="8" class="right">Grade:</th>
          <th>{{ $sec['grade'] ?? '-' }}</th>
        </tr>
      </tbody>
    </table>
  @empty
    <div style="text-align:center; color:#777; margin-top:20px;">
      No results to show.
    </div>
  @endforelse
</body>
</html>
