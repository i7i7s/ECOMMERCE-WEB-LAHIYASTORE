@props(['mockup'])

@php
    $id = 'pc'.substr(md5(uniqid('', true)), 0, 8);
    $w = $mockup['width'];
    $h = $mockup['height'];
@endphp

{{-- Camera area of the phone seen through the case cutouts (App\Support\PhoneMockups), drawn in millimetres. --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" aria-hidden="true" {{ $attributes->class('pointer-events-none absolute inset-0 size-full') }}>
    <defs>
        <radialGradient id="{{ $id }}-glass" cx="0.38" cy="0.35" r="0.75">
            <stop offset="0" stop-color="#4a5a73" />
            <stop offset="0.45" stop-color="#1b2230" />
            <stop offset="1" stop-color="#05070b" />
        </radialGradient>
        <linearGradient id="{{ $id }}-ring" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#f2f2f4" stop-opacity="0.9" />
            <stop offset="0.5" stop-color="#8e8e93" stop-opacity="0.9" />
            <stop offset="1" stop-color="#d1d1d6" stop-opacity="0.9" />
        </linearGradient>
        <radialGradient id="{{ $id }}-flash" cx="0.5" cy="0.5" r="0.6">
            <stop offset="0" stop-color="#fffdf2" />
            <stop offset="0.7" stop-color="#f1e7c4" />
            <stop offset="1" stop-color="#bfb38b" />
        </radialGradient>
        <linearGradient id="{{ $id }}-sheen" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.35" />
            <stop offset="0.6" stop-color="#ffffff" stop-opacity="0" />
            <stop offset="1" stop-color="#000000" stop-opacity="0.12" />
        </linearGradient>
    </defs>

    {{-- Case cutouts: the phone body shows through, with the case wall as a dark edge. --}}
    @foreach ($mockup['holes'] as [$x, $y, $rw, $rh, $r])
        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $rw }}" height="{{ $rh }}" rx="{{ $r }}" fill="{{ $mockup['body'] }}" />
        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $rw }}" height="{{ $rh }}" rx="{{ $r }}" fill="url(#{{ $id }}-sheen)" stroke="#000" stroke-opacity="0.35" stroke-width="0.7" />
    @endforeach
    @foreach ($mockup['roundHoles'] as [$cx, $cy, $r])
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="{{ $mockup['body'] }}" stroke="#000" stroke-opacity="0.35" stroke-width="0.7" />
    @endforeach

    {{-- Raised camera glass. --}}
    @foreach ($mockup['islands'] as [$x, $y, $rw, $rh, $r])
        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $rw }}" height="{{ $rh }}" rx="{{ $r }}" fill="{{ $mockup['body'] }}" />
        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $rw }}" height="{{ $rh }}" rx="{{ $r }}" fill="url(#{{ $id }}-sheen)" stroke="#000" stroke-opacity="0.18" stroke-width="0.4" />
    @endforeach

    @foreach ($mockup['lenses'] as [$cx, $cy, $r])
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="#1c1c1e" />
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r * 0.93 }}" fill="none" stroke="url(#{{ $id }}-ring)" stroke-width="{{ $r * 0.1 }}" />
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r * 0.7 }}" fill="url(#{{ $id }}-glass)" />
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r * 0.3 }}" fill="#030406" />
        <circle cx="{{ $cx - $r * 0.25 }}" cy="{{ $cy - $r * 0.28 }}" r="{{ $r * 0.12 }}" fill="#fff" fill-opacity="0.45" />
    @endforeach

    @foreach ($mockup['periscopes'] as [$cx, $cy, $r])
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="#1c1c1e" />
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r * 0.92 }}" fill="none" stroke="url(#{{ $id }}-ring)" stroke-width="{{ $r * 0.1 }}" />
        <rect x="{{ $cx - $r * 0.5 }}" y="{{ $cy - $r * 0.5 }}" width="{{ $r }}" height="{{ $r }}" rx="{{ $r * 0.12 }}" fill="url(#{{ $id }}-glass)" />
    @endforeach

    @foreach ($mockup['flashes'] as [$cx, $cy, $r])
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="url(#{{ $id }}-flash)" stroke="#000" stroke-opacity="0.25" stroke-width="0.3" />
    @endforeach

    @foreach ($mockup['sensors'] as [$cx, $cy, $r])
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="#121214" />
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r * 0.55 }}" fill="#26262b" />
    @endforeach

    @foreach ($mockup['dots'] as [$cx, $cy, $r])
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $r }}" fill="#000" fill-opacity="0.55" />
    @endforeach
</svg>
