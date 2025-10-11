<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Student Statement</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h2 { margin: 0 0 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
    th { background: #f2f2f2; }
    .text-right { text-align: right; }
    .small { color:#666; font-size:11px; }
  </style>
</head>
<body>
  <h2>Student Statement</h2>
  @if(isset($filters['student_id']))
    <p class="small">Student ID: {{ $filters['student_id'] }}
      @if(!empty($filters['academic_year'])) | Year: {{ $filters['academic_year'] }} @endif
    </p>
  @endif

  <table>
    <thead>
      <tr>
        <th>Academic Year</th>
        <th>Month</th>
        <th>Class</th>
        <th class="text-right">Billed</th>
        <th class="text-right">Paid</th>
        <th class="text-right">Due</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($invoices as $inv)
        @php
          $billed = (float) ($inv->amount - $inv->discount + $inv->fine);
          $paid   = (float) $inv->payments->sum('amount');
          $due    = max(0, $billed - $paid);
        @endphp
        <tr>
          <td>{{ $inv->academic_year }}</td>
          <td>{{ $months[$inv->month] ?? $inv->month }}</td>
          <td>{{ $inv->class?->name }}</td>
          <td class="text-right">{{ number_format($billed, 2) }}</td>
          <td class="text-right">{{ number_format($paid, 2) }}</td>
          <td class="text-right">{{ number_format($due, 2) }}</td>
          <td>{{ ucfirst($inv->status) }}</td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-right">No invoices</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
