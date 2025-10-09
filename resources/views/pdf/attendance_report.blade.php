<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Attendance Report</title>
  <style>
    body{ font-family: DejaVu Sans, sans-serif; font-size:12px; }
    h3{ margin:0 0 6px 0; }
    .meta{ margin-bottom:10px; }
    table{ width:100%; border-collapse:collapse; }
    th,td{ border:1px solid #888; padding:6px; }
    th{ background:#f5f5f5; }
    .badge{ padding:2px 6px; border-radius:3px; display:inline-block; }
    .bg-success{ background:#d1e7dd; }
    .bg-warning{ background:#fff3cd; }
    .bg-info{ background:#cff4fc; }
    .bg-danger{ background:#f8d7da; }
    .text-right{ text-align:right; }

    .report-header {
        text-align: center;
        margin-top: 20px;
        margin-bottom: 20px;
    }
    .report-header h1 {
        font-weight: bold;
        margin-bottom: 5px;
    }
    .report-header h3 {
        font-size: 1.1rem;
        margin: 0;
    }

  </style>
</head>
<body>

<div class="report-header">
    <h1>Attendance Report</h1>
    <h3>Class: {{ $class->name ?? ('#'.$class->id) }}</h3>
</div>

<div class="meta">
  <strong>Range:</strong>
  @if(isset($range['single'])) {{ $range['single'] }}
  @else {{ $range['from'] }} to {{ $range['to'] }} @endif
  &nbsp;&nbsp;|&nbsp;&nbsp;
  <strong>Type:</strong> {{ $filter['type'] }}
  @if($filter['student'])
    &nbsp;&nbsp;|&nbsp;&nbsp;<strong>Student:</strong>
    {{ $filter['student']->roll_number ?? $filter['student']->id }} —
    {{ $filter['student']->name }} {{ $filter['student']->last_name }}
  @endif
  &nbsp;&nbsp;|&nbsp;&nbsp;<strong>Generated:</strong> {{ $generated }}
</div>

<table>
  <thead>
    <tr>
      <th style="width:120px">Date</th>
      <th style="width:130px">Student ID</th>
      <th>Student Name</th>
      <th style="width:130px">Type</th>
      <th style="width:160px">Created By</th>
    </tr>
  </thead>
  <tbody>
    @forelse($rows as $r)
      @php
        $label = $typeMap[$r->attendance_type] ?? 'Unknown';
        $badge = match((int)$r->attendance_type){
          1=>'bg-success',2=>'bg-warning',3=>'bg-info',4=>'bg-danger',default=>''
        };
      @endphp
      <tr>
        <td>{{ \Carbon\Carbon::parse($r->attendance_date)->format('d-m-Y') }}</td>
        <td>{{ $r->student?->roll_number ?? $r->student_id }}</td>
        <td>{{ $r->student?->name }} {{ $r->student?->last_name }}</td>
        <td><span class="badge {{ $badge }}">{{ $label }}</span></td>
        <td>{{ $r->creator?->name }} {{ $r->creator?->last_name }}</td>
      </tr>
    @empty
      <tr><td colspan="5" class="text-right">No data.</td></tr>
    @endforelse
  </tbody>
</table>

</body>
</html>
