@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Exam Schedule</h3></div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          @include('admin.message')

          {{-- Search / Filter --}}
          <div class="card card-primary card-outline mb-4">
            <div class="card-header"><h3 class="card-title">Search Exam Schedule</h3></div>
            <div class="card-body">
              <form method="GET" action="{{ route('admin.exam-schedule.list') }}" class="row g-3 align-items-end">

                <div class="col-md-4">
                  <label class="form-label">Exam</label>
                  <select name="exam_id" id="exam_id" class="form-select">
                    <option value="">Select Exam</option>
                    @foreach($exams as $e)
                      <option value="{{ $e->id }}" {{ $selectedExamId == $e->id ? 'selected' : '' }}>
                        {{ $e->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Class</label>
                  <select name="class_id" id="class_id" class="form-select">
                    <option value="">Select Class</option>
                    @foreach($classes as $c)
                      <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-4 d-flex gap-2 align-items-end">
                  <button type="submit" class="btn btn-primary">Search</button>
                  <a href="{{ route('admin.exam-schedule.list') }}" class="btn btn-success">Reset</a>

                  {{-- Open PDF inline in a new tab --}}
                  <button type="submit"
                          class="btn btn-danger"
                          formaction="{{ route('admin.exam-schedule.download') }}"
                          formmethod="GET"
                          formtarget="_blank"
                          onclick="return validateExamClass();">
                    Exam Schedule Download
                  </button>
                </div>

              </form>
            </div>
          </div>

          {{-- Schedule table only when both selected --}}
          @if($selectedExamId && $selectedClassId)
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title">Exam Schedule</h3>
            </div>

            <div class="card-body p-0">
              <form method="POST" action="{{ route('admin.exam-schedule.save') }}">
                @csrf
                <input type="hidden" name="exam_id"  value="{{ $selectedExamId }}">
                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">

                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th>Subject Name</th>
                      <th style="width:15%">Exam Date</th>
                      <th style="width:12%">Start Time</th>
                      <th style="width:12%">End Time</th>
                      <th style="width:12%">Room Number</th>
                      <th style="width:12%">Full Marks</th>
                      <th style="width:12%">Passing Marks</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($subjects as $s)
                      @php $row = $existing[$s->id] ?? null; @endphp
                      <tr>
                        <td class="align-middle"><strong>{{ $s->name }}</strong></td>
                        <td>
                          <input type="text" class="form-control js-exam-date"
                                 name="exam_date[{{ $s->id }}]"
                                 placeholder="dd-mm-yyyy"
                                 value="{{ old("exam_date.$s->id", $row?->exam_date ? \Carbon\Carbon::parse($row->exam_date)->format('d-m-Y') : '') }}">
                        </td>
                        <td>
                          <input type="text" class="form-control js-time"
                                 name="start_time[{{ $s->id }}]"
                                 placeholder="hh:mm AM"
                                 value="{{ old("start_time.$s->id",
                                     $row?->start_time
                                         ? \Carbon\Carbon::createFromFormat('H:i:s',$row->start_time)->format('h:i A')
                                         : ''
                                 ) }}">
                        </td>
                        <td>
                          <input type="text" class="form-control js-time"
                                 name="end_time[{{ $s->id }}]"
                                 placeholder="hh:mm AM"
                                 value="{{ old("end_time.$s->id",
                                     $row?->end_time
                                         ? \Carbon\Carbon::createFromFormat('H:i:s',$row->end_time)->format('h:i A')
                                         : ''
                                 ) }}">
                        </td>
                        <td>
                          <input type="text" class="form-control"
                                 name="room_number[{{ $s->id }}]"
                                 value="{{ old("room_number.$s->id", $row?->room_number) }}"
                                 placeholder="e.g. 101">
                        </td>
                        <td>
                          <input type="number" class="form-control"
                                 name="full_mark[{{ $s->id }}]"
                                 min="0" step="1"
                                 value="{{ old("full_mark.$s->id", $row?->full_mark) }}">
                        </td>
                        <td>
                          <input type="number" class="form-control"
                                 name="passing_mark[{{ $s->id }}]"
                                 min="0" step="1"
                                 value="{{ old("passing_mark.$s->id", $row?->passing_mark) }}">
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>

                <div class="p-3 text-center">
                  <button type="submit" class="btn btn-primary">Submit</button>
                </div>
              </form>
            </div>
          </div>
          @elseif(request()->has('exam_id') || request()->has('class_id'))
            <div class="alert alert-info">
              Select both an <strong>Exam</strong> and a <strong>Class</strong>, then click <strong>Search</strong>.
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@push('scripts')
  {{-- Flatpickr CSS & JS (CDN) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <script>
    // Date picker: dd-mm-yyyy
    flatpickr(".js-exam-date", {
      dateFormat: "d-m-Y",
      allowInput: true,
      disableMobile: true
    });

    // Time pickers in 12-hour format with AM/PM
    flatpickr(".js-time", {
      enableTime: true,
      noCalendar: true,
      dateFormat: "h:i K",   // e.g., 02:00 PM
      time_24hr: false,
      minuteIncrement: 5,
      allowInput: true,
      onClose: (selectedDates, dateStr, instance) => {
        if (dateStr) instance.input.value = dateStr.toUpperCase();
      }
    });

    function validateExamClass() {
      const exam  = document.getElementById('exam_id')?.value;
      const klass = document.getElementById('class_id')?.value;
      if (!exam || !klass) {
        alert('Please select both an Exam and a Class.');
        return false;
      }
      return true;
    }
  </script>
@endpush
