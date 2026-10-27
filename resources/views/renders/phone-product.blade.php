@php
    // 600 × 600 picture; both views are the same height, front on the left, back on the right.
    $w = $mockup['width'];
    $h = $mockup['height'];
    $scale = 460 / $h;
    $pw = round($w * $scale, 2);
    $ph = round($h * $scale, 2);
    $gap = 18;
    $frontX = round((600 - 2 * $pw - $gap) / 2, 2);
    $backX = round($frontX + $pw + $gap, 2);
    $top = 58;
    $bezel = 1.0;
    $screenInset = 2.4;
    $id = 'pr'.substr(md5($mockup['body'].$w.$h), 0, 6);
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" width="600" height="600">
    <defs>
        <linearGradient id="{{ $id }}-bg" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#fbfbfd" />
            <stop offset="1" stop-color="#ececf0" />
        </linearGradient>
        <linearGradient id="{{ $id }}-wall" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="{{ $wallpaperFrom }}" />
            <stop offset="1" stop-color="{{ $wallpaperTo }}" />
        </linearGradient>
        <radialGradient id="{{ $id }}-glow" cx="0.3" cy="0.25" r="0.6">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.55" />
            <stop offset="1" stop-color="#ffffff" stop-opacity="0" />
        </radialGradient>
        <linearGradient id="{{ $id }}-sheen" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#ffffff" stop-opacity="0.32" />
            <stop offset="0.45" stop-color="#ffffff" stop-opacity="0" />
            <stop offset="1" stop-color="#000000" stop-opacity="0.12" />
        </linearGradient>
        <filter id="{{ $id }}-blur" x="-20%" y="-200%" width="140%" height="500%">
            <feGaussianBlur stdDeviation="9" />
        </filter>
    </defs>

    <rect width="600" height="600" fill="url(#{{ $id }}-bg)" />

    @foreach ([$frontX, $backX] as $x)
        <ellipse cx="{{ $x + $pw / 2 }}" cy="{{ $top + $ph + 6 }}" rx="{{ $pw * 0.42 }}" ry="7" fill="#000" opacity="0.18" filter="url(#{{ $id }}-blur)" />
    @endforeach

    {{-- Front: aluminium edge, black bezel, wallpaper, Dynamic Island or notch. --}}
    <svg x="{{ $frontX }}" y="{{ $top }}" width="{{ $pw }}" height="{{ $ph }}" viewBox="0 0 {{ $w }} {{ $h }}">
        <rect width="{{ $w }}" height="{{ $h }}" rx="{{ $mockup['radius'] }}" fill="{{ $mockup['body'] }}" />
        <rect x="{{ $bezel }}" y="{{ $bezel }}" width="{{ $w - 2 * $bezel }}" height="{{ $h - 2 * $bezel }}" rx="{{ $mockup['radius'] - $bezel }}" fill="#0b0b0c" />
        <rect x="{{ $screenInset }}" y="{{ $screenInset }}" width="{{ $w - 2 * $screenInset }}" height="{{ $h - 2 * $screenInset }}" rx="{{ $mockup['radius'] - $screenInset }}" fill="url(#{{ $id }}-wall)" />
        <rect x="{{ $screenInset }}" y="{{ $screenInset }}" width="{{ $w - 2 * $screenInset }}" height="{{ $h - 2 * $screenInset }}" rx="{{ $mockup['radius'] - $screenInset }}" fill="url(#{{ $id }}-glow)" />
        <circle cx="{{ $w * 0.72 }}" cy="{{ $h * 0.68 }}" r="{{ $w * 0.42 }}" fill="#ffffff" opacity="0.08" />
        @if ($mockup['front'] === 'notch')
            <rect x="{{ $w / 2 - 15 }}" y="{{ $screenInset - 1 }}" width="30" height="6.4" rx="3" fill="#0b0b0c" />
        @else
            <rect x="{{ $w / 2 - 10.5 }}" y="4.4" width="21" height="6.2" rx="3.1" fill="#000" />
        @endif
        <rect x="{{ $w / 2 - 12 }}" y="{{ $h - 4.6 }}" width="24" height="1.1" rx="0.55" fill="#ffffff" opacity="0.75" />
    </svg>

    {{-- Back: coloured body with the real camera layout. --}}
    <svg x="{{ $backX }}" y="{{ $top }}" width="{{ $pw }}" height="{{ $ph }}" viewBox="0 0 {{ $w }} {{ $h }}">
        <rect width="{{ $w }}" height="{{ $h }}" rx="{{ $mockup['radius'] }}" fill="{{ $mockup['body'] }}" />
        <rect width="{{ $w }}" height="{{ $h }}" rx="{{ $mockup['radius'] }}" fill="url(#{{ $id }}-sheen)" stroke="#000" stroke-opacity="0.12" stroke-width="0.4" />
        <x-store.phone-camera :mockup="$mockup" />
    </svg>
</svg>
