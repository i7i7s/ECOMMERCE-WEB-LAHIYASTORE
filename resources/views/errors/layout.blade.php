{{--
    Error pages. Self-contained on purpose: no database, no Vite build and no Livewire,
    so they still render when one of those is the reason for the error.
--}}
@php
    $inAdmin = request()->is('admin', 'admin/*');
    $homeUrl = $inAdmin ? url('/admin') : url('/');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · Lahiya Store</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; background: #f5f5f7; color: #1d1d1f; font-family: -apple-system, 'SF Pro Text', 'Segoe UI', 'Helvetica Neue', sans-serif; text-align: center; }
        main { max-width: 460px; }
        .logo { display: inline-flex; align-items: center; gap: 8px; color: inherit; text-decoration: none; font-weight: 700; font-size: 19px; letter-spacing: -0.03em; }
        .logo svg { width: 28px; height: 28px; }
        .code { margin: 44px 0 0; font-size: 15px; font-weight: 600; letter-spacing: 0.2em; color: #86868b; }
        h1 { margin: 8px 0 10px; font-size: 34px; line-height: 1.15; letter-spacing: -0.02em; }
        p { margin: 0; color: #6e6e73; font-size: 17px; line-height: 1.5; }
        .actions { margin-top: 30px; display: flex; flex-wrap: wrap; gap: 14px 22px; align-items: center; justify-content: center; }
        .primary { display: inline-flex; align-items: center; min-height: 48px; padding: 0 26px; border: 0; border-radius: 999px; background: #0071e3; color: #fff; font: inherit; font-size: 17px; text-decoration: none; cursor: pointer; }
        .primary:hover { background: #0077ed; }
        .link { color: #0066cc; font-size: 17px; text-decoration: none; background: none; border: 0; font-family: inherit; cursor: pointer; padding: 0; }
        .link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <main>
        <a href="{{ $homeUrl }}" class="logo"><x-store.logo-mark />lahiya</a>
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            @hasSection('actions')
                @yield('actions')
            @else
                <a href="{{ $homeUrl }}" class="primary">{{ $inAdmin ? 'Ke dashboard admin' : 'Kembali ke beranda' }}</a>
                <button type="button" class="link" onclick="history.length > 1 ? history.back() : location.assign(@js($homeUrl))">Halaman sebelumnya</button>
            @endif
        </div>
    </main>
</body>
</html>
