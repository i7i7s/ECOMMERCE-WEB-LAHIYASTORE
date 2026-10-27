{{-- Offline banner and request-failure toast; behaviour in resources/js/connection.js. --}}
<div x-data class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4">
    <div x-cloak x-show="! $store.connection.online" x-transition.opacity role="status" aria-live="polite" class="pointer-events-auto flex max-w-md items-center gap-3 rounded-2xl bg-ink px-4 py-3 text-sm text-white shadow-lg">
        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 8.8a15 15 0 0 1 4.2-2.6M10.7 5.1A15 15 0 0 1 22 8.8M5 12.6a10 10 0 0 1 3.5-2M15.5 10.6A10 10 0 0 1 19 12.6M8.5 16.4a5 5 0 0 1 7 0M12 20h.01M3 3l18 18" /></svg>
        <span>Kamu sedang offline. Perubahan belum bisa dikirim sampai koneksi kembali.</span>
    </div>

    <div x-cloak x-show="$store.connection.backOnline" x-transition.opacity role="status" aria-live="polite" class="pointer-events-auto rounded-2xl bg-good px-4 py-3 text-sm font-medium text-white shadow-lg">Koneksi kembali.</div>

    <div x-cloak x-show="$store.connection.toast" x-transition.opacity role="alert" class="pointer-events-auto flex max-w-md items-start gap-3 rounded-2xl bg-white px-4 py-3 text-sm text-ink shadow-lg ring-1 ring-black/8">
        <svg class="mt-0.5 size-5 shrink-0 text-bad" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v5M12 16h.01" /></svg>
        <span class="grow leading-relaxed" x-text="$store.connection.toast?.text"></span>
        <button type="button" x-show="$store.connection.toast?.reload" x-on:click="window.location.reload()" class="shrink-0 cursor-pointer font-semibold text-link">Muat ulang</button>
        <button type="button" x-on:click="$store.connection.dismiss()" aria-label="Tutup" class="-mr-1 shrink-0 cursor-pointer text-muted hover:text-ink">✕</button>
    </div>
</div>
