@props([
    'word' => 'lahiya',
    'tagline' => 'CASING KAMU. CERITA KAMU.',
    'caption' => null,
])

@php
    $caption ??= 'Custom casing · Preorder '.\App\Support\BatchSchedule::periodLabel().' · Gratis ongkir di Jepang';
    $wordSize = min(232, (int) round(1500 / (max(1, mb_strlen($word)) * 0.68)));
    $font = "-apple-system, 'SF Pro Display', 'Helvetica Neue', 'Segoe UI', sans-serif";
@endphp

{{-- Scroll-driven reveal; behaviour lives in resources/js/tear-reveal.js. --}}
<section data-tear-reveal {{ $attributes->class('relative h-[190svh] bg-mist') }} aria-label="Lahiya Store: {{ mb_strtolower($tagline) }}">
    <div data-tear-stage class="sticky top-13 h-[calc(100svh-52px)] touch-pan-y overflow-hidden bg-[#0b0b0c]">
        {{-- Behind the paper: phones wearing Lahiya cases. --}}
        <div data-tear-scene class="absolute inset-0 opacity-0" aria-hidden="true">
            <div class="absolute inset-0 bg-[radial-gradient(55%_45%_at_50%_52%,#34324a_0%,#16151d_55%,#0b0b0c_100%)]"></div>
            <div class="absolute inset-x-0 top-[54%] flex -translate-y-1/2 justify-center">
                <div data-tear-rig class="tear-rig flex items-center justify-center">
                    <x-store.cased-phone model="iphone-17" class="tear-phone tear-phone-left">
                        <x-store.case-art design="sakura" />
                    </x-store.cased-phone>
                    <x-store.cased-phone model="iphone-17-pro-max" class="tear-phone tear-phone-center">
                        <x-store.case-art design="fuji" />
                    </x-store.cased-phone>
                    <x-store.cased-phone model="samsung-galaxy-s25-ultra" class="tear-phone tear-phone-right">
                        <x-store.case-art design="kawung" />
                    </x-store.cased-phone>
                </div>
            </div>
        </div>

        <svg viewBox="0 0 1000 560" preserveAspectRatio="xMidYMid meet" class="absolute inset-0 block size-full overflow-visible" role="img" aria-label="Kertas bertuliskan {{ $word }} sobek, lalu muncul HP dengan casing custom">
            <defs>
                <clipPath id="tear-clip-top"><path data-tear="top-clip" /></clipPath>
                <clipPath id="tear-clip-bottom"><path data-tear="bottom-clip" /></clipPath>
            </defs>

            <g data-tear="shake">
                @foreach (['top' => 10, 'bottom' => -10] as $half => $shadowOffset)
                    <g data-tear="{{ $half }}">
                        <path data-tear="line shadow" fill="none" stroke="#000" stroke-opacity="0" stroke-width="20" transform="translate(0 {{ $shadowOffset }})" />
                        <g clip-path="url(#tear-clip-{{ $half }})">
                            <rect x="-3000" y="-3000" width="7000" height="7000" fill="#f5f5f7" />
                            <x-store.logo-mark x="465" y="58" width="70" height="70" />
                            <text x="500" y="168" text-anchor="middle" font-size="21" font-weight="600" letter-spacing="7" fill="#6e6e73" font-family="{{ $font }}">{{ $tagline }}</text>
                            <text x="500" y="372" text-anchor="middle" font-size="{{ $wordSize }}" font-weight="800" letter-spacing="-8" fill="#1d1d1f" font-family="{{ $font }}">{{ $word }}</text>
                            <text x="500" y="452" text-anchor="middle" font-size="17" fill="#6e6e73" font-family="{{ $font }}">{{ $caption }}</text>
                        </g>
                        <path data-tear="fibre-{{ $half }}" fill="#ffffff" />
                    </g>
                @endforeach
                <path data-tear="line crack" fill="none" stroke="#1d1d1f" stroke-width="2.4" opacity="0" />
            </g>
        </svg>

        <p class="pointer-events-none absolute inset-x-0 bottom-6 text-center text-xs font-medium text-muted motion-reduce:hidden">Gulir ke bawah</p>
    </div>
</section>
