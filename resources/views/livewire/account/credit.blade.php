<div>
    <x-store.account-shell title="Kredit saya">
        @php($yen = \App\Enums\Currency::Jpy)

        @if ($submittedCode)
            <p role="status" class="mb-4 rounded-[18px] bg-good-soft px-4 py-4 text-[15px] leading-relaxed text-good">Pengajuan <strong class="font-semibold">{{ $submittedCode }}</strong> terkirim. Admin akan memeriksanya dan menghubungimu lewat WhatsApp.</p>
        @endif

        @error('credit') <p role="alert" class="mb-4 rounded-2xl bg-bad-soft px-4 py-3 text-sm text-bad">{{ $message }}</p> @enderror

        {{-- Verification --}}
        <section aria-labelledby="verify-h" class="rounded-[20px] bg-mist p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="verify-h" class="text-[17px] font-semibold">Verifikasi kredit</h2>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $user->credit_status->badgeClasses() }}">{{ $user->credit_status->getLabel() }}</span>
            </div>

            @if ($user->canBuyOnCredit())
                <p class="mt-2 text-[15px] leading-relaxed text-ink-2">Akunmu bisa membeli HP dengan kredit.@if ($remainingLimit !== null) Sisa batas kredit: <strong class="font-semibold">{{ $yen->format($remainingLimit) }}</strong> dari {{ $yen->format($user->credit_limit) }}.@endif</p>
                <a href="{{ route('catalog', ['kategori' => 'iphone']) }}" wire:navigate class="mt-4 inline-flex min-h-11 items-center rounded-full bg-action px-5 text-[15px] text-white hover:bg-[#0077ed]">Pilih HP</a>
            @else
                <p class="mt-2 text-[15px] leading-relaxed text-ink-2">
                    @if ($user->credit_status === \App\Enums\CreditVerification::Diajukan)
                        Permintaanmu sudah masuk. Admin akan membalas lewat WhatsApp dan memverifikasi akunmu.
                    @elseif ($user->credit_status === \App\Enums\CreditVerification::Ditolak)
                        Verifikasi belum disetujui.@if ($user->credit_note) Alasan: {{ $user->credit_note }}.@endif Hubungi admin kalau ingin melengkapi data.
                    @else
                        Untuk membeli HP dengan kredit, akunmu perlu diverifikasi admin. Caranya cukup kirim pesan lewat WhatsApp.
                    @endif
                </p>
                @if ($verificationUrl)
                    <a href="{{ $verificationUrl }}" target="_blank" rel="noopener" wire:click="requestVerification" class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-full bg-[#25d366] px-5 text-[15px] font-medium text-white hover:bg-[#1ebe5b]">Verifikasi via WhatsApp</a>
                @else
                    <p class="mt-3 text-sm text-muted">Nomor WhatsApp admin belum diatur. Coba lagi nanti.</p>
                @endif
                @if (blank($user->phone))
                    <p class="mt-3 text-sm text-warn">Isi juga nomor HP di <a href="{{ route('account.profile') }}" wire:navigate class="font-semibold underline">profil</a>, karena admin menghubungimu lewat nomor itu.</p>
                @endif
            @endif
        </section>

        {{-- Credit purchases --}}
        <h2 class="mt-8 mb-3 text-[19px] font-semibold">Pengajuan & cicilan</h2>

        @forelse ($applications as $application)
            <article wire:key="credit-{{ $application->id }}" class="mb-3 rounded-[20px] border border-line p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[13px] text-muted">{{ $application->code }} · {{ $application->created_at->setTimezone('Asia/Tokyo')->locale('id')->translatedFormat('j M Y') }}</p>
                        <h3 class="mt-0.5 text-[17px] font-semibold">{{ $application->item_name }}</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $application->status->badgeClasses() }}">{{ $application->status->getLabel() }}</span>
                </div>

                <p class="mt-2 text-sm text-ink-2">{{ $application->status->description() }}@if ($application->admin_note && in_array($application->status, [\App\Enums\CreditApplicationStatus::Ditolak, \App\Enums\CreditApplicationStatus::Dibatalkan], true)) Catatan: {{ $application->admin_note }}@endif</p>

                <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:grid-cols-4">
                    <div><dt class="text-muted">Harga</dt><dd class="font-medium tabular-nums">{{ $application->money($application->cash_price) }}</dd></div>
                    <div><dt class="text-muted">DP</dt><dd class="font-medium tabular-nums">{{ $application->money($application->down_payment) }}</dd></div>
                    <div><dt class="text-muted">Cicilan</dt><dd class="font-medium tabular-nums">{{ $application->money($application->monthly_installment) }} × {{ $application->tenor_months }}</dd></div>
                    <div><dt class="text-muted">Total</dt><dd class="font-medium tabular-nums">{{ $application->money($application->total_payable) }}</dd></div>
                </dl>

                @if ($application->installments->isNotEmpty())
                    @php($paid = $application->installments->whereNotNull('paid_at')->sum('amount'))
                    <div class="mt-4">
                        <div class="flex justify-between text-[13px] text-muted"><span>Sudah dibayar {{ $application->money($paid) }}</span><span>Sisa {{ $application->money($application->installments->sum('amount') - $paid) }}</span></div>
                        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-fog" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) round($paid / max(1, $application->installments->sum('amount')) * 100) }}">
                            <div class="h-full rounded-full bg-good" style="width: {{ round($paid / max(1, $application->installments->sum('amount')) * 100, 1) }}%"></div>
                        </div>
                    </div>

                    <details class="mt-3" @if (in_array($application->status, [\App\Enums\CreditApplicationStatus::Disetujui, \App\Enums\CreditApplicationStatus::Berjalan], true)) open @endif>
                        <summary class="cursor-pointer text-sm font-semibold text-link">Jadwal pembayaran</summary>
                        <table class="mt-2 w-full text-sm">
                            <thead class="text-left text-[13px] text-muted">
                                <tr><th class="py-1.5 font-medium">Pembayaran</th><th class="py-1.5 font-medium">Jatuh tempo</th><th class="py-1.5 text-right font-medium">Jumlah</th><th class="py-1.5 text-right font-medium">Status</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($application->installments as $installment)
                                    <tr wire:key="inst-{{ $installment->id }}" class="border-t border-line">
                                        <td class="py-2">{{ $installment->label() }}</td>
                                        <td class="py-2 tabular-nums">{{ $installment->due_date->locale('id')->translatedFormat('j M Y') }}</td>
                                        <td class="py-2 text-right tabular-nums">{{ $application->money($installment->amount) }}</td>
                                        <td class="py-2 text-right">
                                            @if ($installment->isPaid())
                                                <span class="text-good">Lunas</span>
                                            @elseif ($installment->isOverdue())
                                                <span class="font-semibold text-bad">Terlambat</span>
                                            @else
                                                <span class="text-muted">Belum</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p class="mt-2 text-xs text-muted">Bayar lewat transfer ke rekening toko, lalu kirim bukti ke admin lewat WhatsApp. Admin mencatat pembayaranmu di sini.</p>
                    </details>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-4">
                    @if ($contactUrl)
                        <a href="{{ $contactUrl }}?text={{ rawurlencode('Halo admin, saya mau tanya kredit '.$application->code.'.') }}" target="_blank" rel="noopener" class="text-sm text-link hover:underline">Tanya admin via WhatsApp</a>
                    @endif
                    @if ($canCancel($application))
                        <button type="button" x-on:click="$store.confirm.ask({ title: 'Batalkan pengajuan?', message: @js('Pengajuan kredit '.$application->code.' akan dibatalkan.'), confirmLabel: 'Batalkan pengajuan', cancelLabel: 'Tidak', onConfirm: () => $wire.cancel({{ $application->id }}) })" class="cursor-pointer text-sm text-bad hover:underline">Batalkan pengajuan</button>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-tile bg-mist px-6 py-12 text-center">
                <p class="text-[17px] font-semibold">Belum ada pengajuan kredit.</p>
                <p class="mt-1 text-[15px] text-muted">iPhone 16 sampai 18 Pro Max bisa dicicil setelah akunmu diverifikasi.</p>
                <a href="{{ route('catalog', ['kategori' => 'iphone']) }}" wire:navigate class="mt-5 inline-flex min-h-11 items-center rounded-full bg-action px-5.5 text-[17px] text-white">Lihat iPhone</a>
            </div>
        @endforelse
    </x-store.account-shell>
</div>
