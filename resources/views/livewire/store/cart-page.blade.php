<div class="mx-auto max-w-5xl px-4 pt-12 pb-24 sm:px-6">
    <h1 class="text-3xl font-bold tracking-tight sm:text-[40px]">Keranjang</h1>
    <p class="mt-2 text-[17px] text-muted">Semua harga dalam {{ $currency->value }}. Ganti mata uang di menu atas.</p>

    @if ($items->isEmpty())
        <div class="mt-10 rounded-tile bg-mist px-6 py-16 text-center">
            <p class="text-[21px] font-semibold">Keranjangmu masih kosong.</p>
            <div class="mt-5 flex flex-wrap justify-center gap-6">
                <a href="{{ route('custom') }}" wire:navigate class="inline-flex min-h-11 items-center rounded-full bg-action px-5.5 text-[17px] text-white">Buat casing custom</a>
                <a href="{{ route('catalog') }}" wire:navigate class="inline-flex min-h-11 items-center text-[17px] text-link hover:underline">Lihat katalog ›</a>
            </div>
        </div>
    @else
        <div class="mt-8 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
            <section aria-label="Item di keranjang" class="flex flex-col">
                @foreach ($items as $item)
                    <article wire:key="cart-{{ $item['id'] }}" class="flex gap-4.5 border-b border-line py-5.5">
                        <span aria-hidden="true" class="flex h-30 w-21 shrink-0 items-center justify-center rounded-[18px] bg-mist">
                            @if ($item['thumbnail'])
                                <img src="{{ $item['thumbnail'] }}" alt="" class="h-23 w-12 rounded-xl border-[3px] border-[#d9e1e8] object-cover">
                            @elseif ($item['type'] === 'custom_case')
                                <span class="h-23 w-12 rounded-xl border-[3px] border-[#d9e1e8] bg-[repeating-linear-gradient(45deg,#6e6e73_0_6px,#d2d2d7_6px_12px)]"></span>
                            @else
                                <x-store.product-image :path="$item['image']" class="size-16 rounded-xl" />
                            @endif
                        </span>
                        <div class="flex grow flex-col gap-1">
                            <div class="flex justify-between gap-3">
                                <span class="text-[17px] font-semibold">{{ $item['name'] }}</span>
                                <span class="text-[17px] font-semibold tabular-nums">{{ $item['line_total'] }}</span>
                            </div>
                            <span class="text-sm text-ink-2">{{ $item['detail'] }}</span>
                            @if ($item['needs_confirmation'])
                                <span class="text-[13px] text-confirm">Perlu konfirmasi ketersediaan sebelum bayar</span>
                            @endif
                            @if ($item['position_note'])
                                <span class="text-[13px] text-muted">Catatan: {{ $item['position_note'] }}</span>
                            @endif
                            <div class="mt-auto flex items-center gap-4 pt-2.5">
                                <div role="group" aria-label="Jumlah {{ $item['name'] }}" class="flex items-center rounded-full border border-line">
                                    <button type="button" wire:click="decrement('{{ $item['id'] }}')" @disabled($item['qty'] <= 1) aria-label="Kurangi" class="size-10 cursor-pointer text-lg disabled:cursor-not-allowed disabled:text-line">−</button>
                                    <span class="min-w-6 text-center text-[15px] tabular-nums">{{ $item['qty'] }}</span>
                                    <button type="button" wire:click="increment('{{ $item['id'] }}')" @disabled($item['qty'] >= $maxQty) aria-label="Tambah" class="size-10 cursor-pointer text-lg disabled:cursor-not-allowed disabled:text-line">+</button>
                                </div>
                                <button type="button" x-on:click="$store.confirm.ask({ title: 'Hapus item?', message: @js($item['name'].' akan dihapus dari keranjang.'), confirmLabel: 'Hapus', onConfirm: () => $wire.remove(@js($item['id'])) })" class="min-h-10 cursor-pointer text-sm text-link hover:underline">Hapus</button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <aside aria-label="Ringkasan" class="flex flex-col gap-3 rounded-[22px] bg-mist p-6 lg:sticky lg:top-20">
                <div class="flex justify-between text-[15px]"><span class="text-muted">Subtotal ({{ $count }} item)</span><span class="tabular-nums">{{ $subtotal }}</span></div>
                <div class="flex justify-between text-[15px]"><span class="text-muted">Ongkir di Jepang</span><span class="text-good">Gratis</span></div>
                <div class="flex justify-between border-t border-line pt-3 text-[21px] font-bold"><span>Total</span><span class="tabular-nums">{{ $subtotal }}</span></div>
                <div class="rounded-2xl bg-white px-3.5 py-3 text-[13px] leading-relaxed">Preorder batch {{ $batch->code }}<br><strong class="font-semibold">Estimasi tiba {{ $etaLabel }}</strong></div>
                @if ($needsConfirmation)
                    <div role="note" class="rounded-2xl bg-confirm-soft px-3.5 py-3 text-[13px] leading-relaxed text-confirm"><strong class="font-semibold">Ada item tanpa preview.</strong> Setelah checkout, tim mengecek ketersediaan blank case ke vendor (maks. 2 hari kerja). Kamu baru bayar setelah dikonfirmasi.</div>
                @endif
                <a href="{{ route('checkout') }}" wire:navigate class="flex min-h-12.5 items-center justify-center rounded-full bg-action text-[17px] text-white hover:bg-[#0077ed]">Lanjut ke checkout</a>
                <p class="text-center text-xs text-muted">Pesanan tersimpan di akunmu. Pengiriman hanya ke alamat di Jepang.</p>
            </aside>
        </div>
    @endif
</div>
