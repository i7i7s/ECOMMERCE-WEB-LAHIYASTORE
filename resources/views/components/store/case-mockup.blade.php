@props(['frame' => '#d2d2d7', 'model' => 'iphone-17-pro-max', 'body' => '#3c4a5e'])

@php
    // Deep Blue body: the orange iPhone would disappear into the tiger artwork.
    $mockup = [...\App\Support\PhoneMockups::for($model), 'body' => $body];
    $rim = 1.8;
    $outerWidth = $mockup['width'] + 2 * $rim;
    $outerHeight = $mockup['height'] + 2 * $rim;
@endphp

{{-- Decorative phone case with the tiger-stripe sample artwork, drawn on a real phone back. --}}
<div aria-hidden="true" {{ $attributes->class('shadow-[0_30px_60px_rgba(0,0,0,0.18)]') }} style="background: {{ $frame }}; aspect-ratio: {{ $outerWidth }} / {{ $outerHeight }}; padding: {{ round($rim / $outerWidth * 100, 2) }}%; border-radius: {{ round(($mockup['radius'] + $rim) / $outerWidth * 100, 2) }}% / {{ round(($mockup['radius'] + $rim) / $outerHeight * 100, 2) }}%">
    <div class="case-art relative size-full overflow-hidden" style="border-radius: {{ round($mockup['radius'] / $mockup['width'] * 100, 2) }}% / {{ round($mockup['radius'] / $mockup['height'] * 100, 2) }}%">
        <x-store.phone-camera :mockup="$mockup" />
    </div>
</div>
