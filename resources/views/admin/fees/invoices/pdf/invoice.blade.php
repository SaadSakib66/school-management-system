<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Invoice</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#222; }
    .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 10px; }
    .school h2 { margin:0 0 2px; font-size:18px; }
    .meta { text-align:right; font-size:12px; }
    .box { border:1px solid #ddd; padding:8px; margin-bottom:10px; }
    table { width:100%; border-collapse: collapse; }
    th, td { border:1px solid #ddd; padding:6px; }
    th { background:#f6f6f6; }
    .right { text-align:right; }
    .muted { color:#666; font-size:11px; }
  </style>
</head>
<body>

  @php
    $student   = $invoice->student;
    $class     = $invoice->class;
    $monthName = $months[$invoice->month] ?? $invoice->month;
    $billed    = (float) ($invoice->amount - $invoice->discount + $invoice->fine);
    $paid      = (float) $invoice->payments->sum('amount');
    $due       = max(0, $billed - $paid);
  @endphp

  <div class="header">
    <div class="school">
      <h2>Payment Invoice</h2>
      <div class="muted">Academic Year: {{ $invoice->academic_year }}</div>
      <div class="muted">Month: {{ $monthName }}</div>
    </div>
    <div class="meta">
      <div><strong>Invoice #:</strong> {{ $invoice->id }}</div>
      <div><strong>Status:</strong> {{ ucfirst($invoice->status) }}</div>
      @if($invoice->due_date)
        <div><strong>Due date:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M, Y') }}</div>
      @endif
    </div>
  </div>

  <div class="box">
    <table style="border:none;">
      <tr style="border:none;">
        <td style="border:none; padding:0;">
          <strong>Student</strong><br>
          {{ trim(($student?->name ?? '') . ' ' . ($student?->last_name ?? '')) }}<br>
          <span class="muted">{{ $student?->email }}</span>
        </td>
        <td style="border:none; padding:0;" class="right">
          <strong>Class</strong><br>
          {{ $class?->name }}<br>
          <span class="muted">Student ID: {{ $student?->id }}</span>
        </td>
      </tr>
    </table>
  </div>

  <table>
    <thead>
      <tr>
        <th>Description</th>
        <th class="right">Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Monthly Tuition ({{ $monthName }} {{ $invoice->academic_year }})</td>
        <td class="right">{{ number_format($invoice->amount,2) }}</td>
      </tr>
      <tr>
        <td>Discount</td>
        <td class="right">-{{ number_format($invoice->discount,2) }}</td>
      </tr>
      <tr>
        <td>Fine</td>
        <td class="right">{{ number_format($invoice->fine,2) }}</td>
      </tr>
      <tr>
        <th>Total Billed</th>
        <th class="right">{{ number_format($billed,2) }}</th>
      </tr>
    </tbody>
  </table>

  <br>

  <table>
    <thead>
      <tr>
        <th colspan="3">Payments</th>
      </tr>
      <tr>
        <th>Date</th>
        <th>Method / Ref</th>
        <th class="right">Amount</th>
      </tr>
    </thead>
    <tbody>
      @forelse($invoice->payments as $p)
        <tr>
          <td>{{ \Carbon\Carbon::parse($p->paid_on)->format('d M, Y') }}</td>
          <td>{{ strtoupper($p->method) }} @if($p->reference) — {{ $p->reference }} @endif</td>
          <td class="right">{{ number_format($p->amount,2) }}</td>
        </tr>
      @empty
        <tr><td colspan="3" class="muted">No payments recorded.</td></tr>
      @endforelse
      <tr>
        <th colspan="2" class="right">Total Paid</th>
        <th class="right">{{ number_format($paid,2) }}</th>
      </tr>
      <tr>
        <th colspan="2" class="right">Amount Due</th>
        <th class="right">{{ number_format($due,2) }}</th>
      </tr>
    </tbody>
  </table>

  @if($invoice->notes)
    <p class="muted"><strong>Notes:</strong> {{ $invoice->notes }}</p>
  @endif

  <p class="muted" style="margin-top:10px;">Generated on {{ now()->format('d M Y, H:i') }}</p>
</body>
</html>
