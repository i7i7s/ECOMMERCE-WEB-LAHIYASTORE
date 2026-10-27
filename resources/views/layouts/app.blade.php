<!DOCTYPE html>
<html lang="id" @if (config('app.debug')) data-debug="1" @endif>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · Lahiya Store' : 'Lahiya Store · Casing custom, dikirim ke seluruh Jepang' }}</title>
        <meta name="description" content="Casing HP custom dari gambarmu. Preorder {{ \App\Support\BatchSchedule::periodLabel() }}, dicek tim sebelum produksi, gratis ongkir ke seluruh Jepang.">
        <meta property="og:site_name" content="Lahiya Store">
        <meta property="og:title" content="{{ isset($title) ? $title.' · Lahiya Store' : 'Lahiya Store · Casing custom dari gambarmu' }}">
        <meta property="og:description" content="Casing HP custom dari gambarmu. Preorder {{ \App\Support\BatchSchedule::periodLabel() }}, gratis ongkir ke seluruh Jepang.">
        <meta name="theme-color" content="#f5f5f7">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-paper">
        <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-full focus:bg-white focus:px-4 focus:py-2">Langsung ke konten</a>

        <x-store.nav />

        <main id="konten">
            {{ $slot }}
        </main>

        <x-store.footer />

        <x-store.connection-status />
        <x-store.confirm-dialog />

        @livewireScriptConfig
    </body>
</html>
