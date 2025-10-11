@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0">Invoice #{{ $invoice->id }}</h3></div>
        <div class="col-sm-6 text-sm-end">
          <a href="{{ route('admin.fees.invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Invoices
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      @include('admin.message')

      <div class="row">
        <div class="col-md-7">
          <div class="card card-body">
            <h5>Student: {{ $invoice->student?->name }}</h5>
            <p>Class: {{ $invoice->class?->name }}</p>
            <p>Year/Month: {{ $invoice->academic_year }} / {{ str_pad($invoice->month,2,'0',STR_PAD_LEFT) }}</p>
            <p>Amount: {{ number_format($invoice->amount - $invoice->discount + $invoice->fine,2) }}</p>
            <p>Paid: {{ number_format($invoice->paid_amount,2) }}</p>
            <p>Due: <strong>{{ number_format($invoice->due_amount,2) }}</strong></p>
            <p>Status:
              <span class="badge bg-{{ $invoice->status=='paid'?'success':($invoice->status=='partial'?'warning':'danger') }}">
                {{ ucfirst($invoice->status) }}
              </span>
            </p>
          </div>

          <div class="card card-body mt-3">
            <h5 class="mb-3">Payments</h5>
            <div class="table-responsive">
              <table class="table">
                <thead><tr><th>Date</th><th>Method</th><th>Ref</th><th class="text-end">Amount</th><th></th></tr></thead>
                <tbody>
                @foreach($invoice->payments as $p)
                  <tr>
                    <td>{{ $p->paid_on }}</td>
                    <td>{{ strtoupper($p->method) }}</td>
                    <td>{{ $p->reference }}</td>
                    <td class="text-end">{{ number_format($p->amount,2) }}</td>
                    <td>
                      <form action="{{ route('admin.fees.payments.delete',$p->id) }}" method="post" onsubmit="return confirm('Delete payment?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                      </form>
                    </td>
                  </tr>
                @endforeach
                @if($invoice->payments->isEmpty())
                  <tr><td colspan="5" class="text-center text-muted">No payments yet.</td></tr>
                @endif
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-md-5">
          <div class="card card-body">
            <h5 class="mb-3">Record Payment</h5>
            <form method="post" action="{{ route('admin.fees.invoices.payments.store',$invoice->id) }}">
              @csrf
              <div class="mb-2">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ $invoice->due_amount }}" class="form-control" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Paid On</label>
                <input type="date" name="paid_on" class="form-control" value="{{ date('Y-m-d') }}" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Method</label>
                <select name="method" class="form-select">
                  <option value="cash">Cash</option>
                  <option value="bank">Bank</option>
                  <option value="mobile">Mobile</option>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label">Reference</label>
                <input type="text" name="reference" class="form-control" placeholder="Receipt / TxID">
              </div>
              <div class="mb-3">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="2"></textarea>
              </div>
              <button class="btn btn-success w-100">Save Payment</button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</main>
@endsection
