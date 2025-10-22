<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    /* Tight page margins for Dompdf */
    @page { margin: 8px 18px 16px 18px; }
    html, body { margin: 0; padding: 0; }

    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 12px; }

    h2 { text-align: center; margin-bottom: 20px; }
    h3 { margin: 18px 0 8px; background:#f0f0f0; padding:6px 10px; }

    /* Container to give the table 'margins' L/R while header stays full width */
    .group { padding: 0 16px 12px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    th, td { border: 1px solid #666; padding: 6px; text-align: left; }
    th { background: #eee; }

    .status-active { color: green; font-weight:bold; }
    .status-inactive { color: red; font-weight:bold; }
  </style>
</head>
<body>
  {{-- header stays as-is --}}
  @include('pdf.partials.school_header')

  <h2>Assigned Subjects Report</h2>

  @php
    $grouped = $records->groupBy('class_name');
  @endphp

  @forelse($grouped as $className => $rows)
    <h3>Class: {{ $className ?? 'N/A' }}</h3>

    {{-- 👇 padding gives left/right spacing for the table --}}
    <div class="group">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Created By</th>
            <th>Created Date</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $i => $r)
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ $r->subject_name }}</td>
              <td>
                @if($r->status==1)
                  <span class="status-active">Active</span>
                @else
                  <span class="status-inactive">Inactive</span>
                @endif
              </td>
              <td>{{ $r->created_by_name ?? 'N/A' }}</td>
              <td>{{ \Carbon\Carbon::parse($r->created_at)->format('d M Y') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @empty
    <p style="text-align:center;">No records found.</p>
  @endforelse
</body>
</html>
