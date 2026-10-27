<div class="mx-auto max-w-xl px-4 pt-8 pb-24 sm:px-6">
    <h1 class="text-3xl font-bold tracking-tight">Checkout</h1>

    <ol aria-label="Langkah checkout" class="mt-5 grid grid-cols-3 gap-1.5">
        @foreach (['Alamat', 'Ringkasan', 'Pembayaran'] as $label)
            <li @if ($step === $loop->iteration) aria-current="step" @endif @class(['flex flex-col gap-1.5 text-xs', 'font-semibold text-ink' => $step === $loop->iteration, 'text-muted' => $step !== $loop->iteration])>
                <span @class(['h-1 rounded-full', 'bg-action' => $loop->iteration <= $step, 'bg-line' => $loop->iteration > $step])></span>
                {{ $loop->iteration }} {{ $label }}
            </li>
        @endforeach
    </ol>

    @if ($step === 1)
        <form wire:submit="toSummary" class="mt-6 flex flex-col gap-4">
            <p class="text-[15px] text-muted">Pengiriman hanya ke alamat di Jepang, gratis ongkir.</p>


            @if ($savedAddresses->isNotEmpty())
                <label class="flex flex-col gap-1.5 text-[13px] text-muted">Alamat tersimpan
                    <select wire:model.live="addressId" class="h-12.5 rounded-xl border border-line bg-white px-4 text-[17px] text-ink">
                        @foreach ($savedAddresses as $saved)
                            <option value="{{ $saved->id }}">{{ $saved->label ?: $saved->recipient_name }} · {{ $saved->prefecture }}, {{ $saved->city }}</option>
                        @endforeach
                        <option value="0">Alamat baru…</option>
                    </select>
                </label>
            @endif
            @foreach ([
                ['name', 'Nama penerima', 'text', 'name', null],
                ['phone', 'No. HP', 'tel', 'tel', '080-1234-5678'],
                ['postalCode', 'Kode pos (7 digit)', 'text', 'postal-code', '530-0001'],
            ] as [$field, $label, $type, $autocomplete, $placeholder])
                <label class="flex flex-col gap-1.5 text-[13px] text-muted">{{ $label }}
                    <input wire:model="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}" @if ($placeholder) placeholder="{{ $placeholder }}" @endif @class(['h-12.5 rounded-xl border bg-white px-4 text-[17px] text-ink', 'border-bad' => $errors->has($field), 'border-line' => ! $errors->has($field)])>
                    @error($field) <span class="text-sm text-bad">{{ $message }}</span> @enderror
                </label>
            @endforeach

            <label class="flex flex-col gap-1.5 text-[13px] text-muted">Prefektur
                <select wire:model="prefecture" autocomplete="address-level1" @class(['h-12.5 rounded-xl border bg-white px-4 text-[17px] text-ink', 'border-bad' => $errors->has('prefecture'), 'border-line' => ! $errors->has('prefecture')])>
                    <option value="">Pilih prefektur</option>
                    @foreach ($prefectures as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
                @error('prefecture') <span class="text-sm text-bad">{{ $message }}</span> @enderror
            </label>

            @foreach ([
                ['city', 'Kota / ward', 'address-level2', 'Osaka-shi Kita-ku'],
                ['addressLine1', 'Alamat (chome, banchi)', 'address-line1', 'Umeda 1-2-3'],
                ['addressLine2', 'Nama gedung & kamar (opsional)', 'address-line2', 'Lahiya Mansion 405'],
            ] as [$field, $label, $autocomplete, $placeholder])
                <label class="flex flex-col gap-1.5 text-[13px] text-muted">{{ $label }}
                    <input wire:model="{{ $field }}" autocomplete="{{ $autocomplete }}" placeholder="{{ $placeholder }}" @class(['h-12.5 rounded-xl border bg-white px-4 text-[17px] text-ink', 'border-bad' => $errors->has($field), 'border-line' => ! $errors->has($field)])>
                    @error($field) <span class="text-sm text-bad">{{ $message }}</span> @enderror
                </label>
            @endforeach

            @if (auth()->check() && ! $addressId)
                <label class="flex cursor-pointer items-center gap-2.5 text-[15px]">
                    <input type="checkbox" wire:model="saveAddress" class="size-5 accent-action">
                    Simpan alamat ini ke akun
                </label>
            @endif

            <button type="submit" class="mt-2 min-h-12.5 cursor-pointer rounded-full bg-action text-[17px] font-medium text-white hover:bg-[#0077ed]">Lanjut ke ringkasan</button>
        </form>
    @elseif ($step === 2)
        <div class="mt-6 flex flex-col gap-3">
            <section aria-label="Item" class="flex flex-col gap-4 rounded-[18px] bg-mist p-4">
                @foreach ($items as $item)
                    <div wire:key="summary-{{ $item['id'] }}" class="flex gap-3.5">
                        @if ($item['thumbnail'])
                            <img src="{{ $item['thumbnail'] }}" alt="" class="h-20 w-11 shrink-0 rounded-xl border-[3px] border-[#d9e1e8] object-cover">
                        @else
                            <x-store.product-image :path="$item['image']" class="h-20 w-11 shrink-0 rounded-xl" />
                        @endif
                        <div class="flex grow flex-col gap-0.5">
                            <span class="text-[15px] font-semibold">{{ $item['name'] }}</span>
                            <span class="text-[13px] text-muted">{{ $item['detail'] }}</span>
                            <span class="mt-auto flex justify-between text-[15px]"><span class="text-muted">Qty {{ $item['qty'] }}</span><strong class="font-semibold">{{ $item['line_total'] }}</strong></span>
                        </div>
                    </div>
                @endforeach
            </section>

            <section aria-label="Alamat pengiriman" class="rounded-[18px] bg-mist p-4">
                <div class="flex items-center justify-between">
                    <span class="text-[13px] font-semibold text-muted">Dikirim ke</span>
                    <button type="button" wire:click="backToAddress" class="min-h-8 cursor-pointer text-sm text-link">Ubah</button>
                </div>
                <p class="mt-1 text-[15px] font-semibold">{{ $name }} · {{ $phone }}</p>
                <p class="text-sm leading-relaxed text-ink-2">〒{{ $postalCode }} {{ $prefecture }}, {{ $city }}, {{ $addressLine1 }}{{ $addressLine2 ? ', '.$addressLine2 : '' }}</p>
            </section>

            <section aria-label="Estimasi tiba" class="flex items-center gap-3 rounded-[18px] bg-mist p-4">
                <span aria-hidden="true" class="flex size-10 items-center justify-center rounded-xl bg-info-soft text-link"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="3" /><path d="M3 10h18M8 3v4M16 3v4" /></svg></span>
                <span class="flex flex-col"><span class="text-[15px] font-semibold">Estimasi tiba {{ $etaLabel }}</span><span class="text-[13px] text-muted">Batch {{ $batch->code }} · ditutup {{ $cutoffLabel }}</span></span>
            </section>

            <section aria-label="Total" class="flex flex-col gap-2 rounded-[18px] bg-mist p-4 text-[15px]">
                <div class="flex justify-between"><span class="text-muted">Subtotal</span><span>{{ $total }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Ongkir di Jepang</span><span class="text-good">Gratis</span></div>
                <div class="flex justify-between border-t border-line pt-2 text-[17px] font-semibold"><span>Total</span><span>{{ $total }}</span></div>
            </section>

            @if ($needsConfirmation)
                <p role="note" class="rounded-[18px] bg-confirm-soft px-4 py-3 text-sm leading-relaxed text-confirm">Pesanan berisi tipe HP tanpa preview. Tim akan mengonfirmasi ketersediaan dulu; instruksi pembayaran dikirim setelah dikonfirmasi.</p>
            @endif

            <form wire:submit="placeOrder" class="flex flex-col gap-3">
                <label class="flex cursor-pointer items-start gap-3 px-1 pt-1 text-sm leading-relaxed">
                    <input type="checkbox" wire:model="agreePolicy" class="mt-0.5 size-5 shrink-0 accent-action">
                    <span>Saya setuju dengan <a href="{{ route('policy') }}" target="_blank" class="text-link hover:underline">kebijakan preorder</a> dan <a href="{{ route('policy') }}#refund" target="_blank" class="text-link hover:underline">retur &amp; refund</a>. Pesanan tidak bisa dibatalkan setelah diproduksi.</span>
                </label>
                @error('agreePolicy') <p class="px-1 text-sm text-bad">{{ $message }}</p> @enderror
                @error('order') <p role="alert" class="rounded-2xl bg-bad-soft px-4 py-3 text-sm text-bad">{{ $message }}</p> @enderror
                <button type="submit" class="min-h-12.5 cursor-pointer rounded-full bg-action text-[17px] font-medium text-white hover:bg-[#0077ed]">
                    <span wire:loading.remove wire:target="placeOrder">Buat pesanan · {{ $total }}</span>
                    <span wire:loading wire:target="placeOrder">Memproses…</span>
                </button>
            </form>
        </div>
    @else
        <div class="mt-6 flex flex-col gap-3">
            <section class="py-2 text-center">
                <span class="inline-flex rounded-full px-3 py-1 text-[13px] font-semibold {{ $orderStatus->badgeClasses() }}">{{ $orderStatus->label() }}</span>
                <p class="mt-3 text-[13px] text-muted">Order {{ $order->code }}</p>
                <p class="mt-0.5 text-[40px] font-bold tracking-tight">{{ $orderTotal }}</p>
                @if ($paymentDue)
                    <p class="mt-1 text-sm text-ink-2">Bayar sebelum <strong class="font-semibold">{{ $paymentDue }}</strong></p>
                @endif
            </section>

            <livewire:store.payment-panel :order-code="$order->code" :show-due="false" wire:key="payment-{{ $order->code }}" />

            <a href="{{ route('track', ['kode' => $order->code]) }}" wire:navigate class="flex min-h-11 items-center justify-center text-[15px] text-link hover:underline">Lihat status order</a>
        </div>
    @endif
</div>
