<div>
    <x-store.account-shell title="Pesanan saya">
        @forelse ($orders as $order)
            <a href="{{ route('track', ['kode' => $order->code]) }}" wire:navigate wire:key="order-{{ $order->id }}" class="mb-3 flex items-center justify-between gap-4 rounded-[20px] bg-mist p-5 text-ink hover:bg-fog">
                <span class="flex flex-col gap-1">
                    <span class="text-[17px] font-semibold">{{ $order->code }}</span>
                    <span class="text-[13px] text-muted">{{ $order->created_at->setTimezone('Asia/Tokyo')->locale('id')->translatedFormat('j M Y') }} · {{ $order->items_count }} item · {{ $order->formattedTotal() }}</span>
                    @if ($order->status === \App\Enums\OrderStatus::MenungguPembayaran)
                        <span class="text-[13px] font-semibold text-link">Bayar & upload bukti transfer ›</span>
                    @endif
                </span>
                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span>
            </a>
        @empty
            <div class="rounded-tile bg-mist px-6 py-14 text-center">
                <p class="text-[19px] font-semibold">Belum ada pesanan.</p>
                <p class="mt-1 text-[15px] text-muted">Pesanan yang kamu buat saat masuk akan muncul di sini.</p>
                <a href="{{ route('custom') }}" wire:navigate class="mt-5 inline-flex min-h-11 items-center rounded-full bg-action px-5.5 text-[17px] text-white">Buat casing custom</a>
            </div>
        @endforelse

        <div class="mt-6">{{ $orders->links() }}</div>
    </x-store.account-shell>
</div>
