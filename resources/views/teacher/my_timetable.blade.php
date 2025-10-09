@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          @php $selectedClass = ($classes ?? collect())->firstWhere('id', $selectedClassId); @endphp
          <h3 class="mb-0">
            {{ $header_title ?? 'My Class Timetable' }}
            @if($selectedClass)
              <small class="text-muted">— {{ $selectedClass->name }}</small>
            @endif
          </h3>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          {{-- Filter: pick one of teacher's classes --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title">Select Class</h3></div>
            <div class="card-body">
              <form method="GET" action="{{ route('teacher.my-timetable') }}" class="row g-3 align-items-end">
                <div class="col-md-6">
                  <label class="form-label">Class</label>
                  <select name="class_id" class="form-select">
                    <option value="">Select Class</option>
                    @foreach($classes as $c)
                      <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-6 d-flex gap-2 align-items-end">
                  <button type="submit" class="btn btn-primary">Show</button>
                  <a href="{{ route('teacher.my-timetable') }}" class="btn btn-success">Reset</a>

                  {{-- Download: submits current class to teacher download route --}}
                  <button type="submit"
                          class="btn btn-danger"
                          formaction="{{ route('teacher.my-timetable.download') }}"
                          formmethod="GET"
                          formtarget="_blank">
                    Class Schedule Download
                  </button>
                </div>
              </form>
            </div>
          </div>

          {{-- Timetable (only after class selected) --}}
          @if($selectedClassId)
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">Class Timetable</h3>
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
                      @php $items = $byWeek[$week->id] ?? collect(); @endphp

                      @if($items->isEmpty())
                        <tr>
                          <td class="align-middle"><strong>{{ $week->name }}</strong></td>
                          <td colspan="4" class="text-muted">No classes scheduled.</td>
                        </tr>
                      @else
                        @foreach($items as $i => $row)
                          <tr>
                            @if($i === 0)
                              <td class="align-middle" rowspan="{{ $items->count() }}"><strong>{{ $week->name }}</strong></td>
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

          @if($classes->isEmpty())
            <div class="alert alert-warning mt-3">
              You are not assigned to any class yet. Please contact the administrator.
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</main>
@endsection
