{{--
    Lahiya Store mark: an app-icon tile with a custom phone case on it. The camera cutout sits
    top left and a sakura-to-blue band runs across the back, standing for "your picture on a case".
    The same drawing is public/favicon.svg.
--}}
@php($id = 'lm'.substr(md5(uniqid('', true)), 0, 6))
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" aria-hidden="true" {{ $attributes }}>
    <defs>
        <linearGradient id="{{ $id }}-tile" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#2c2c2e" />
            <stop offset="1" stop-color="#0b0b0c" />
        </linearGradient>
        <linearGradient id="{{ $id }}-band" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#ff5f8f" />
            <stop offset="0.55" stop-color="#8b5cf6" />
            <stop offset="1" stop-color="#0071e3" />
        </linearGradient>
        <clipPath id="{{ $id }}-case"><rect x="9" y="4.5" width="14" height="23" rx="4" /></clipPath>
    </defs>
    <rect width="32" height="32" rx="8" fill="url(#{{ $id }}-tile)" />
    <rect x="9" y="4.5" width="14" height="23" rx="4" fill="#fff" />
    <g clip-path="url(#{{ $id }}-case)">
        <path d="M5 23.5 L27 12.5 L27 21 L5 32 Z" fill="url(#{{ $id }}-band)" />
    </g>
    <rect x="11" y="6.5" width="5.6" height="5.6" rx="1.8" fill="#1d1d1f" />
    <circle cx="13.8" cy="9.3" r="1.25" fill="#5e5ce6" />
</svg>
