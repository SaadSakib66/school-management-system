{{-- resources/views/admin/fees/reports/student_statement.blade.php --}}
@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h3 class="mb-0">Student Statement</h3>

      {{-- ✅ PDF (filters preserved) --}}
      <a
        href="{{ route('admin.fees.student_statement.pdf', request()->only(['student_id','academic_year'])) }}"
        class="btn btn-outline-primary"
        target="_blank"
      >
        <i class="bi bi-download"></i> Download PDF
      </a>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      @include('admin.message')

      {{-- =========================
           Filter
         ========================= --}}
      <form method="get" class="card card-body mb-3">
        <div class="row g-2">
          <div class="col-md-4">
            <input type="number" min="1" name="student_id" value="{{ request('student_id') }}"
                   class="form-control" placeholder="Student ID" required>
          </div>
          <div class="col-md-4">
            <input type="text" name="academic_year" value="{{ request('academic_year') }}"
                   class="form-control" placeholder="Academic Year (optional)">
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-secondary flex-fill">View</button>
            <a href="{{ route('admin.fees.reports.student-statement') }}" class="btn btn-outline-dark">Reset</a>
          </div>
        </div>
      </form>

      @php
        // Group by academic year for year-wise subtotals
        $grouped = $invoices->groupBy('academic_year');

        $grandBilled = 0.00;
        $grandPaid   = 0.00;
        $grandDue    = 0.00;

        // Try to fetch student/class info from first invoice (if present)
        $firstInv  = $invoices->first();
        $theStudent = $firstInv?->student;
      @endphp

      {{-- =========================
           Student quick meta (optional)
         ========================= --}}
      @if($theStudent)
      <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-4 align-items-center">
          <div>
            <div class="fw-semibold">Student</div>
            <div>{{ trim(($theStudent->name ?? '') . ' ' . ($theStudent->last_name ?? '')) }}</div>
          </div>
          <div>
            <div class="fw-semibold">Email</div>
            <div>{{ $theStudent->email }}</div>
          </div>
          <div>
            <div class="fw-semibold">Mobile</div>
            <div>{{ $theStudent->mobile_number }}</div>
          </div>
          <div>
            <div class="fw-semibold">Student ID</div>
            <div>{{ $theStudent->id }}</div>
          </div>
        </div>
      </div>
      @endif

      {{-- =========================
           Year-wise tables
         ========================= --}}
      @forelse($grouped as $year => $yearInvoices)
        @php
          $yearBilled = 0.00;
          $yearPaid   = 0.00;
          $yearDue    = 0.00;
        @endphp

        <div class="card card-primary card-outline mb-4">
          <div class="card-header">
            <strong>Academic Year:</strong> {{ $year ?: 'N/A' }}
          </div>

          <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
              <thead>
                <tr>
                  <th>Month</th>
                  <th>Class</th>
                  <th class="text-end">Billed</th>
                  <th class="text-end">Paid</th>
                  <th class="text-end">Due</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($yearInvoices as $inv)
                  @php
                    // Prefer controller-computed fields (with components), fallback to legacy
                    $billed = (float) ($inv->computed_billed ?? ($inv->amount - $inv->discount + $inv->fine));
                    $paid   = (float) ($inv->computed_paid   ?? $inv->payments->sum('amount'));
                    $due    = (float) ($inv->computed_due    ?? max(0, $billed - $paid));

                    $yearBilled += $billed;
                    $yearPaid   += $paid;
                    $yearDue    += $due;
                  @endphp
                  <tr>
                    <td>{{ $months[$inv->month] ?? $inv->month }}</td>
                    <td>{{ $inv->class?->name }}</td>
                    <td class="text-end">{{ number_format($billed, 2) }}</td>
                    <td class="text-end">{{ number_format($paid, 2) }}</td>
                    <td class="text-end">{{ number_format($due, 2) }}</td>
                    <td>
                      <span class="badge bg-{{ $inv->status=='paid' ? 'success' : ($inv->status=='partial' ? 'warning' : 'danger') }}">
                        {{ ucfirst($inv->status) }}
                      </span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="2" class="text-end">Year Total</th>
                  <th class="text-end">{{ number_format($yearBilled, 2) }}</th>
                  <th class="text-end">{{ number_format($yearPaid, 2) }}</th>
                  <th class="text-end">{{ number_format($yearDue, 2) }}</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        @php
          $grandBilled += $yearBilled;
          $grandPaid   += $yearPaid;
          $grandDue    += $yearDue;
        @endphp
      @empty
        <div class="card">
          <div class="card-body text-center text-muted py-4">
            No invoices
          </div>
        </div>
      @endforelse

      {{-- =========================
           Grand totals (all years)
         ========================= --}}
      @if($invoices->isNotEmpty())
      <div class="card">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <div class="text-muted">Grand Billed</div>
                <div class="fs-5 fw-semibold">{{ number_format($grandBilled, 2) }}</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <div class="text-muted">Grand Paid</div>
                <div class="fs-5 fw-semibold">{{ number_format($grandPaid, 2) }}</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <div class="text-muted">Grand Due</div>
                <div class="fs-5 fw-semibold">{{ number_format($grandDue, 2) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      @endif

    </div>
  </div>
</main>
@endsection
