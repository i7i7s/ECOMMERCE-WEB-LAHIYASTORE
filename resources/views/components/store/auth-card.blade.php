@props(['title', 'subtitle' => null])

<div class="flex min-h-[calc(100svh-52px)] items-start justify-center bg-mist px-4 py-12 sm:items-center sm:px-6">
    <div class="w-full max-w-md rounded-tile bg-white p-7 shadow-sm sm:p-9">
        <h1 class="text-[28px] font-bold tracking-tight">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1.5 text-[15px] text-muted">{{ $subtitle }}</p>
        @endif

        @if (session('login_notice'))
            <p role="status" class="mt-5 rounded-2xl bg-info-soft px-4 py-3 text-sm text-info">{{ session('login_notice') }}</p>
        @endif
        @if (session('auth_error'))
            <p role="alert" class="mt-5 rounded-2xl bg-bad-soft px-4 py-3 text-sm text-bad">{{ session('auth_error') }}</p>
        @endif
        @if (session('status'))
            <p role="status" class="mt-5 rounded-2xl bg-good-soft px-4 py-3 text-sm text-good">{{ session('status') }}</p>
        @endif

        <div class="mt-6">
            {{ $slot }}
        </div>
    </div>
</div>
