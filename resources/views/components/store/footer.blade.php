<footer class="border-t border-line bg-mist px-4 pt-7 pb-10 sm:px-6">
    <div class="mx-auto flex max-w-5xl flex-col gap-3.5 text-xs leading-relaxed text-muted">
        <x-store.logo class="mb-1" />
        <p>Semua produk dijual dengan sistem preorder. Pesanan yang sudah berstatus Diproduksi tidak bisa dibatalkan karena casing dicetak khusus untukmu. Komplain diterima maksimal {{ (int) \App\Models\Setting::valueOf('complaint_days_after_received', '3') }} hari setelah paket diterima, disertai video unboxing.</p>
        <div class="flex flex-wrap gap-x-6 gap-y-2">
            <a href="{{ route('policy') }}" wire:navigate class="text-ink-2 hover:underline">Kebijakan Preorder</a>
            <a href="{{ route('policy.show', ['topic' => 'retur-refund']) }}" wire:navigate class="text-ink-2 hover:underline">Retur &amp; Refund</a>
            <a href="{{ route('track') }}" wire:navigate class="text-ink-2 hover:underline">Lacak Order</a>
            @if ($whatsapp = \App\Models\Setting::valueOf('store_whatsapp'))
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" class="text-ink-2 hover:underline">WhatsApp</a>
            @endif
            @if ($email = \App\Models\Setting::valueOf('store_email'))
                <a href="mailto:{{ $email }}" class="text-ink-2 hover:underline">{{ $email }}</a>
            @endif
            @if ($tiktok = \App\Models\Setting::valueOf('store_tiktok'))
                <a href="{{ $tiktok }}" class="text-ink-2 hover:underline" rel="noopener" target="_blank">TikTok</a>
            @endif
        </div>
        <p>© {{ now()->year }} Lahiya Store. Berbasis di Jepang.</p>
    </div>
</footer>
