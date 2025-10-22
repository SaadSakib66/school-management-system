<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    /* Page + base styles */
    @page { margin: 8px 18px 16px 18px; }
    html, body { margin: 0; padding: 0; }

    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 12px; }
    .wrap { padding:16px; }
    .left { width:28%; float:left; text-align:center; }
    .right { width:70%; float:right; }
    .img { border:1px solid #e5e5e5; padding:6px; display:inline-block; }
    .tbl { width:100%; border-collapse:collapse; }
    .tbl td, .tbl th { border:1px solid #e5e5e5; padding:8px 10px; vertical-align:top; }
    .label { width:35%; background:#f9f9f9; font-weight:bold; }
    .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; }
    .active { background:#28a745; color:#fff; }
    .inactive { background:#dc3545; color:#fff; }
    .section-title { margin:14px 0 8px; font-weight:bold; }
    .clear { clear: both; }
  </style>
</head>
<body>
{{-- School Header --}}
@include('pdf.partials.school_header')
<div class="wrap">
  <h2 style="text-align:center; margin-top:0;">Parent Profile</h2>

  {{-- Parent --}}
  <div class="left">
    <div class="img">
      @if(!empty($parentPhoto))
        <img src="{{ $parentPhoto }}" alt="Parent Photo" width="140" height="160" style="object-fit:cover;">
      @else
        <div style="width:140px;height:160px;display:flex;align-items:center;justify-content:center;border:1px dashed #bbb;">
          No Image
        </div>
      @endif
    </div>
  </div>

  <div class="right">
    <table class="tbl">
      <tr><td class="label">First Name</td><td>{{ $parent->name }}</td></tr>
      <tr><td class="label">Last Name</td><td>{{ $parent->last_name ?? '' }}</td></tr>
      <tr><td class="label">Gender</td><td>{{ $parent->gender ?? 'N/A' }}</td></tr>
      <tr><td class="label">Email</td><td>{{ $parent->email }}</td></tr>
      <tr><td class="label">Mobile</td><td>{{ $parent->mobile_number ?? 'N/A' }}</td></tr>
      <tr><td class="label">Occupation</td><td>{{ $parent->occupation ?? 'N/A' }}</td></tr>
      <tr><td class="label">Address</td><td>{{ $parent->address ?? 'N/A' }}</td></tr>
      {{-- NEW: NID / Birth Certificate --}}
      <tr><td class="label">NID / Birth Certificate</td><td>{{ $parent->nid_or_birthcertificate_no ?? 'N/A' }}</td></tr>
      <tr>
        <td class="label">Status</td>
        <td>
          @if((int)$parent->status === 1)
            <span class="badge active">Active</span>
          @else
            <span class="badge inactive">Inactive</span>
          @endif
        </td>
      </tr>
      <tr><td class="label">Role</td><td>Parent</td></tr>
    </table>
  </div>

  <div class="clear"></div>

  {{-- Children --}}
  <div class="section-title">Children</div>
  <table class="tbl">
    <thead>
      <tr>
        <th style="width:5%;">#</th>
        <th style="width:25%;">Name</th>
        <th style="width:10%;">Gender</th>
        <th style="width:15%;">Class</th>
        <th style="width:20%;">Email</th>
        <th style="width:15%;">Mobile</th>
        <th style="width:10%;">Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($children as $i => $s)
        <tr>
          <td>{{ $i+1 }}</td>
          <td>{{ trim(($s->name ?? '').' '.($s->last_name ?? '')) }}</td>
          <td>{{ $s->gender ?? 'N/A' }}</td>
          <td>{{ $s->class->name ?? 'N/A' }}</td>
          <td>{{ $s->email }}</td>
          <td>{{ $s->mobile_number ?? 'N/A' }}</td>
          <td>
            @if((int)$s->status === 1)
              <span class="badge active">Active</span>
            @else
              <span class="badge inactive">Inactive</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;">No children assigned.</td></tr>
      @endforelse
    </tbody>
  </table>

</div>
</body>
</html>
