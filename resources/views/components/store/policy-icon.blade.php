@props(['name'])
<svg {{ $attributes->class('shrink-0') }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('preorder')
            <path d="m12 3 9 5v8l-9 5-9-5V8l9-5Z" /><path d="m3 8 9 5 9-5M12 13v8M7.5 5.5l9 5" />
            @break
        @case('payment')
            <rect x="3" y="5" width="18" height="14" rx="3" /><path d="M3 10h18M7 15h3" />
            @break
        @case('cancellation')
            <circle cx="12" cy="12" r="9" /><path d="m9 9 6 6m0-6-6 6" />
            @break
        @case('complaints')
            <path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z" /><path d="m8.5 12 2.5 2.5 4.5-5" />
            @break
        @case('link')
            <path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-2 2M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l2-2" />
            @break
        @case('copy')
            <rect x="8" y="8" width="12" height="12" rx="2" /><path d="M16 8V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h4" />
            @break
        @case('check')
            <path d="m5 12 4 4L19 6" />
            @break
        @case('arrow')
            <path d="M5 12h14m-6-6 6 6-6 6" />
            @break
        @case('chevron')
            <path d="m7 10 5 5 5-5" />
            @break
    @endswitch
</svg>
