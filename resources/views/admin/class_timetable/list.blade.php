@extends('admin.layout.layout')
@section('content')

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Class Timetable</h3></div>
        <div class="col-sm-6"></div>
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
            <div class="card-header"><h3 class="card-title">Search Class Timetable</h3></div>
            <div class="card-body">
              <form method="GET" action="{{ route('admin.class-timetable.list') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                  <label class="form-label">Class Name</label>
                  <select name="class_id" id="class_id" class="form-select">
                    <option value="">Select Class</option>
                    @foreach($getClass as $c)
                      <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Subject Name</label>
                  <select name="subject_id" id="subject_id" class="form-select">
                    <option value="">Select Subject</option>
                    @foreach($getSubject as $s)
                      <option value="{{ $s->id }}" {{ $selectedSubjectId == $s->id ? 'selected' : '' }}>
                        {{ $s->name }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <div class="col-md-4 d-flex gap-2 align-items-end">
                  {{-- regular search (submits to list route) --}}
                  <button type="submit" class="btn btn-primary">Search</button>

                  <a href="{{ route('admin.class-timetable.list') }}" class="btn btn-success">Reset</a>

                  {{-- Class Schedule Download: submits current class/subject to download route --}}
                  <button type="submit"
                          class="btn btn-danger"
                          formaction="{{ route('admin.class-timetable.download') }}"
                          formmethod="GET"
                          formtarget="_blank">
                    Class Schedule Download
                  </button>
                </div>
              </form>
            </div>
          </div>

          {{-- Timetable --}}
          @if($selectedClassId && $selectedSubjectId)
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">Class Timetable</h3>
              </div>

              <div class="card-body p-0">
                <form method="POST" action="{{ route('admin.class-timetable.save') }}">
                  @csrf
                  <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                  <input type="hidden" name="subject_id" value="{{ $selectedSubjectId }}">

                  <table class="table table-striped mb-0">
                    <thead>
                      <tr>
                        <th style="width:25%">Week</th>
                        <th style="width:25%">Start Time</th>
                        <th style="width:25%">End Time</th>
                        <th style="width:25%">Room Number</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($weeks as $week)
                        @php $row = $existing[$week->id] ?? null; @endphp
                        <tr>
                          <td class="align-middle"><strong>{{ $week->name }}</strong></td>
                          <td>
                            <input type="time" class="form-control" name="start_time[{{ $week->id }}]"
                                   value="{{ $row?->start_time }}">
                          </td>
                          <td>
                            <input type="time" class="form-control" name="end_time[{{ $week->id }}]"
                                   value="{{ $row?->end_time }}">
                          </td>
                          <td>
                            <input type="text" class="form-control" name="room_number[{{ $week->id }}]"
                                   value="{{ $row?->room_number }}" placeholder="e.g. 204">
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>

                  <div class="p-3 text-center">
                    <button type="submit" class="btn btn-primary">Save Timetable</button>
                  </div>
                </form>
              </div>
            </div>
          @elseif(request()->has('class_id') || request()->has('subject_id'))
            {{-- Optional gentle hint after a Search with only one filter set --}}
            <div class="alert alert-info">
              Select both a Class and a Subject, then click <strong>Search</strong> to view/edit the timetable.
            </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</main>

{{-- Simple AJAX to reload subjects when class changes --}}
@push('scripts')
<script>
(function () {
  const classSel   = document.getElementById('class_id');
  const subjectSel = document.getElementById('subject_id');

  // Build from named route with dummy id 0, then replace it
  const SUBJECTS_ROUTE_BASE = @json(route('admin.class-timetable.subjects', ['class_id' => 0]));

  classSel?.addEventListener('change', async function () {
    const classId = this.value;
    subjectSel.innerHTML = '<option value="">Loading...</option>';

    if (!classId) {
      subjectSel.innerHTML = '<option value="">Select Subject</option>';
      return;
    }

    const url = SUBJECTS_ROUTE_BASE.replace(/\/0$/, '/' + encodeURIComponent(classId));

    try {
      const res = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest' // helps Laravel return JSON on auth errors
        },
        credentials: 'same-origin'
      });

      if (!res.ok) {
        // Attempt to read JSON error message, fallback to text
        let msg = 'Failed to load';
        try {
          const data = await res.json();
          if (data?.message) msg = data.message;
        } catch {
          const text = await res.text();
          if (text) msg = text.substring(0, 300);
        }
        console.error('Subjects fetch failed:', res.status, msg);
        subjectSel.innerHTML = `<option value="">${msg}</option>`;
        return;
      }

      const data = await res.json();
      subjectSel.innerHTML = '<option value="">Select Subject</option>';
      data.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        subjectSel.appendChild(opt);
      });
    } catch (e) {
      console.error('Subjects fetch exception:', e);
      subjectSel.innerHTML = '<option value="">Failed to load</option>';
    }
  });
})();
</script>
@endpush

@endsection
