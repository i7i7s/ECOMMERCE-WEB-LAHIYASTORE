@props(['model' => 'iphone-17-pro-max', 'frame' => 'rgb(232 232 237 / 0.95)'])

@php
    $mockup = \App\Support\PhoneMockups::for($model);
    $rim = 1.8;
    $outerWidth = $mockup['width'] + 2 * $rim;
    $outerHeight = $mockup['height'] + 2 * $rim;
@endphp

{{-- A phone wearing a printed case, seen from the back. The artwork goes in the slot. --}}
<div aria-hidden="true" {{ $attributes->class('relative') }} style="aspect-ratio: {{ $outerWidth }} / {{ $outerHeight }}; background: {{ $frame }}; padding: {{ round($rim / $outerWidth * 100, 2) }}%; border-radius: {{ round(($mockup['radius'] + $rim) / $outerWidth * 100, 2) }}% / {{ round(($mockup['radius'] + $rim) / $outerHeight * 100, 2) }}%">
    <div class="relative size-full overflow-hidden bg-white" style="border-radius: {{ round($mockup['radius'] / $mockup['width'] * 100, 2) }}% / {{ round($mockup['radius'] / $mockup['height'] * 100, 2) }}%">
        {{ $slot }}
        <x-store.phone-camera :mockup="$mockup" />
    </div>
</div>
