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

    /* Layout blocks */
    .left  { width:28%; float:left; text-align:center; }
    .right { width:70%; float:right; }
    .img { border:1px solid #e5e5e5; padding:6px; display:inline-block; }
    .tbl { width:100%; border-collapse:collapse; }
    .tbl td { border:1px solid #e5e5e5; padding:8px 10px; vertical-align:top; }
    .label { width:35%; background:#f9f9f9; font-weight:bold; }
    .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; }
    .active { background:#28a745; color:#fff; }
    .inactive { background:#dc3545; color:#fff; }
    .section-title { margin:14px 0 8px; font-weight:bold; }
    .clear { clear: both; }

    /* Parent blocks */
    .parent-block { margin-top:12px; page-break-inside: avoid; }
    .page-break { page-break-before: always; } /* force next page */
  </style>
</head>
<body>

{{-- School Header --}}
@include('pdf.partials.school_header')

<div class="wrap">
  <h2 style="text-align:center; margin-top:0;">Student Profile</h2>

  {{-- Student --}}
  <div class="left">
    <div class="img">
      @if(!empty($studentPhoto))
        <img src="{{ $studentPhoto }}" alt="Student Photo" width="140" height="160" style="object-fit:cover;">
      @else
        <div style="width:140px;height:160px;display:flex;align-items:center;justify-content:center;border:1px dashed #bbb;">
          No Image
        </div>
      @endif
    </div>
  </div>

  <div class="right">
    <table class="tbl">
      <tr><td class="label">First Name</td><td>{{ $user->name }}</td></tr>
      <tr><td class="label">Last Name</td><td>{{ $user->last_name ?? '' }}</td></tr>
      <tr><td class="label">Gender</td><td>{{ $user->gender ?? 'N/A' }}</td></tr>
      <tr><td class="label">Email</td><td>{{ $user->email }}</td></tr>
      <tr><td class="label">Mobile</td><td>{{ $user->mobile_number ?? 'N/A' }}</td></tr>
      <tr><td class="label">Class</td><td>{{ $user->class->name ?? 'N/A' }}</td></tr>
      <tr><td class="label">Address</td><td>{{ $user->address ?? 'N/A' }}</td></tr>
      <tr><td class="label">NID / Birth Certificate (Student)</td><td>{{ $user->nid_or_birthcertificate_no ?? 'N/A' }}</td></tr>
      <tr>
        <td class="label">Status</td>
        <td>
          @if((int)$user->status === 1)
            <span class="badge active">Active</span>
          @else
            <span class="badge inactive">Inactive</span>
          @endif
        </td>
      </tr>
      <tr><td class="label">Role</td><td>Student</td></tr>
    </table>
  </div>

  <div class="clear"></div>

  {{-- Determine parents --}}
  @php
    $motherObj = $mother ?? (
      (isset($parent) && (
        (strtolower((string)($parent->relationship ?? '')) === 'mother') ||
        (strtolower((string)($parent->gender ?? '')) === 'female')
      )) ? $parent : null
    );
    $fatherObj = $father ?? (
      (isset($parent) && (
        (strtolower((string)($parent->relationship ?? '')) === 'father') ||
        (strtolower((string)($parent->gender ?? '')) === 'male')
      )) ? $parent : null
    );

    // Order: prefer mother as first parent; otherwise father first
    $firstParent  = $motherObj ?? $fatherObj;
    $secondParent = ($firstParent && $firstParent === $motherObj) ? $fatherObj : ($firstParent ? $motherObj : null);

    // Photos
    $firstPhoto  = isset($firstParent)  && isset($motherObj) && $firstParent === $motherObj  ? ($motherPhoto ?? $parentPhoto ?? null)
                 : (isset($firstParent)  && isset($fatherObj) && $firstParent === $fatherObj  ? ($fatherPhoto ?? $parentPhoto ?? null) : null);
    $secondPhoto = isset($secondParent) && isset($motherObj) && $secondParent === $motherObj ? ($motherPhoto ?? $parentPhoto ?? null)
                 : (isset($secondParent) && isset($fatherObj) && $secondParent === $fatherObj ? ($fatherPhoto ?? $parentPhoto ?? null) : null);
  @endphp

  @if($firstParent || $secondParent)
    <div class="section-title">Parent Information</div>
  @endif

  {{-- FIRST parent (stays on page 1) --}}
  @if($firstParent)
    <div class="parent-block">
      <div class="left">
        <div class="img">
          @if(!empty($firstPhoto))
            <img src="{{ $firstPhoto }}" alt="Parent Photo" width="120" height="140" style="object-fit:cover;">
          @else
            <div style="width:120px;height:140px;display:flex;align-items:center;justify-content:center;border:1px dashed #bbb;">
              No Image
            </div>
          @endif
        </div>
      </div>
      <div class="right">
        <table class="tbl">
          <tr><td class="label">Name</td><td>{{ trim(($firstParent->name ?? '').' '.($firstParent->last_name ?? '')) ?: 'N/A' }}</td></tr>
          <tr><td class="label">Email</td><td>{{ $firstParent->email ?? 'N/A' }}</td></tr>
          <tr><td class="label">Mobile</td><td>{{ $firstParent->mobile_number ?? 'N/A' }}</td></tr>
          <tr><td class="label">Address</td><td>{{ $firstParent->address ?? 'N/A' }}</td></tr>
          <tr><td class="label">NID</td><td>{{ $firstParent->nid_or_birthcertificate_no ?? 'N/A' }}</td></tr>
          <tr>
            <td class="label">Status</td>
            <td>
              @if((int)($firstParent->status ?? 0) === 1)
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
    </div>
  @endif

  {{-- SECOND parent (always starts on new page) --}}
  @if($secondParent)
    <div class="page-break"></div>
@include('pdf.partials.school_header')
    <div class="parent-block">
      <div class="left">
        <div class="img">
          @if(!empty($secondPhoto))
            <img src="{{ $secondPhoto }}" alt="Parent Photo" width="120" height="140" style="object-fit:cover;">
          @else
            <div style="width:120px;height:140px;display:flex;align-items:center;justify-content:center;border:1px dashed #bbb;">
              No Image
            </div>
          @endif
        </div>
      </div>
      <div class="right">
        <table class="tbl">
          <tr><td class="label">Name</td><td>{{ trim(($secondParent->name ?? '').' '.($secondParent->last_name ?? '')) ?: 'N/A' }}</td></tr>
          <tr><td class="label">Email</td><td>{{ $secondParent->email ?? 'N/A' }}</td></tr>
          <tr><td class="label">Mobile</td><td>{{ $secondParent->mobile_number ?? 'N/A' }}</td></tr>
          <tr><td class="label">Address</td><td>{{ $secondParent->address ?? 'N/A' }}</td></tr>
          <tr><td class="label">NID</td><td>{{ $secondParent->nid_or_birthcertificate_no ?? 'N/A' }}</td></tr>
          <tr>
            <td class="label">Status</td>
            <td>
              @if((int)($secondParent->status ?? 0) === 1)
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
    </div>
  @endif

</div>
</body>
</html>
