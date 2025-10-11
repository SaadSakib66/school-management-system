@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0">Invoices</h3></div>
        <div class="col-sm-6 text-sm-end">
          <a href="{{ route('admin.fees.invoices.generate.form') }}" class="btn btn-primary">
            <i class="bi bi-gear"></i> Generate Invoices
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      @include('admin.message')

      <form method="get" class="card card-body mb-3">
        <div class="row g-2">
          {{-- Class --}}
          <div class="col-md-2">
            <select name="class_id" class="form-select">
              <option value="">-- Class --</option>
              @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected(request('class_id')==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- Month (names) --}}
          <div class="col-md-2">
            <select name="month" class="form-select">
              <option value="">-- Month --</option>
              @foreach($months as $num => $label)
                <option value="{{ $num }}" @selected((int)request('month') === (int)$num)>{{ $label }}</option>
              @endforeach
            </select>
          </div>

          {{-- Academic Year --}}
          <div class="col-md-2">
            <input type="text" name="year" value="{{ request('year') }}" class="form-control" placeholder="Academic Year">
          </div>

          {{-- Status --}}
          <div class="col-md-2">
            <select name="status" class="form-select">
              <option value="">-- Status --</option>
              @foreach(['unpaid','partial','paid'] as $st)
                <option value="{{ $st }}" @selected(request('status')==$st)>{{ ucfirst($st) }}</option>
              @endforeach
            </select>
          </div>

          {{-- Student Name --}}
          <div class="col-md-2">
            <input type="text" name="student" value="{{ request('student') }}" class="form-control" placeholder="Student name">
          </div>

          {{-- Email --}}
          <div class="col-md-2">
            <input type="email" name="email" value="{{ request('email') }}" class="form-control" placeholder="Email">
          </div>
        </div>

        <div class="row mt-2 g-2">
          <div class="col-md-2 ms-auto">
            <button class="btn btn-secondary w-100">Filter</button>
          </div>
          <div class="col-md-2">
            <a href="{{ route('admin.fees.invoices.index') }}" class="btn btn-outline-dark w-100">Reset</a>
          </div>
        </div>
      </form>

      <div class="card card-primary card-outline">
        <div class="card-body table-responsive p-0">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>Student</th>
                <th>Email</th>
                <th>Class</th>
                <th>Year/Month</th>
                <th class="text-end">Amount</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Due</th>
                <th>Status</th>
                <th style="width:120px"></th>
              </tr>
            </thead>
            <tbody>
            @forelse($invoices as $inv)
              <tr>
                <td>{{ trim(($inv->student?->name ?? '') . ' ' . ($inv->student?->last_name ?? '')) }}</td>
                <td>{{ $inv->student?->email }}</td>
                <td>{{ $inv->class?->name }}</td>
                <td>
                  {{ $inv->academic_year }} /
                  {{ $months[$inv->month] ?? $inv->month }}
                </td>
                <td class="text-end">{{ number_format($inv->amount - $inv->discount + $inv->fine, 2) }}</td>
                <td class="text-end">{{ number_format($inv->paid_amount, 2) }}</td>
                <td class="text-end">{{ number_format($inv->due_amount, 2) }}</td>
                <td>
                  <span class="badge bg-{{ $inv->status=='paid' ? 'success' : ($inv->status=='partial' ? 'warning' : 'danger') }}">
                    {{ ucfirst($inv->status) }}
                  </span>
                </td>
                <td class="text-nowrap">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.fees.invoices.show', $inv->id) }}">View</a>
                  <a class="btn btn-sm btn-outline-danger" href="{{ route('admin.fees.invoices.pdf', $inv->id) }}" target="_blank" title="Invoice PDF">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">No invoices found.</td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          {{ $invoices->appends(request()->query())->links() }}
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
