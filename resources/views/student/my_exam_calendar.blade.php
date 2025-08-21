@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h3 class="mb-1">{{ $header_title ?? 'My Exam Calendar' }}</h3>
          @if(!empty($studentClassName))
            <small class="text-muted">Class: {{ $studentClassName }}</small>
          @endif
        </div>
        <div class="col-md-4">
          <form method="GET" action="{{ route('student.my-exam-calendar') }}" class="d-flex gap-2 justify-content-md-end mt-2 mt-md-0">
            <select name="exam_id" class="form-select" onchange="this.form.submit()">
              <option value="">-- Select Exam --</option>
              @foreach($exams as $exam)
                <option value="{{ $exam->id }}" {{ (int)$selectedExamId === (int)$exam->id ? 'selected' : '' }}>
                  {{ $exam->name }}
                </option>
              @endforeach
            </select>
            @if(request()->has('exam_id'))
              <a class="btn btn-outline-secondary" href="{{ route('student.my-exam-calendar') }}">Reset</a>
            @endif
          </form>
        </div>
      </div>
      @if(session('info'))
        <div class="alert alert-warning mt-3">{{ session('info') }}</div>
      @endif
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <div class="row justify-content-center">
        <div class="col-md-10">
          <div id="exam-calendar"></div>
        </div>
      </div>
    </div>
  </div>
</main>
@endsection

@push('styles')
  <!-- FullCalendar CSS (CDN) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
  <style>
    #exam-calendar{
      min-height: 650px;
      background: #fff;
      padding: 1rem;
      border-radius: .5rem;
    }
  </style>
@endpush

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const el = document.getElementById('exam-calendar');
      if (!el) return;

      const events = @json($events ?? []);
      console.log('Exam events sent to FullCalendar:', events); // <-- verify here

      const calendar = new FullCalendar.Calendar(el, {
        initialDate: '{{ now()->toDateString() }}',
        initialView: 'dayGridMonth',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        height: 'auto',
        navLinks: true,
        editable: false,
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        events: events
      });

      calendar.render();
    });
  </script>
@endpush
