<div>
    <x-store.tear-reveal />

    <section aria-label="Batch preorder minggu ini" class="bg-paper px-4 pt-14 pb-18 text-center sm:px-6">
        <p class="inline-flex items-center gap-2 rounded-full bg-good-soft px-3.5 py-1.5 text-[13px] font-semibold text-good">
            <span class="size-2 rounded-full bg-good"></span>
            Batch {{ $batch->code }} sedang dibuka
        </p>
        <h1 class="mx-auto mt-4 max-w-3xl text-4xl leading-[1.07] font-bold tracking-tight sm:text-[56px]">Casing custom dari gambarmu, dikirim gratis ke rumahmu di Jepang.</h1>
        <p class="mx-auto mt-4 max-w-2xl text-lg text-muted sm:text-[21px]">Batch ditutup {{ $cutoffLabel }}. Estimasi tiba {{ $etaLabel }}.</p>
        <div class="mt-7 flex flex-wrap items-center justify-center gap-6">
            <a href="{{ route('custom') }}" wire:navigate class="inline-flex min-h-12 items-center rounded-full bg-action px-6.5 text-[17px] font-medium text-white hover:bg-[#0077ed]">Buat casing kamu</a>
            <a href="{{ route('catalog') }}" wire:navigate class="text-[17px] text-link hover:underline">Lihat katalog ›</a>
        </div>
    </section>

    <section id="custom" aria-labelledby="custom-h" class="bg-mist px-4 py-24 sm:px-6">
        <div class="mx-auto grid max-w-5xl items-center gap-14 md:grid-cols-2">
            <div>
                <p class="text-[17px] font-semibold text-eyebrow">Custom Casing</p>
                <h2 id="custom-h" class="mt-2 text-4xl leading-[1.07] font-bold tracking-tight sm:text-[56px]">Gambarmu.<br>Di casingmu.</h2>
                <p class="mt-4 text-lg leading-relaxed text-muted sm:text-[19px]">Upload gambar, atur posisi, dan lihat preview 2D sebelum dicetak. Tim kami mengecek setiap desain sebelum masuk produksi.</p>
                <ul class="mt-6 flex flex-col gap-3.5 text-[17px]">
                    <li class="flex items-center gap-3"><svg class="size-5.5 text-action" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3" /><path d="M10 6h4" /></svg>Preview 2D: geser, zoom, putar</li>
                    <li class="flex items-center gap-3"><svg class="size-5.5 text-action" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2" /></svg>Atur posisi, zoom dan rotasi sendiri</li>
                    <li class="flex items-center gap-3"><svg class="size-5.5 text-action" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5" /></svg>Desain dicek tim sebelum produksi</li>
                </ul>
                <div class="mt-8 flex flex-wrap items-center gap-6">
                    <a href="{{ route('custom') }}" wire:navigate class="inline-flex min-h-11 items-center rounded-full bg-action px-5.5 text-[17px] text-white hover:bg-[#0077ed]">Mulai desain</a>
                    @if ($customPrice)
                        <span class="text-[17px]">Mulai {{ $customPrice }}</span>
                    @endif
                </div>
            </div>
            <div class="flex justify-center py-6">
                <x-store.hero-phone :image="asset('images/hero-case-design.jpg')" class="w-[254px]" />
            </div>
        </div>
    </section>

    <section aria-label="Tipe HP dan pembayaran" class="bg-paper px-4 pt-6 pb-24 sm:px-6">
        <div class="mx-auto grid max-w-5xl gap-5 md:grid-cols-2">
            <div class="flex flex-col gap-6 rounded-tile bg-mist px-6 py-10 sm:px-10">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight sm:text-[40px]">Tipe HP apa pun.</h2>
                    <p class="mt-2.5 text-[17px] leading-relaxed text-muted">iPhone dan Samsung punya preview 2D. Tipe lain tetap bisa dipesan, dikonfirmasi tim sebelum kamu bayar.</p>
                </div>
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm" aria-hidden="true">
                    <div class="flex h-13 items-center gap-2.5 border-b border-fog px-4 text-[17px]">
                        <svg class="size-4.5 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
                        iPhone 17
                    </div>
                    <div class="p-1.5 text-[15px]">
                        <div class="flex items-center justify-between rounded-xl bg-mist p-3">iPhone 17 Pro Max <span class="text-xs font-semibold text-link">Preview tersedia</span></div>
                        <div class="flex items-center justify-between p-3">iPhone 17 Pro <span class="text-xs font-semibold text-link">Preview tersedia</span></div>
                        <div class="flex items-center justify-between border-t border-fog p-3 text-muted">Tidak ketemu? Ketik manual <span class="text-xs font-semibold">Tanpa preview</span></div>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-6 rounded-tile bg-black px-6 py-10 text-mist sm:px-10">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight sm:text-[40px]">Bayar Yen atau Rupiah.</h2>
                    <p class="mt-2.5 text-[17px] leading-relaxed text-[#a1a1a6]">Pilih mata uang sekali di menu atas, semua harga langsung menyesuaikan.</p>
                </div>
                <div class="flex flex-col gap-3">
                    <div class="flex items-center gap-4 rounded-2xl bg-ink p-4.5">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-[#2c2c2e] text-xl font-bold">¥</span>
                        <span class="flex flex-col"><span class="text-[17px] font-semibold">Transfer bank Jepang</span><span class="text-sm text-[#a1a1a6]">Upload bukti, diverifikasi admin</span></span>
                    </div>
                    <div class="flex items-center gap-4 rounded-2xl bg-ink p-4.5">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-[#2c2c2e] text-[15px] font-bold">Rp</span>
                        <span class="flex flex-col"><span class="text-[17px] font-semibold">Transfer BCA</span><span class="text-sm text-[#a1a1a6]">QRIS &amp; e-wallet menyusul</span></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="preorder" aria-labelledby="po-h" class="bg-mist px-4 py-24 sm:px-6">
        <div class="mx-auto max-w-5xl">
            <h2 id="po-h" class="text-center text-3xl font-bold tracking-tight sm:text-5xl">Preorder {{ \App\Support\BatchSchedule::periodLabel() }}, tanpa ribet.</h2>
            <p class="mx-auto mt-3 max-w-xl text-center text-lg text-muted sm:text-[19px]">Status pesananmu bisa dicek kapan saja, dari dibayar sampai diantar kurir.</p>
            <ol class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Pesan & bayar', 'Order yang sudah dibayar langsung masuk batch preorder bulan ini.'],
                    ['Cutoff batch '.\App\Support\BatchSchedule::periodLabel(), $cutoffLabel.'. Desain dicek tim sebelum dicetak.'],
                    ['Dicetak di Indonesia', 'Lalu dikirim bersama-sama ke Jepang.'],
                    ['Gratis ongkir', 'Dikemas satu per satu dengan cek ulang, lalu dikirim ke alamatmu.'],
                ] as [$stepTitle, $stepText])
                    <li class="rounded-[22px] bg-white px-6 py-7">
                        <span class="text-[15px] font-semibold text-link">{{ $loop->iteration }}</span>
                        <h3 class="mt-2.5 mb-1.5 text-[21px] font-semibold">{{ $stepTitle }}</h3>
                        <p class="text-[15px] leading-relaxed text-muted">{{ $stepText }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section aria-labelledby="kat-h" class="bg-paper px-4 py-24 sm:px-6">
        <div class="mx-auto max-w-5xl">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 id="kat-h" class="text-3xl font-bold tracking-tight sm:text-5xl">Bukan cuma casing.</h2>
                <a href="{{ route('catalog') }}" wire:navigate class="text-[17px] text-link hover:underline">Semua produk ›</a>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3">
                <a href="{{ route('custom') }}" wire:navigate class="flex min-h-37 flex-col justify-between rounded-[22px] bg-mist p-6 text-ink hover:bg-fog">
                    <span class="text-[21px] font-semibold">Casing Custom</span>
                    <span class="text-sm text-link">Desain sendiri ›</span>
                </a>
                @foreach ($categories as $slug => $name)
                    <a href="{{ route('catalog', ['kategori' => $slug]) }}" wire:navigate class="flex min-h-37 flex-col justify-between rounded-[22px] bg-mist p-6 text-ink hover:bg-fog">
                        <span class="text-[21px] font-semibold">{{ $name }}</span>
                        <span class="text-sm text-link">Lihat ›</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section aria-labelledby="lacak-h" class="bg-mist px-4 py-24 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 id="lacak-h" class="text-3xl font-bold tracking-tight sm:text-5xl">Pesananmu sampai mana?</h2>
            <p class="mt-3 text-lg text-muted sm:text-[19px]">Masukkan kode order dan 4 digit terakhir nomor HP kamu.</p>
            <div class="mt-8">
                <livewire:store.track-form />
            </div>
        </div>
    </section>
</div>
