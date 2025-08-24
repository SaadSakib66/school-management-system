@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">My Attendance</h3></div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          {{-- Filter --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title">Filter</h3></div>
            <div class="card-body">
              <form method="GET" action="{{ route('student.attendance.month') }}" class="row g-3 align-items-end">
                <div class="col-md-6">
                  <label class="form-label">Month</label>
                  <div class="d-flex gap-2">
                    <select name="m" class="form-select" style="max-width: 220px;">
                      @foreach([1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'] as $mNum => $mName)
                        <option value="{{ $mNum }}" {{ $selMonth == $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                      @endforeach
                    </select>
                    <select name="y" class="form-select" style="max-width: 160px;">
                      @foreach($years as $y)
                        <option value="{{ $y }}" {{ $selYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <button type="submit" class="btn btn-primary">Search</button>
                  <a href="{{ route('student.attendance.month') }}" class="btn btn-success">Reset</a>
                </div>
              </form>
            </div>
          </div>

          {{-- Results --}}
          <div class="card">
            <div class="card-header">
              <strong>Attendance for {{ $monthLabel }}</strong>
            </div>
            <div class="card-body p-0">
              <table class="table table-striped mb-0 align-middle">
                <thead>
                  <tr>
                    <th style="width:160px">Date</th>
                    <th style="width:120px">Day</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($days as $d)
                    <tr>
                      <td>{{ $d['date_fmt'] }}</td> {{-- dd-mm-yyyy --}}
                      <td>{{ $d['dow'] }}</td>
                      <td>
                        @if($d['code'])
                          <span class="badge {{ $d['badge'] }}">{{ $d['text'] }}</span>
                        @else
                          <span class="text-muted">Not Taken</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="3" class="p-3">
                      <span class="badge bg-success me-2">Present: {{ $count['present'] }}</span>
                      <span class="badge bg-warning me-2">Late: {{ $count['late'] }}</span>
                      <span class="badge bg-info me-2">Half Day: {{ $count['halfday'] }}</span>
                      <span class="badge bg-danger">Absent: {{ $count['absent'] }}</span>
                    </th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</main>
@endsection
