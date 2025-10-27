<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Homework Report</title>
  <style>
    /* Page & base */
    @page { margin: 8px 14px 14px 14px; } /* slightly smaller side margins */
    html, body { margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }

    /* Container: NO padding here (to avoid width overflow in DomPDF) */
    .container {
      width: 100%;
      border: 1px solid #999;
      box-sizing: border-box;
      margin-top: 6px;
    }
    /* Put padding in an inner div instead */
    .inner {
      padding: 8px 10px;
    }

    .heading { margin: 0 0 8px 0; }

    /* Table */
    table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;         /* prevent width auto-expansion */
    }
    th, td {
      border: 1px solid #444;
      padding: 5px;
      vertical-align: top;
      word-wrap: break-word;
      overflow-wrap: break-word;   /* break long words/URLs */
    }
    th { background: #eee; }

    /* Keep headers repeating & avoid mid-row breaks */
    thead { display: table-header-group; }
    tfoot { display: table-row-group; }
    tr { page-break-inside: avoid; }

    /* Optional: slight column width guidance (percentages must sum <=100) */
    th.col-idx      { width: 4%; }
    th.col-student  { width: 16%; }
    th.col-stuid    { width: 8%; }
    th.col-class    { width: 10%; }
    th.col-subject  { width: 12%; }
    th.col-hwdate   { width: 9%; }
    th.col-subdate  { width: 10%; }
    th.col-desc     { width: 15%; }
    th.col-subtext  { width: 12%; }
    th.col-subat    { width: 8%; }
  </style>
</head>
<body>
  {{-- ✅ Universal School Header --}}
  @include('pdf.partials.school_header')

  <div class="container">
    <div class="inner">
      <h3 class="heading">Homework Report</h3>

      <div style="margin-bottom:8px;">
        @if($class)<strong>Class:</strong> {{ $class->name }} @endif
        @if($subject)&nbsp; | &nbsp;<strong>Subject:</strong> {{ $subject->name }} @endif
        @if($student)&nbsp; | &nbsp;<strong>Student:</strong> {{ trim(($student->name ?? '').' '.($student->last_name ?? '')) }} @endif
        &nbsp; | &nbsp;<strong>Generated:</strong> {{ \Carbon\Carbon::parse($generated_at)->format('d-m-Y H:i') }}
      </div>

      <table>
        <thead>
          <tr>
            <th class="col-idx">#</th>
            <th class="col-student">Student</th>
            <th class="col-stuid">Student ID</th>
            <th class="col-class">Class</th>
            <th class="col-subject">Subject</th>
            <th class="col-hwdate">Homework Date</th>
            <th class="col-subdate">Submission Date</th>
            <th class="col-desc">Description</th>
            <th class="col-subtext">Submitted Text</th>
            <th class="col-subat">Submitted At</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $i => $sub)
            @php
              $hw = $sub->homework;
              $stu = $sub->student;
              $submittedDate = $sub->submitted_at ?? $sub->created_at;
            @endphp
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ trim(($stu->name ?? '').' '.($stu->last_name ?? '')) }}</td>
              <td>{{ $stu->admission_number ?? '' }}</td>
              <td>{{ $hw->class->name ?? '' }}</td>
              <td>{{ $hw->subject->name ?? '' }}</td>
              <td>{{ optional($hw->homework_date)->format('d-m-Y') }}</td>
              <td>{{ optional($hw->submission_date)->format('d-m-Y') }}</td>
              <td>{{ $hw->description_plain }}</td>
              <td>{{ $sub->text_plain }}</td>
              <td>{{ optional($submittedDate)->format('d-m-Y') }}</td>
            </tr>
          @empty
            <tr><td colspan="10" style="text-align:center;">No data</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
