<div class="flex flex-col gap-3">
    @if ($order === null)
        <p role="alert" class="rounded-[18px] bg-bad-soft px-4 py-4 text-[15px] text-bad">Order tidak ditemukan. Muat ulang halaman ini.</p>
    @elseif ($proofUploaded || $status === \App\Enums\OrderStatus::MenungguVerifikasi)
        <p role="status" class="rounded-[18px] bg-good-soft px-4 py-4 text-[15px] leading-relaxed text-good">Bukti transfer terkirim. Admin akan memverifikasi, lalu pesananmu masuk batch preorder yang sedang dibuka.</p>
    @elseif ($status === \App\Enums\OrderStatus::MenungguKonfirmasi)
        <p class="rounded-[18px] bg-confirm-soft px-4 py-4 text-[15px] leading-relaxed text-confirm">{{ $status->description() }} Simpan kode order ini untuk melacak pesananmu.</p>
    @elseif ($status === \App\Enums\OrderStatus::MenungguPembayaran)
        @if ($overdue)
            <div role="alert" class="rounded-[18px] bg-warn-soft px-4 py-4 text-[15px] leading-relaxed text-warn">
                Batas waktu pembayaran ({{ $paymentDue }}) sudah lewat, jadi order ini akan dibatalkan otomatis.
                @if ($whatsappUrl)
                    Kalau kamu sudah transfer, <a href="{{ $whatsappUrl }}" class="font-semibold underline">hubungi admin via WhatsApp</a>.
                @endif
            </div>
        @else
            @if ($showDue && $paymentDue)
                <p class="text-center text-sm text-ink-2">Bayar sebelum <strong class="font-semibold">{{ $paymentDue }}</strong></p>
            @endif

            @if ($rejection)
                <p role="alert" class="rounded-[18px] bg-bad-soft px-4 py-3.5 text-[15px] leading-relaxed text-bad"><strong class="font-semibold">Bukti sebelumnya ditolak:</strong> {{ $rejection }}. Upload bukti yang benar di bawah.</p>
            @endif

            <section aria-label="Rekening tujuan" class="rounded-[18px] bg-mist px-4 py-1.5">
                @foreach (['Bank' => $account?->bank_name ?? '-', 'No. rekening' => $account?->account_number ?? 'Akan dikirim admin via WhatsApp', 'Atas nama' => $account?->account_holder ?? '-', 'Jumlah tepat' => $order->formattedTotal()] as $label => $value)
                    <div x-data="{ copied: false, failed: false }" @class(['flex min-h-13 items-center justify-between gap-2', 'border-b border-line' => ! $loop->last])>
                        <span class="text-sm text-muted">{{ $label }}</span>
                        <span class="flex items-center gap-1">
                            <span class="text-right text-[15px] font-medium tabular-nums">{{ $value }}</span>
                            @if (in_array($label, ['No. rekening', 'Jumlah tepat'], true) && ($label !== 'No. rekening' || $account?->account_number))
                                <button
                                    type="button"
                                    x-on:click="navigator.clipboard.writeText(@js(preg_replace('/[^0-9]/', '', $value) ?: $value)).then(() => { copied = true; setTimeout(() => copied = false, 1500) }, () => { failed = true; setTimeout(() => failed = false, 2500) })"
                                    aria-label="Salin {{ mb_strtolower($label) }}"
                                    class="flex size-11 cursor-pointer items-center justify-center text-link"
                                >
                                    <svg x-show="! copied && ! failed" class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2" /><path d="M5 15V5a2 2 0 0 1 2-2h10" /></svg>
                                    <svg x-cloak x-show="copied" class="size-4.5 text-good" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5" /></svg>
                                    <span x-cloak x-show="failed" class="text-xs text-bad">Salin manual</span>
                                </button>
                            @endif
                        </span>
                    </div>
                @endforeach
            </section>

            <form
                wire:submit="uploadProof"
                x-data="{ uploadError: '', progress: 0 }"
                x-on:livewire-upload-start="uploadError = ''; progress = 0"
                x-on:livewire-upload-progress="progress = $event.detail.progress"
                x-on:livewire-upload-error="uploadError = navigator.onLine ? 'Bukti gagal diunggah. Coba pilih ulang filenya.' : 'Kamu sedang offline. Sambungkan internet lalu pilih ulang filenya.'"
                class="flex flex-col gap-3 rounded-[18px] bg-mist p-4"
            >
                <span class="text-[15px] font-semibold">Bukti transfer</span>
                <label class="flex cursor-pointer flex-col items-center gap-1.5 rounded-2xl border-[1.5px] border-dashed border-[#b0b0b8] bg-white px-4 py-6 text-center hover:border-action">
                    <svg class="size-7 text-link" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5" /><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3" /></svg>
                    <span class="text-[15px] font-medium text-link">{{ $proof ? $proof->getClientOriginalName() : 'Pilih foto / screenshot' }}</span>
                    <span class="text-xs text-muted">JPG, PNG, atau PDF · maks. {{ $maxMb }} MB</span>
                    <input type="file" wire:model="proof" accept="image/jpeg,image/png,application/pdf" class="sr-only">
                </label>
                <div wire:loading wire:target="proof" class="h-1.5 overflow-hidden rounded-full bg-fog" role="progressbar" aria-label="Mengunggah bukti" x-bind:aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-action transition-[width]" x-bind:style="'width: ' + progress + '%'"></div>
                </div>
                <p x-cloak x-show="uploadError" x-text="uploadError" role="alert" class="text-sm text-bad"></p>
                @error('proof') <p role="alert" class="text-sm text-bad">{{ $message }}</p> @enderror
                <button type="submit" wire:loading.attr="disabled" wire:target="proof,uploadProof" class="min-h-12.5 cursor-pointer rounded-full bg-action text-[17px] font-medium text-white hover:bg-[#0077ed] disabled:cursor-not-allowed disabled:bg-fog disabled:text-muted">
                    <span wire:loading.remove wire:target="uploadProof">Kirim bukti</span>
                    <span wire:loading wire:target="uploadProof">Mengirim…</span>
                </button>
            </form>
        @endif
    @endif
</div>
