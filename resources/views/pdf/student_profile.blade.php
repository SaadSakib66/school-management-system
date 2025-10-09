<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 12px; }
    .wrap { border:1px solid #ddd; padding:16px; }
    .left { width:28%; float:left; text-align:center; }
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
  </style>
</head>
<body>
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

  {{-- Parent --}}
  <div class="section-title">Parent Information</div>

  <div class="left">
    <div class="img">
      @if(!empty($parentPhoto))
        <img src="{{ $parentPhoto }}" alt="Parent Photo" width="120" height="140" style="object-fit:cover;">
      @else
        <div style="width:120px;height:140px;display:flex;align-items:center;justify-content:center;border:1px dashed #bbb;">
          No Image
        </div>
      @endif
    </div>
  </div>

  <div class="right">
    <table class="tbl">
      <tr><td class="label">Name</td><td>{{ $parent ? trim(($parent->name ?? '').' '.($parent->last_name ?? '')) : 'N/A' }}</td></tr>
      <tr><td class="label">Email</td><td>{{ $parent->email ?? 'N/A' }}</td></tr>
      <tr><td class="label">Mobile</td><td>{{ $parent->mobile_number ?? 'N/A' }}</td></tr>
      <tr><td class="label">Address</td><td>{{ $parent->address ?? 'N/A' }}</td></tr>
      <tr>
        <td class="label">Status</td>
        <td>
          @if($parent && (int)$parent->status === 1)
            <span class="badge active">Active</span>
          @elseif($parent)
            <span class="badge inactive">Inactive</span>
          @else
            N/A
          @endif
        </td>
      </tr>
      <tr><td class="label">Role</td><td>{{ $parent ? 'Parent' : 'N/A' }}</td></tr>
    </table>
  </div>

  <div class="clear"></div>

</div>
</body>
</html>
