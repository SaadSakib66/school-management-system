{{-- resources/views/pdf/partials/school_header.blade.php --}}
@php
    // Make website clickable even if scheme is missing
    $ws = $schoolPrint['website'] ?? null;
    if ($ws && !preg_match('#^https?://#i', $ws)) {
        $ws = 'http://' . $ws;
    }
@endphp

<div style="text-align:center; margin:0 0 10px 0;">
    {{-- Logo (round, no border/outline) --}}
    @if(!empty($schoolLogoSrc))
        <img src="{{ $schoolLogoSrc }}"
             alt="School Logo"
             width="55" height="55"
             style="display:block;margin:3px auto 8px auto;object-fit:cover;border-radius:50%;outline:none;border:none;">
    @else
        <div style="width:96px;height:96px;display:inline-flex;align-items:center;justify-content:center;margin:0 auto 8px auto;border-radius:50%;background:#f2f2f2;color:#888;font-size:11px;">
            No Logo
        </div>
    @endif

    {{-- School Name --}}
    <div style="font-size:15px;font-weight:bold;color:#222;line-height:1.25;">
        {{ $schoolPrint['name'] ?? ($school->name ?? 'School') }}
    </div>

    {{-- EIIN (correct key) --}}
    @if(!empty($schoolPrint['eiin']))
        <div style="font-size:10px;color:#444;line-height:1.4;margin-top:2px;">
            EIIN: {{ $schoolPrint['eiin'] }}
        </div>
    @endif

    {{-- Address --}}
    @if(!empty($schoolPrint['address']))
        <div style="font-size:10px;color:#444;line-height:1.4;margin-top:2px;">
            {{ $schoolPrint['address'] }}
        </div>
    @endif

    {{-- Website (clickable) --}}
    @if(!empty($schoolPrint['website']))
        <div style="font-size:10px;line-height:1.4;margin-top:2px;">
            <a href="{{ $ws }}" style="color:#1f4e79; text-decoration:none;">
                {{ $schoolPrint['website'] }}
            </a>
        </div>
    @endif
</div>

