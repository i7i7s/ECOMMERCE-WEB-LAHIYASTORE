@props(['image', 'model' => 'iphone-17-pro-max', 'frame' => 'rgb(232 232 237 / 0.92)'])

@php
    $mockup = \App\Support\PhoneMockups::for($model);
    $rim = 1.8;
    $outerWidth = $mockup['width'] + 2 * $rim;
    $outerHeight = $mockup['height'] + 2 * $rim;
    $phoneRadius = round($mockup['radius'] / $mockup['width'] * 100, 2).'% / '.round($mockup['radius'] / $mockup['height'] * 100, 2).'%';
    $caseRadius = round(($mockup['radius'] + $rim) / $outerWidth * 100, 2).'% / '.round(($mockup['radius'] + $rim) / $outerHeight * 100, 2).'%';
    $inset = round($rim / $outerWidth * 100, 2).'% '.round($rim / $outerHeight * 100, 2).'%';

    // Without a case the whole camera plateau is visible, edge to edge.
    $bare = \App\Support\PhoneMockups::bare($mockup);

    // The case has real holes where the camera is, so the phone's own camera shows through while it slides on.
    $caseMask = rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$outerWidth.' '.$outerHeight.'" preserveAspectRatio="none"><path fill-rule="evenodd" d="M0 0H'.$outerWidth.'V'.$outerHeight.'H0Z'.\App\Support\PhoneMockups::cutoutPath($mockup, $rim, $rim).'"/></svg>');
    $maskStyle = "mask: url('data:image/svg+xml,{$caseMask}') center / 100% 100% no-repeat; -webkit-mask: url('data:image/svg+xml,{$caseMask}') center / 100% 100% no-repeat";
@endphp

{{--
    Landing page animation: the bare phone turns slowly in 3D, then a Lahiya case slides on,
    stays for a moment and slides off again (CSS keyframes in resources/css/app.css).
--}}
<div aria-hidden="true" {{ $attributes->class('hero-phone relative') }} style="aspect-ratio: {{ $outerWidth }} / {{ $outerHeight }}">
    <div class="hero-phone-rig absolute inset-0">
        <div class="hero-phone-edge absolute" style="inset: {{ $inset }}; border-radius: {{ $phoneRadius }}; background: {{ $mockup['body'] }}"></div>
        <div class="hero-phone-body absolute overflow-hidden" style="inset: {{ $inset }}; border-radius: {{ $phoneRadius }}; background: {{ $mockup['body'] }}">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,rgb(255_255_255/0.28),rgb(255_255_255/0)_45%,rgb(0_0_0/0.14))]"></div>
            <x-store.phone-camera :mockup="$bare" />
        </div>

        <div class="hero-phone-case absolute inset-0">
            <div class="absolute inset-0" style="background: {{ $frame }}; padding: {{ round($rim / $outerWidth * 100, 2) }}%; border-radius: {{ $caseRadius }}; {{ $maskStyle }}">
                <div class="relative size-full overflow-hidden bg-white" style="border-radius: {{ $phoneRadius }}">
                    <img src="{{ $image }}" alt="" class="absolute inset-0 size-full object-cover" decoding="async">
                    {{-- Case wall around each cutout. --}}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {{ $mockup['width'] }} {{ $mockup['height'] }}" preserveAspectRatio="none" class="absolute inset-0 size-full">
                        @foreach ($mockup['holes'] as [$x, $y, $holeWidth, $holeHeight, $radius])
                            <rect x="{{ $x }}" y="{{ $y }}" width="{{ $holeWidth }}" height="{{ $holeHeight }}" rx="{{ $radius }}" fill="none" stroke="#fff" stroke-opacity="0.85" stroke-width="1.4" />
                            <rect x="{{ $x }}" y="{{ $y }}" width="{{ $holeWidth }}" height="{{ $holeHeight }}" rx="{{ $radius }}" fill="none" stroke="#000" stroke-opacity="0.3" stroke-width="0.5" />
                        @endforeach
                        @foreach ($mockup['roundHoles'] as [$cx, $cy, $radius])
                            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $radius }}" fill="none" stroke="#fff" stroke-opacity="0.85" stroke-width="1.4" />
                            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $radius }}" fill="none" stroke="#000" stroke-opacity="0.3" stroke-width="0.5" />
                        @endforeach
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="glass absolute -right-10 bottom-12 flex items-center gap-2.5 rounded-2xl px-4 py-3 text-sm shadow-lg sm:-right-16">
        <span class="hero-phone-dot size-2.5 rounded-full bg-muted"></span>
        <span class="grid">
            <span class="hero-phone-label-bare col-start-1 row-start-1 whitespace-nowrap"><strong class="font-semibold">iPhone 17 Pro Max</strong> · tanpa casing</span>
            <span class="hero-phone-label-case col-start-1 row-start-1 whitespace-nowrap"><strong class="font-semibold">Casing Lahiya</strong> · terpasang</span>
        </span>
    </div>
</div>
