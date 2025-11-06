<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Student Monthly Summary</title>
  <style>
    /* ↓ Control the page margins Dompdf uses */
    @page {
        margin: 8px 18px 16px 18px; /* top, right, bottom, left */
    }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
    th { background: #f2f2f2; }
    .text-right { text-align: right; }
    .small { color:#666; font-size:11px; }
  </style>
</head>
<body>
  @include('pdf.partials.school_header')

  <h2>Student Monthly Summary</h2>
  @if(!empty($filters))
    <p class="small">
      @if(!empty($filters['academic_year'])) Year: {{ $filters['academic_year'] }} @endif
      @if(!empty($filters['student_id'])) | Student ID: {{ $filters['student_id'] }} @endif
    </p>
  @endif

  <table>
    <thead>
      <tr>
        <th>Student</th>
        <th>Year</th>
        <th>Month</th>
        <th class="text-right">Invoices</th>
        <th class="text-right">Total Billed</th>
        <th class="text-right">Total Paid</th>
        <th class="text-right">Total Due</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        @php
          $billed = (float) $r->total_billed;
          $paid   = (float) $r->total_paid;
          $due    = max(0, $billed - $paid);
        @endphp
        <tr>
          <td>
            {{ trim(($r->student?->name ?? '').' '.($r->student?->last_name ?? '')) }}
            <div class="small">ID: {{ $r->student_id }}</div>
          </td>
          <td>{{ $r->academic_year }}</td>
          <td>{{ $months[$r->month] ?? $r->month }}</td>
          <td class="text-right">{{ number_format($r->total_invoices) }}</td>
          <td class="text-right">{{ number_format($billed, 2) }}</td>
          <td class="text-right">{{ number_format($paid, 2) }}</td>
          <td class="text-right">{{ number_format($due, 2) }}</td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-right">No data</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
