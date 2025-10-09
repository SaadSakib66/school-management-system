<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Exam Result</title>
  <style>
    body { font-family: DejaVu Sans, calibri, sans-serif; font-size: 12px; }
    .header { text-align:center; margin-bottom:12px; }
    .meta { font-size:12px; margin-bottom:10px; }
    table { width:100%; border-collapse: collapse; margin-bottom: 18px; }
    th, td { border:1px solid #888; padding:6px; }
    th { background:#f2f2f2; }
    .totals { text-align:right; margin-top:6px; }
    .pagebreak { page-break-after: always; }
    .badge { display:inline-block; padding:2px 6px; border-radius:3px; }
    .bg-pass { background:#cce5ff; }
    .bg-fail { background:#f8d7da; }
  </style>
</head>
<body>

<div class="header">
  <h3 style="margin:0;">{{ $exam->name }} — Class: {{ $class->name ?? ('#'.$class->id) }}</h3>
  <div class="meta">Generated: {{ $generatedAt }}</div>
</div>

@foreach($printables as $i => $p)
  <div class="meta">
    <strong>Student:</strong> {{ $p['student']->name }} {{ $p['student']->last_name }}
    @if(!empty($p['student']->roll_number))
      &nbsp;&nbsp;<strong>Roll:</strong> {{ $p['student']->roll_number }}
    @endif
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:24%">Subject</th>
        <th>Class Work</th>
        <th>Test Work</th>
        <th>Home Work</th>
        <th>Exam</th>
        <th>Total</th>
        <th>Pass Mark</th>
        <th>Full Mark</th>
        <th>Result</th>
      </tr>
    </thead>
    <tbody>
      @foreach($p['rows'] as $r)
        <tr>
          <td>{{ $r['subject'] }}</td>
          <td class="text-center">{{ $r['class_work'] }}</td>
          <td class="text-center">{{ $r['test_work'] }}</td>
          <td class="text-center">{{ $r['home_work'] }}</td>
          <td class="text-center">{{ $r['exam'] }}</td>
          <td class="text-center">{{ $r['total'] }}</td>
          <td class="text-center">{{ $r['passing_mark'] }}</td>
          <td class="text-center">{{ $r['full_mark'] }}</td>
          <td class="text-center">
            <span class="badge {{ $r['result']==='Pass' ? 'bg-pass' : 'bg-fail' }}">{{ $r['result'] }}</span>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="totals">
    <strong>Grand Total:</strong> {{ $p['grandTotal'] }} / {{ $p['grandFull'] }}
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <strong>Percentage:</strong>
    @if(!is_null($p['percentage'])) {{ number_format($p['percentage'],2) }}% @else – @endif
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <strong>Overall:</strong> {{ $p['overall'] }}
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <strong>Grade:</strong> {{ $p['grade'] ?? '–' }}
  </div>

  {{-- @if($i < count($printables)-1)
    <div class="pagebreak"></div>
  @endif --}}
@endforeach

</body>
</html>
