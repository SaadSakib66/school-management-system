@extends('admin.layout.layout')

@section('content')
<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h3 class="mb-0">{{ $header_title ?? 'My Calendar' }}</h3>
          @if(!empty($class))
            <small class="text-muted d-block mt-1">Class: {{ $class->name }}</small>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">

      @if(empty($class))
        <div class="alert alert-warning">
          You are not assigned to any class yet. The calendar will appear once a class is assigned.
        </div>
      @endif

      <div class="row justify-content-center">
        <div class="col-md-8">
          <div id="calendar"></div>
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
    #calendar{
      min-height: 650px;
      background: #fff;
      padding: 1rem;
      border-radius: .5rem;
    }
  </style>
@endpush

@push('scripts')
  <!-- FullCalendar JS (CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const calendarEl = document.getElementById('calendar');
      if (!calendarEl) return;

      // Recurring weekly events from controller
      const events = @json($events ?? []);

      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialDate: '{{ now()->toDateString() }}',
        initialView: 'timeGridWeek', // time grid shows class times nicely
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        height: 'auto',
        slotMinTime: '07:00:00',
        slotMaxTime: '20:00:00',
        navLinks: true,
        editable: false,
        events: events
      });

      calendar.render();
    });
  </script>
@endpush
