@props(['title'])

@php
    $tabs = [
        ['route' => 'account.orders', 'label' => 'Pesanan saya'],
        ['route' => 'account.credit', 'label' => 'Kredit'],
        ['route' => 'account.addresses', 'label' => 'Alamat'],
        ['route' => 'account.profile', 'label' => 'Profil'],
    ];
@endphp

<div class="mx-auto max-w-3xl px-4 pt-10 pb-24 sm:px-6">
    <p class="text-sm text-muted">Halo, {{ auth()->user()->name }}</p>
    <h1 class="mt-1 text-3xl font-bold tracking-tight sm:text-[40px]">{{ $title }}</h1>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-b border-line">
        <nav aria-label="Menu akun" class="-mb-px flex gap-6 text-[15px]">
            @foreach ($tabs as $tab)
                <a
                    href="{{ route($tab['route']) }}"
                    wire:navigate
                    @if (request()->routeIs($tab['route'])) aria-current="page" @endif
                    @class([
                        'border-b-2 pb-3',
                        'border-ink font-semibold text-ink' => request()->routeIs($tab['route']),
                        'border-transparent text-muted hover:text-ink' => ! request()->routeIs($tab['route']),
                    ])
                >{{ $tab['label'] }}</a>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="pb-3">
            @csrf
            <button type="submit" class="cursor-pointer text-[15px] text-link hover:underline">Keluar</button>
        </form>
    </div>

    <div class="mt-6">
        {{ $slot }}
    </div>
</div>
