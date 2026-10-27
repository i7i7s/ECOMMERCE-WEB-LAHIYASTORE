@props(['design' => 'fuji'])

{{--
    Sample case artwork for the landing page, drawn in the phone's millimetre space (78 × 163).
    fuji: Mount Fuji at dusk · sakura: cherry blossoms · kawung: Javanese batik pattern.
--}}
@php($id = 'art'.substr(md5(uniqid('', true)), 0, 6))
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 78 163" preserveAspectRatio="xMidYMid slice" aria-hidden="true" {{ $attributes->class('absolute inset-0 size-full') }}>
    @if ($design === 'fuji')
        <defs>
            <linearGradient id="{{ $id }}-sky" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#2b2f6b" />
                <stop offset="0.45" stop-color="#b65d9b" />
                <stop offset="0.72" stop-color="#ff9e7a" />
                <stop offset="1" stop-color="#ffd8a8" />
            </linearGradient>
        </defs>
        <rect width="78" height="163" fill="url(#{{ $id }}-sky)" />
        <circle cx="39" cy="100" r="19" fill="#ffe7b0" opacity="0.9" />
        @foreach ([[12, 30], [60, 22], [70, 52], [24, 58], [46, 40], [8, 80]] as [$x, $y])
            <circle cx="{{ $x }}" cy="{{ $y }}" r="0.6" fill="#fff" opacity="0.8" />
        @endforeach
        <path d="M-6 163 L29 112 Q39 101 49 112 L84 163 Z" fill="#3b3f73" />
        <path d="M29 112 Q39 101 49 112 L45.5 119 L42 115.5 L39 121 L35.5 116 L32.5 119.5 Z" fill="#f7f4ff" />
        <path d="M-6 163 Q20 140 39 146 T84 138 V163 Z" fill="#26284f" />
        @foreach ([150, 155, 160] as $y)
            <path d="M8 {{ $y }} H{{ 70 - ($y - 150) * 2 }}" stroke="#ffd8a8" stroke-opacity="0.35" stroke-width="0.5" />
        @endforeach
    @elseif ($design === 'sakura')
        <defs>
            <g id="{{ $id }}-flower">
                @foreach ([0, 72, 144, 216, 288] as $angle)
                    <ellipse cx="0" cy="-3.6" rx="2.4" ry="3.6" transform="rotate({{ $angle }})" fill="#ff8fb1" />
                @endforeach
                <circle r="1.3" fill="#ffd166" />
            </g>
        </defs>
        <rect width="78" height="163" fill="#ffe8ef" />
        <path d="M-4 40 Q30 52 46 80 T84 120" stroke="#6b4a3a" stroke-width="2.2" fill="none" stroke-linecap="round" />
        <path d="M30 56 Q40 44 56 42" stroke="#6b4a3a" stroke-width="1.3" fill="none" stroke-linecap="round" />
        @foreach ([[10, 44, 1.2], [26, 54, 1], [44, 46, 1.3], [56, 42, 0.9], [44, 76, 1.1], [60, 98, 1.2], [72, 112, 0.9], [18, 120, 1], [34, 140, 0.8], [64, 146, 1.1], [14, 92, 0.7], [66, 70, 0.8]] as [$x, $y, $scale])
            <use href="#{{ $id }}-flower" transform="translate({{ $x }} {{ $y }}) scale({{ $scale }}) rotate({{ $x * 7 }})" />
        @endforeach
        @foreach ([[20, 76], [52, 120], [30, 104], [70, 132], [8, 150]] as [$x, $y])
            <ellipse cx="{{ $x }}" cy="{{ $y }}" rx="1.4" ry="2.2" transform="rotate({{ $y }} {{ $x }} {{ $y }})" fill="#ffb3c8" />
        @endforeach
    @else
        <defs>
            <pattern id="{{ $id }}-kawung" width="13" height="13" patternUnits="userSpaceOnUse">
                <rect width="13" height="13" fill="#f2e2c4" />
                @foreach ([0, 90, 180, 270] as $angle)
                    <g transform="rotate({{ $angle }} 6.5 6.5)">
                        <ellipse cx="6.5" cy="2.9" rx="2.5" ry="3.4" fill="#5c3418" />
                        <ellipse cx="6.5" cy="2.9" rx="1.2" ry="2" fill="#c98b4a" />
                    </g>
                @endforeach
                <circle cx="6.5" cy="6.5" r="0.9" fill="#5c3418" />
            </pattern>
        </defs>
        <rect width="78" height="163" fill="url(#{{ $id }}-kawung)" />
    @endif
</svg>
