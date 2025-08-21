@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">{{ $header_title ?? 'My Class Timetable' }} @if(!empty($class))<small class="text-muted">— {{ $class->name }}</small>@endif</h3></div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          @if(!$classId)
            <div class="alert alert-warning">
              You are not assigned to any class yet. Please contact the administrator.
            </div>
          @else
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">Weekly Timetable</h3>
              </div>

              <div class="card-body p-0">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th style="width:20%">Day</th>
                      <th style="width:30%">Subject</th>
                      <th style="width:20%">Start</th>
                      <th style="width:20%">End</th>
                      <th style="width:10%">Room</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($weeks as $week)
                      @php
                        $items = $byWeek[$week->id] ?? collect();
                      @endphp

                      @if($items->isEmpty())
                        <tr>
                          <td class="align-middle"><strong>{{ $week->name }}</strong></td>
                          <td colspan="4" class="text-muted">No classes scheduled.</td>
                        </tr>
                      @else
                        @foreach($items as $i => $row)
                          <tr>
                            @if($i === 0)
                              <td class="align-middle" rowspan="{{ $items->count() }}">
                                <strong>{{ $week->name }}</strong>
                              </td>
                            @endif
                            <td>{{ $row->subject?->name ?? '—' }}</td>
                            <td>{{ $row->start_time ? \Carbon\Carbon::createFromFormat('H:i:s',$row->start_time)->format('h:i A') : '—' }}</td>
                            <td>{{ $row->end_time ? \Carbon\Carbon::createFromFormat('H:i:s',$row->end_time)->format('h:i A') : '—' }}</td>
                            <td>{{ $row->room_number ?? '—' }}</td>
                          </tr>
                        @endforeach
                      @endif
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</main>
@endsection
