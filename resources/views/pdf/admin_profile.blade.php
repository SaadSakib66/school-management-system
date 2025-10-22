<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* ↓ Control the page margins Dompdf uses */
        @page {
            margin: 8px 18px 16px 18px; /* top, right, bottom, left */
        }

        /* ↓ Remove browser default margins that Dompdf respects */
        html, body { margin: 0; padding: 0; }

        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 12px; }

        .wrap {padding: 16px; }
        .left { width: 28%; float: left; text-align: center; }
        .right { width: 70%; float: right; }
        .img { border: 1px solid #e5e5e5; padding: 6px; display: inline-block; }
        .tbl { width: 100%; border-collapse: collapse; }
        .tbl td { border: 1px solid #e5e5e5; padding: 8px 10px; vertical-align: top; }
        .label { width: 35%; background: #f9f9f9; font-weight: bold; }
        .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size: 11px; }
        .active { background:#28a745; color:#fff; }
        .inactive { background:#dc3545; color:#fff; }
        .clear { clear: both; }
        h2 { margin: 10px 0 14px; }
    </style>
</head>
<body>

{{-- ✅ Universal School Header --}}
@include('pdf.partials.school_header')

<div class="wrap">
    <h2 style="text-align:center; margin-top:0;">Admin Profile</h2>

    {{-- Admin --}}
    <div class="left">
        <div class="img">
            @if($photoSrc)
                <img src="{{ $photoSrc }}" alt="Profile Photo" width="140" height="160" style="object-fit:cover;">
            @else
                <div style="width:140px;height:160px;display:flex;align-items:center;justify-content:center;border:1px dashed #bbb;">
                    No Image
                </div>
            @endif
        </div>
    </div>

    <div class="right">
        <table class="tbl">
            <tr>
                <td class="label">First Name</td>
                <td>{{ explode(' ', $user->name)[0] ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Last Name</td>
                <td>{{ $user->last_name ?? 'N/A'}}</td>
            </tr>
            <tr>
                <td class="label">Gender</td>
                <td>{{ $user->gender ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td>{{ $user->email }}</td>
            </tr>
            <tr>
                <td class="label">Mobile</td>
                <td>{{ $user->mobile_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Address</td>
                <td>{{ $user->address ?? 'N/A' }}</td>
            </tr>
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
            <tr>
                <td class="label">Role</td>
                <td>{{ ucfirst($user->role) }}</td>
            </tr>
        </table>
    </div>

    <div class="clear"></div>
</div>
</body>
</html>
