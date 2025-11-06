@extends('admin.layout.layout')
@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h3 class="mb-0">Student Monthly Summary</h3>
      <a
        href="{{ route('admin.fees.student_monthly.pdf', request()->only(['academic_year','student_id'])) }}"
        class="btn btn-outline-primary" target="_blank">
        <i class="bi bi-download"></i> View/Print PDF
      </a>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      @include('admin.message')

      <form method="get" class="card card-body mb-3">
        <div class="row g-2">
          <div class="col-md-4">
            <input type="text" name="academic_year" value="{{ request('academic_year') }}" class="form-control" placeholder="Academic Year (e.g. 2025-2026)">
          </div>
          <div class="col-md-4">
            <input type="number" min="1" name="student_id" value="{{ request('student_id') }}" class="form-control" placeholder="Student ID">
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-secondary flex-fill">Filter</button>
            <a href="{{ route('admin.fees.reports.student-monthly') }}" class="btn btn-outline-dark">Reset</a>
          </div>
        </div>
      </form>

      <div class="card card-primary card-outline">
        <div class="card-body table-responsive p-0">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>Student</th>
                <th>Year</th>
                <th>Month</th>
                <th class="text-end">Invoices</th>
                <th class="text-end">Total Billed</th>
                <th class="text-end">Total Paid</th>
                <th class="text-end">Total Due</th>
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
                    <div class="text-muted small">ID: {{ $r->student_id }}</div>
                  </td>
                  <td>{{ $r->academic_year }}</td>
                  <td>{{ $months[$r->month] ?? $r->month }}</td>
                  <td class="text-end">{{ number_format($r->total_invoices) }}</td>
                  <td class="text-end">{{ number_format($billed,2) }}</td>
                  <td class="text-end">{{ number_format($paid,2) }}</td>
                  <td class="text-end">{{ number_format($due,2) }}</td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No data</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</main>
@endsection
