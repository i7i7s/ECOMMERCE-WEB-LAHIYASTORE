<div class="mx-auto max-w-5xl px-4 pt-8 pb-24 sm:px-6">
    <a href="{{ route('catalog', ['kategori' => $product->category->slug]) }}" wire:navigate class="text-sm text-link hover:underline">‹ {{ $product->category->name }}</a>

    <div class="mt-6 grid items-start gap-10 md:grid-cols-2">
        {{-- Pictures of the chosen colour. --}}
        <div x-data="{ shown: 0 }" class="flex flex-col gap-3 md:sticky md:top-20">
            <div class="relative overflow-hidden rounded-tile bg-mist">
                @foreach ($images as $index => $image)
                    <x-store.product-image wire:key="image-{{ $image->id }}" :path="$image->path" :alt="$product->name.' '.$image->color_name" class="aspect-square w-full" x-show="shown === {{ $index }}" />
                @endforeach
                @if ($images->isEmpty())
                    <x-store.product-image :path="null" :alt="$product->name" class="aspect-square w-full" />
                @endif
                <span class="absolute top-4 left-4 rounded-full bg-white px-3 py-1 text-xs font-semibold text-link">Bisa kredit</span>
            </div>
            @if ($images->count() > 1)
                <div class="flex gap-2">
                    @foreach ($images as $index => $image)
                        <button type="button" wire:key="thumb-{{ $image->id }}" x-on:click="shown = {{ $index }}" x-bind:class="shown === {{ $index }} ? 'ring-2 ring-action' : 'ring-1 ring-line'" class="size-16 cursor-pointer overflow-hidden rounded-xl" aria-label="Foto {{ $index + 1 }}">
                            <x-store.product-image :path="$image->path" alt="" class="size-full" />
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <form wire:submit="applyCredit" class="flex flex-col gap-6">
            <div>
                <p class="text-sm text-muted">{{ $product->category->name }}</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight sm:text-[40px]">{{ $product->name }}</h1>
                <p class="mt-2 text-[17px]">Harga {{ $money->format($price) }} · cicilan mulai <strong class="font-semibold">{{ $money->format($quote['monthly']) }}/bulan</strong></p>
                @if ($product->description)
                    <p class="mt-3 text-[15px] leading-relaxed text-ink-2">{{ $product->description }}</p>
                @endif
            </div>

            @if ($colors->isNotEmpty())
                <fieldset>
                    <legend class="mb-2.5 text-sm font-semibold text-muted">Warna · <span class="text-ink">{{ $color }}</span></legend>
                    <div class="flex flex-wrap gap-2.5">
                        @foreach ($colors as $name => $hex)
                            <label wire:key="color-{{ \Illuminate\Support\Str::slug($name) }}" @class(['size-11 cursor-pointer rounded-full border-2 p-0.5', 'border-action' => $color === $name, 'border-transparent' => $color !== $name])>
                                <input type="radio" wire:model.live="color" value="{{ $name }}" class="sr-only">
                                <span class="sr-only">{{ $name }}</span>
                                <span class="block size-full rounded-full border border-black/15" style="background: {{ $hex ?: '#d2d2d7' }}"></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <fieldset>
                <legend class="mb-2.5 text-sm font-semibold text-muted">Kapasitas</legend>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($storages as $option)
                        <label wire:key="storage-{{ $option->id }}" @class([
                            'flex min-h-14 cursor-pointer flex-col justify-center rounded-xl border px-3 py-2',
                            'border-action ring-1 ring-action' => $storage === $option->storage_gb,
                            'border-line hover:border-muted' => $storage !== $option->storage_gb,
                        ])>
                            <input type="radio" wire:model.live.number="storage" value="{{ $option->storage_gb }}" class="sr-only">
                            <span class="text-[15px] font-semibold">{{ $option->storageLabel() ?? $option->name }}</span>
                            <span class="text-[13px] text-muted">{{ $money->format($option->priceIn($money)) }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            {{-- Installment simulator. --}}
            <section aria-labelledby="sim-h" class="flex flex-col gap-4 rounded-[22px] bg-mist p-5">
                <h2 id="sim-h" class="text-[17px] font-semibold">Simulasi kredit</h2>

                <fieldset>
                    <legend class="mb-2 text-sm text-muted">Tenor</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($terms->tenors as $months)
                            <label wire:key="tenor-{{ $months }}" @class([
                                'flex min-h-11 min-w-16 cursor-pointer items-center justify-center rounded-full border px-4 text-[15px]',
                                'border-action bg-white font-semibold ring-1 ring-action' => $tenor === $months,
                                'border-line bg-white hover:border-muted' => $tenor !== $months,
                            ])>
                                <input type="radio" wire:model.live.number="tenor" value="{{ $months }}" class="sr-only">
                                {{ $months }} bulan
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <label class="flex flex-col gap-1.5 text-sm text-muted">
                    DP (uang muka) · minimal {{ $money->format($minimumDownPayment) }} ({{ $terms->minDownPaymentPercent }}%)
                    <span @class(['flex h-12.5 items-center gap-2 rounded-xl border bg-white px-4', 'border-bad' => ! $downPaymentValid, 'border-line focus-within:border-action' => $downPaymentValid])>
                        <span class="text-[17px] text-ink">{{ $money->symbol() }}</span>
                        <input type="number" inputmode="numeric" min="{{ $minimumDownPayment }}" max="{{ $price - $step }}" step="{{ $step }}" wire:model.live.debounce.400ms.number="downPayment" class="w-full bg-transparent text-[17px] text-ink tabular-nums outline-none">
                    </span>
                </label>
                <div class="-mt-2 flex flex-wrap gap-2">
                    @foreach (collect([$terms->minDownPaymentPercent, 30, 50])->filter(fn (int $percent): bool => $percent >= $terms->minDownPaymentPercent && $percent < 100)->unique() as $percent)
                        <button type="button" wire:key="dp-{{ $percent }}" wire:click="$set('downPayment', {{ (int) (ceil($price * $percent / 100 / $step) * $step) }})" class="min-h-9 cursor-pointer rounded-full bg-white px-3 text-[13px] text-link ring-1 ring-line hover:ring-action">DP {{ $percent }}%</button>
                    @endforeach
                </div>
                @unless ($downPaymentValid)
                    <p role="alert" class="-mt-2 text-sm text-bad">DP minimal {{ $money->format($minimumDownPayment) }} dan harus lebih kecil dari harga HP.</p>
                @endunless

                <dl class="flex flex-col gap-2 rounded-2xl bg-white px-4 py-3.5 text-[15px]">
                    <div class="flex justify-between gap-3"><dt class="text-muted">Harga {{ $variant->storageLabel() }}</dt><dd class="tabular-nums">{{ $money->format($price) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-muted">DP</dt><dd class="tabular-nums">{{ $money->format($downPayment) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-muted">Dicicil</dt><dd class="tabular-nums">{{ $money->format($quote['financed']) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-muted">Margin {{ rtrim(rtrim(number_format($terms->marginPercent, 2, ',', '.'), '0'), ',') }}%/bulan × {{ $tenor }}@if ($quote['admin_fee']) + biaya admin @endif</dt><dd class="tabular-nums">{{ $money->format($quote['margin'] + $quote['admin_fee']) }}</dd></div>
                    <div class="mt-1 flex items-baseline justify-between gap-3 border-t border-line pt-3">
                        <dt class="font-semibold">Cicilan per bulan</dt>
                        <dd class="text-[24px] font-bold tracking-tight tabular-nums">{{ $money->format($quote['monthly']) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 text-[13px] text-muted"><dt>Total bayar (DP + {{ $tenor }} × cicilan)</dt><dd class="tabular-nums">{{ $money->format($quote['total']) }}</dd></div>
                </dl>
                <p class="text-xs leading-relaxed text-muted">Kredit dalam {{ $money === \App\Enums\Currency::Jpy ? 'Yen' : 'Rupiah' }}, dibayar ke rekening toko dalam mata uang yang sama. Simulasi ini perkiraan; jadwal resmi dibuat setelah admin menyetujui pengajuanmu.</p>
            </section>

            {{-- What the customer can do depends on their credit verification. --}}
            <section aria-label="Pengajuan kredit" class="flex flex-col gap-3">
                @if ($creditState === 'guest')
                    <p class="text-[15px] leading-relaxed text-ink-2">Kredit hanya untuk akun yang sudah diverifikasi admin. Masuk atau daftar dulu.</p>
                    <a href="{{ $loginUrl }}" wire:navigate class="flex min-h-12.5 items-center justify-center rounded-full bg-action text-[17px] font-medium text-white hover:bg-[#0077ed]">Masuk untuk ajukan kredit</a>
                @elseif ($creditState === 'verified')
                    @if (blank($user->phone))
                        <p role="alert" class="rounded-2xl bg-warn-soft px-4 py-3 text-sm text-warn">Isi nomor HP di <a href="{{ route('account.profile') }}" wire:navigate class="font-semibold underline">profil</a> dulu supaya admin bisa menghubungimu.</p>
                    @else
                        <p class="flex items-center gap-2 text-sm text-good"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5" /></svg>Akunmu sudah terverifikasi untuk kredit @if ($remainingLimit !== null) · sisa batas {{ \App\Enums\Currency::Jpy->format($remainingLimit) }} @endif</p>
                        <label class="flex flex-col gap-1.5 text-sm text-muted">Catatan untuk admin (opsional)
                            <textarea wire:model="creditNote" rows="2" maxlength="500" placeholder="Contoh: ambil HP di Osaka hari Sabtu" class="rounded-xl border border-line bg-white px-3 py-2 text-[15px] text-ink"></textarea>
                        </label>
                        <button type="submit" wire:loading.attr="disabled" @disabled(! $downPaymentValid) class="min-h-12.5 cursor-pointer rounded-full bg-action text-[17px] font-medium text-white hover:bg-[#0077ed] disabled:cursor-not-allowed disabled:bg-fog disabled:text-muted">
                            <span wire:loading.remove wire:target="applyCredit">Ajukan kredit · {{ $money->format($quote['monthly']) }}/bulan</span>
                            <span wire:loading wire:target="applyCredit">Mengirim pengajuan…</span>
                        </button>
                    @endif
                @else
                    <div @class(['rounded-[18px] px-4 py-4 text-[15px] leading-relaxed', 'bg-warn-soft text-warn' => $creditState === 'requested', 'bg-mist text-ink-2' => $creditState !== 'requested'])>
                        @if ($creditState === 'requested')
                            <strong class="font-semibold">Menunggu verifikasi admin.</strong> Admin akan membalas lewat WhatsApp. Setelah terverifikasi, kamu bisa langsung mengajukan kredit di sini.
                        @elseif ($rejected)
                            <strong class="font-semibold">Verifikasi kredit belum disetujui.</strong>@if ($user->credit_note) Alasan: {{ $user->credit_note }}.@endif Hubungi admin kalau ada data yang ingin dilengkapi.
                        @else
                            <strong class="font-semibold">Verifikasi akun dulu.</strong> Kirim pesan ke admin lewat WhatsApp; admin akan mengecek data dan memverifikasi akunmu untuk kredit.
                        @endif
                    </div>
                    @if ($verificationUrl)
                        <a href="{{ $verificationUrl }}" target="_blank" rel="noopener" wire:click="requestVerification" class="flex min-h-12.5 items-center justify-center gap-2 rounded-full bg-[#25d366] text-[17px] font-medium text-white hover:bg-[#1ebe5b]">
                            <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4a.5.5 0 0 0 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.4.8 3.2.6a2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z" /></svg>
                            {{ $creditState === 'requested' ? 'Chat admin lagi' : 'Verifikasi via WhatsApp' }}
                        </a>
                    @else
                        <p class="text-sm text-muted">Nomor WhatsApp admin belum diatur. Coba lagi nanti.</p>
                    @endif
                @endif

                @error('credit') <p role="alert" class="rounded-2xl bg-bad-soft px-4 py-3 text-sm text-bad">{{ $message }}</p> @enderror
            </section>

            @if (filled($terms->terms))
                <details class="rounded-[18px] border border-line px-4 py-3 text-sm text-ink-2">
                    <summary class="cursor-pointer font-semibold text-ink">Syarat kredit</summary>
                    <p class="mt-2 leading-relaxed whitespace-pre-line">{{ $terms->terms }}</p>
                </details>
            @endif
        </form>
    </div>
</div>
