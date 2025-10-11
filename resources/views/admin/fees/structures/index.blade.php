@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6"><h3 class="mb-0">Fee Structure</h3></div>
        <div class="col-sm-6 text-sm-end">
          <a href="{{ route('admin.fees.structures.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Structure
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      @include('admin.message')

      <div class="card card-primary card-outline">
        <div class="card-body table-responsive p-0">
          <table class="table table-bordered table-hover mb-0">
            <thead>
              <tr>
                <th>Class</th>
                <th>Academic Year</th>
                <th class="text-end">Annual Fee</th>
                <th class="text-end">Monthly Fee</th>
                <th style="width:160px">Actions</th>
              </tr>
            </thead>
            <tbody>
            @forelse($structures as $s)
              <tr>
                <td>{{ $s->class?->name }}</td>
                <td>{{ $s->academic_year }}</td>
                <td class="text-end">{{ number_format($s->annual_fee ?? $s->monthly_fee*12, 2) }}</td>
                <td class="text-end">{{ number_format($s->monthly_fee, 2) }}</td>
                <td>
                  <a href="{{ route('admin.fees.structures.edit',$s->id) }}" class="btn btn-sm btn-warning">Edit</a>
                  <form action="{{ route('admin.fees.structures.destroy',$s->id) }}" method="post" class="d-inline">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted py-4">No records.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer">
          {{ $structures->links() }}
        </div>
      </div>
    </div>
  </div>
</main>
@endsection
