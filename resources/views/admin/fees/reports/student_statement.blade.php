@extends('admin.layout.layout')
@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h3 class="mb-0">Student Statement</h3>
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

      {{-- Filter: pick student and optional year (your UI to choose student id goes elsewhere) --}}
      <form method="get" class="card card-body mb-3">
        <div class="row g-2">
          <div class="col-md-4">
            <input type="number" min="1" name="student_id" value="{{ request('student_id') }}" class="form-control" placeholder="Student ID" required>
          </div>
          <div class="col-md-4">
            <input type="text" name="academic_year" value="{{ request('academic_year') }}" class="form-control" placeholder="Academic Year (optional)">
          </div>
          <div class="col-md-4">
            <button class="btn btn-secondary w-100">View</button>
          </div>
        </div>
      </form>

      <div class="card card-primary card-outline">
        <div class="card-body table-responsive p-0">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>Year</th>
                <th>Month</th>
                <th>Class</th>
                <th class="text-end">Billed</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Due</th>
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
                  <td class="text-end">{{ number_format($billed, 2) }}</td>
                  <td class="text-end">{{ number_format($paid, 2) }}</td>
                  <td class="text-end">{{ number_format($due, 2) }}</td>
                  <td>
                    <span class="badge bg-{{ $inv->status=='paid' ? 'success' : ($inv->status=='partial' ? 'warning' : 'danger') }}">
                      {{ ucfirst($inv->status) }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No invoices</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</main>
@endsection
