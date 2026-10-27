@php
    $currency = \App\Enums\Currency::current();
    $links = [
        ['route' => 'catalog', 'label' => 'Katalog'],
        ['route' => 'custom', 'label' => 'Custom Casing'],
        ['route' => 'policy', 'label' => 'Cara Preorder'],
        ['route' => 'track', 'label' => 'Lacak Order'],
    ];
@endphp

<header x-data="{ open: false }" class="glass sticky top-0 z-40 border-b border-black/8">
    <nav aria-label="Navigasi utama" class="mx-auto flex h-13 max-w-5xl items-center justify-between gap-6 px-4 sm:px-6">
        <a href="{{ route('home') }}" wire:navigate aria-label="Lahiya Store, beranda"><x-store.logo compact /></a>

        <div class="hidden gap-8 text-[13px] whitespace-nowrap lg:flex">
            @foreach ($links as $link)
                <a
                    href="{{ route($link['route']) }}"
                    wire:navigate
                    @class(['text-ink hover:text-link', 'font-semibold' => request()->routeIs($link['route']) || ($link['route'] === 'policy' && request()->routeIs('policy.*'))])
                    @if (request()->routeIs($link['route']) || ($link['route'] === 'policy' && request()->routeIs('policy.*'))) aria-current="page" @endif
                >{{ $link['label'] }}</a>
            @endforeach
        </div>

        <div class="flex items-center gap-1">
            <form method="POST" action="{{ route('currency.update') }}" class="flex rounded-full bg-fog p-[3px]" aria-label="Mata uang">
                @csrf
                @foreach (\App\Enums\Currency::cases() as $option)
                    <button
                        type="submit"
                        name="currency"
                        value="{{ $option->value }}"
                        aria-pressed="{{ $option === $currency ? 'true' : 'false' }}"
                        @class([
                            'min-h-9 cursor-pointer rounded-full px-2.5 text-[13px] font-semibold whitespace-nowrap transition sm:px-3.5',
                            'bg-white text-ink shadow-sm' => $option === $currency,
                            'text-ink-2 hover:text-ink' => $option !== $currency,
                        ])
                    ><span class="max-sm:hidden">{{ $option->symbol() }} </span>{{ $option->value }}</button>
                @endforeach
            </form>

            <livewire:store.cart-counter />

            <a
                href="{{ auth()->check() ? route('account.orders') : route('login') }}"
                wire:navigate
                aria-label="{{ auth()->check() ? 'Akun saya' : 'Masuk' }}"
                class="flex size-11 items-center justify-center rounded-full text-ink"
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></svg>
            </a>

            <button type="button" class="flex size-11 items-center justify-center rounded-full text-ink lg:hidden" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-controls="menu-mobile" aria-label="Menu">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 8h16M4 16h16" /></svg>
            </button>
        </div>
    </nav>

    <div id="menu-mobile" x-cloak x-show="open" x-transition.opacity class="border-t border-black/8 lg:hidden">
        <div class="mx-auto flex max-w-5xl flex-col px-4 py-2">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" wire:navigate class="py-3 text-[17px] text-ink">{{ $link['label'] }}</a>
            @endforeach
            @auth
                <a href="{{ route('account.orders') }}" wire:navigate class="py-3 text-[17px] text-ink">Akun saya</a>
            @else
                <a href="{{ route('login') }}" wire:navigate class="py-3 text-[17px] text-ink">Masuk / Daftar</a>
            @endauth
        </div>
    </div>
</header>
