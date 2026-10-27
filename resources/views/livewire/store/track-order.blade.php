<div class="mx-auto max-w-xl px-4 pt-10 pb-24 sm:px-6">
    @if ($order === null)
        <h1 class="text-3xl font-bold tracking-tight sm:text-[40px]">Lacak order</h1>
        <p class="mt-2 text-[17px] text-muted">Masukkan kode order dan 4 digit terakhir nomor HP yang dipakai saat memesan.</p>
        <div class="mt-7">
            <livewire:store.track-form />
        </div>
    @else
        <section class="flex flex-col gap-1.5 rounded-[22px] bg-mist p-5">
            <span class="text-[13px] text-muted">{{ $order->code }}@if ($order->batch) · Batch {{ $order->batch->code }}@endif</span>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-[28px] font-bold tracking-tight">{{ $order->status->label() }}</h1>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $order->status->badgeClasses() }}">{{ $order->formattedTotal() }}</span>
            </div>
            <p class="text-[15px] leading-relaxed text-ink-2">{{ $order->status->description() }}</p>
            @if ($order->status === \App\Enums\OrderStatus::Dibatalkan && $order->cancel_reason)
                <p class="text-sm text-muted">Alasan: {{ $order->cancel_reason }}</p>
            @endif
            @if ($order->status === \App\Enums\OrderStatus::Dikirim && $order->tracking_number)
                <p class="mt-2 rounded-2xl bg-white px-3.5 py-3 text-[15px]">Kurir {{ $order->domestic_courier }} · resi <strong class="font-semibold">{{ $order->tracking_number }}</strong></p>
            @endif
            @unless (in_array($order->status, [\App\Enums\OrderStatus::Dibatalkan, \App\Enums\OrderStatus::Selesai], true))
                <p class="mt-2.5 rounded-2xl bg-info-soft px-3.5 py-3 text-[15px] text-info"><strong class="font-semibold">Estimasi tiba {{ $etaLabel }}</strong></p>
            @endunless
        </section>

        @if ($order->status === \App\Enums\OrderStatus::MenungguPembayaran)
            <section aria-labelledby="pay-h" class="mt-3 rounded-[22px] border border-line p-5">
                <h2 id="pay-h" class="mb-3 text-[19px] font-semibold">Bayar pesanan</h2>
                <livewire:store.payment-panel :order-code="$order->code" wire:key="payment-{{ $order->code }}" />
            </section>
        @endif

        <section aria-label="Riwayat status" class="mt-3 rounded-[22px] bg-mist p-5">
            <ol class="flex flex-col">
                @foreach ($steps as $step)
                    <li class="flex gap-3.5">
                        <div class="flex w-6 shrink-0 flex-col items-center">
                            <span @class([
                                'flex size-6 items-center justify-center rounded-full text-[13px] font-bold text-white',
                                'bg-good' => $step['state'] === 'done',
                                'border-[5px] border-[#cfe2fb] bg-action' => $step['state'] === 'current',
                                'border-2 border-line bg-white' => $step['state'] === 'next',
                            ])>@if ($step['state'] === 'done')✓@endif</span>
                            @unless ($loop->last)
                                <span @class(['min-h-4.5 w-0.5 grow', 'bg-good' => $step['state'] === 'done', 'bg-fog' => $step['state'] !== 'done'])></span>
                            @endunless
                        </div>
                        <div class="flex flex-col gap-0.5 pb-4">
                            <span @class(['text-[15px]', 'font-semibold' => $step['state'] === 'current', 'text-muted' => $step['state'] === 'next'])>{{ $step['label'] }}</span>
                            @if ($step['when'] && $step['state'] !== 'next')
                                <span class="text-[13px] text-muted">{{ $step['when'] }}</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        <section aria-label="Item" class="mt-3 flex flex-col gap-3 rounded-[22px] bg-mist p-5">
            @foreach ($order->items as $item)
                <div wire:key="item-{{ $item->id }}" class="flex justify-between gap-3 text-[15px]">
                    <span class="font-semibold">{{ $item->item_name }}</span>
                    <span class="shrink-0 text-muted">×{{ $item->qty }}</span>
                </div>
            @endforeach
        </section>

        <div class="mt-5 flex flex-wrap justify-center gap-6">
            @if ($whatsapp = \App\Models\Setting::valueOf('store_whatsapp'))
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" class="inline-flex min-h-11 items-center text-[17px] text-link hover:underline">Tanya via WhatsApp</a>
            @endif
            <a href="{{ route('track') }}" wire:navigate class="inline-flex min-h-11 items-center text-[17px] text-link hover:underline">Lacak order lain</a>
        </div>
    @endif
</div>
