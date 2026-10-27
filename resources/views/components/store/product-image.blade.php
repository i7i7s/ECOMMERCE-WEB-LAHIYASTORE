@props(['path' => null, 'alt' => ''])

@php
    // Files on the local public disk get a root-relative URL, so pictures load whichever
    // host serves the store (APP_URL, php artisan serve, a preview port). Cloud disks keep their own URL.
    $src = null;

    if ($path) {
        $src = \Illuminate\Support\Facades\Storage::disk('public')->url($path);

        if (config('filesystems.disks.public.driver') === 'local') {
            $src = parse_url($src, PHP_URL_PATH) ?: $src;
        }
    }
@endphp

{{-- Product photo from the public disk, or a neutral placeholder when none is uploaded yet. --}}
@if ($src)
    {{-- A missing file or dropped connection shows the placeholder instead of a broken image. --}}
    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" decoding="async" onerror="this.onerror = null; this.src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22%3E%3Crect width=%2224%22 height=%2224%22 fill=%22%23f5f5f7%22/%3E%3Cpath d=%22M9 9h6l-.5 6.5h-5z%22 fill=%22none%22 stroke=%22%23d2d2d7%22 stroke-width=%22.6%22/%3E%3C/svg%3E'" {{ $attributes->class('object-cover') }}>
@else
    <span aria-hidden="true" {{ $attributes->class('flex items-center justify-center bg-mist text-line') }}>
        <svg class="size-1/3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 7h12l-1 13H7L6 7z" /><path d="M9 7a3 3 0 0 1 6 0" /></svg>
    </span>
@endif
