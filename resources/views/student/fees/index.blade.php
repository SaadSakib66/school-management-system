@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0">My Fees</h3></div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      @include('admin.message')

      <div class="card card-primary card-outline">
        <div class="card-body table-responsive p-0">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Year/Month</th>
                <th class="text-end">Billed</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Due</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
            @forelse($invoices as $inv)
              @php
                $billed = $inv->amount - $inv->discount + $inv->fine;
              @endphp
              <tr>
                <td>{{ $inv->academic_year }} / {{ str_pad($inv->month,2,'0',STR_PAD_LEFT) }}</td>
                <td class="text-end">{{ number_format($billed,2) }}</td>
                <td class="text-end">{{ number_format($inv->paid_amount,2) }}</td>
                <td class="text-end">{{ number_format($inv->due_amount,2) }}</td>
                <td><span class="badge bg-{{ $inv->status=='paid'?'success':($inv->status=='partial'?'warning':'danger') }}">{{ ucfirst($inv->status) }}</span></td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted py-4">No invoices yet.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          {{ $invoices->links() }}
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
