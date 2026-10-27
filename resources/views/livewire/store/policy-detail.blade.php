<div x-data="policyGuide(@js($guideState))" wire:key="policy-guide-{{ $topic }}" class="mx-auto max-w-6xl px-4 pb-24 sm:px-6">
    <nav aria-label="Jejak halaman" class="flex min-h-16 flex-wrap items-center gap-2 text-xs text-muted sm:gap-3">
        <a href="{{ route('home') }}" wire:navigate class="hover:text-link">Lahiya</a>
        <x-store.policy-icon name="chevron" class="size-3 -rotate-90" />
        <a href="{{ route('policy') }}" wire:navigate class="hover:text-link">Panduan belanja</a>
        <x-store.policy-icon name="chevron" class="size-3 -rotate-90" />
        <span aria-current="page" class="font-medium text-ink">{{ $metadata['title'] }}</span>
    </nav>

    <header class="grid items-center gap-8 py-7 sm:py-12 lg:grid-cols-[1.2fr_1fr] lg:gap-14">
        <div>
            <p class="flex items-center gap-2 text-xs font-semibold tracking-widest text-link"><x-store.policy-icon :name="$topic" class="size-4" /> PANDUAN {{ mb_strtoupper($metadata['title']) }}</p>
            <h1 class="mt-5 text-4xl leading-[1.08] font-bold tracking-tight sm:text-5xl lg:text-6xl">{{ $metadata['heading'] }}<br><span class="text-muted">{{ $metadata['accent'] }}</span></h1>
            <p class="mt-5 max-w-xl text-base leading-relaxed text-muted sm:text-lg">{{ $metadata['intro'] }}</p>
            <a href="#panduan" class="mt-6 inline-flex min-h-11 items-center gap-2 text-sm font-medium text-link hover:underline">Jelajahi panduannya <x-store.policy-icon name="arrow" class="size-4 rotate-90" /></a>
        </div>
        <div class="relative flex min-h-64 items-center justify-center overflow-hidden rounded-[32px] bg-linear-to-br from-info-soft via-mist to-white p-7 sm:min-h-80">
            <div aria-hidden="true" class="absolute -top-16 -right-10 size-56 rounded-full border border-action/10"></div>
            <div aria-hidden="true" class="absolute -bottom-20 -left-12 size-64 rounded-full border border-action/10"></div>
            <div class="relative w-full max-w-sm rounded-card border border-white bg-white p-6 shadow-lg shadow-info/5 sm:p-8">
                <div class="flex items-center justify-between">
                    <span class="flex size-12 items-center justify-center rounded-2xl bg-info-soft text-link"><x-store.policy-icon :name="$topic" class="size-7" /></span>
                    <span class="text-xs font-medium text-muted">Lahiya guide</span>
                </div>
                @switch($topic)
                    @case('preorder')
                        <p class="mt-6 text-xs text-muted">Estimasi perjalanan setelah cutoff</p>
                        <p class="mt-1 text-4xl font-semibold tracking-tight">{{ $limits['etaMin'] }}–{{ $limits['etaMax'] }} <span class="text-lg font-normal text-muted">hari</span></p>
                        <p class="mt-5 border-t border-fog pt-4 text-sm text-ink-2">Indonesia <span class="mx-2 text-link" aria-hidden="true">→</span> Jepang <span class="mx-2 text-link" aria-hidden="true">→</span> Rumahmu</p>
                        @break
                    @case('payment')
                        <p class="mt-6 text-xs text-muted">Batas waktu pembayaran</p>
                        <p class="mt-1 text-4xl font-semibold tracking-tight">{{ $limits['paymentHours'] }} <span class="text-lg font-normal text-muted">jam</span></p>
                        <div class="mt-5 flex gap-2 border-t border-fog pt-4"><span class="rounded-full bg-mist px-3 py-1.5 text-xs font-semibold">¥ JPY</span><span class="rounded-full bg-mist px-3 py-1.5 text-xs font-semibold">Rp IDR</span><span class="self-center text-xs text-muted">Transfer bank</span></div>
                        @break
                    @case('cancellation')
                        <p class="mt-6 text-xs text-muted">Periksa sebelum mengajukan</p>
                        <p class="mt-2 text-3xl font-semibold leading-tight tracking-tight">Belum masuk<br>produksi?</p>
                        <p class="mt-5 border-t border-fog pt-4 text-sm text-ink-2">Lihat pilihan berdasarkan status pesanan.</p>
                        @break
                    @case('complaints')
                        <p class="mt-6 text-xs text-muted">Batas setelah paket diterima</p>
                        <p class="mt-1 text-4xl font-semibold tracking-tight">{{ $limits['receivedDays'] }} <span class="text-lg font-normal text-muted">hari</span></p>
                        <p class="mt-5 border-t border-fog pt-4 text-sm text-ink-2">Paling lambat {{ $limits['shippedDays'] }} hari setelah dikirim.</p>
                        @break
                @endswitch
            </div>
        </div>
    </header>

    <nav aria-label="Topik panduan" class="mt-4 flex gap-2 overflow-x-auto border-b border-fog pb-5">
        @foreach ($topics as $key => $item)
            <a href="{{ route('policy.show', ['topic' => $item['slug']]) }}" wire:navigate @if ($key === $topic) aria-current="page" @endif
                @class(['flex min-h-11 shrink-0 items-center gap-2 rounded-full px-4 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-action', 'bg-ink text-white' => $key === $topic, 'bg-mist text-ink-2 hover:bg-fog' => $key !== $topic])>
                <x-store.policy-icon :name="$key" class="size-4" />{{ $item['title'] }}
            </a>
        @endforeach
    </nav>

    <div id="panduan" class="mt-10 grid scroll-mt-20 gap-10 lg:grid-cols-[180px_minmax(0,1fr)] lg:gap-12">
        <aside class="hidden lg:block">
            <nav aria-label="Isi halaman" class="sticky top-24 flex flex-col gap-1 border-l border-fog pl-4 text-sm">
                <p class="mb-3 text-xs font-semibold tracking-wide text-muted">DI HALAMAN INI</p>
                <a href="#cara" class="min-h-10 py-2 text-ink-2 hover:text-link">Panduan praktis</a>
                <a href="#persiapan" class="min-h-10 py-2 text-ink-2 hover:text-link">Yang perlu disiapkan</a>
                <a href="#aturan" class="min-h-10 py-2 text-ink-2 hover:text-link">Kebijakan toko</a>
                <a href="#faq" class="min-h-10 py-2 text-ink-2 hover:text-link">Pertanyaan umum</a>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-col gap-10 sm:gap-12">
            <section id="cara" class="scroll-mt-24">
                @switch($topic)
                    @case('preorder')
                        @php($steps = [
                            ['Pesan', 'Mulai dari pilihanmu.', 'Pilih produk atau desain casing, lalu isi alamat pengiriman di Jepang. Simpan kode order untuk melacak pesanan.'],
                            ['Bayar', 'Pembayaran membuka perjalanan.', 'Transfer sesuai mata uang order dan upload bukti. Order masuk batch aktif setelah pembayaran diterima tim. Tipe HP tanpa preview perlu dikonfirmasi terlebih dahulu.'],
                            ['Produksi', 'Gambarmu jadi casing.', 'Setelah batch ditutup, desain diperiksa sebelum masuk produksi. Pada tahap produksi, pesanan sudah tidak bisa dibatalkan.'],
                            ['Ke Jepang', 'Dikirim bersama satu batch.', 'Pesanan dalam batch dikirim dari Indonesia ke Jepang. Estimasi tiba dapat diperbarui sesuai perjalanan pengiriman.'],
                            ['Packing', 'Diperiksa sekali lagi.', 'Tim mencocokkan item dan desain, lalu mengemas pesanan sesuai alamat penerima.'],
                            ['Sampai', 'Menuju alamatmu.', 'Paket dikirim melalui kurir domestik Jepang. Resi dan perubahan status bisa dilihat di halaman lacak order.'],
                        ])
                        <p class="text-xs font-semibold tracking-wide text-link">01 · PAHAMI ALURNYA</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Satu pesanan. Enam tahap.</h2>
                        <p class="mt-3 text-sm leading-relaxed text-muted">Pilih tahap untuk melihat apa yang terjadi pada pesananmu.</p>
                        <ol class="mt-6 grid grid-cols-3 gap-2 sm:grid-cols-6" aria-label="Tahap preorder">
                            @foreach ($steps as $index => [$label, $heading, $body])
                                <li><button type="button" @click="stage = {{ $index }}" :aria-pressed="stage === {{ $index }}" aria-controls="preorder-step-{{ $index }}" :class="stage === {{ $index }} ? 'border-action bg-info-soft text-link' : 'border-fog bg-white text-muted'" class="flex min-h-20 w-full flex-col items-start gap-3 rounded-2xl border p-3 text-left text-sm focus-visible:outline-2 focus-visible:outline-action"><span class="text-xs">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span><span class="font-medium">{{ $label }}</span></button></li>
                            @endforeach
                        </ol>
                        <div class="mt-4 rounded-tile bg-mist p-6 sm:p-8">
                            @foreach ($steps as $index => [$label, $heading, $body])
                                <div id="preorder-step-{{ $index }}" x-show="stage === {{ $index }}" @if ($index !== 0) x-cloak @endif role="region" aria-label="{{ $label }}">
                                    <span class="text-xs font-semibold text-link">TAHAP {{ $index + 1 }} DARI 6</span>
                                    <h3 class="mt-3 text-2xl font-semibold tracking-tight">{{ $heading }}</h3>
                                    <p class="mt-3 max-w-2xl text-base leading-relaxed text-ink-2">{{ $body }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-card border border-fog p-5"><p class="text-xs text-muted">Cutoff batch {{ $batch->code }}</p><p class="mt-2 text-base font-semibold">{{ $cutoffLabel }}</p></div>
                            <div class="rounded-card border border-fog p-5"><p class="text-xs text-muted">Estimasi tiba di Jepang</p><p class="mt-2 text-base font-semibold">{{ $etaLabel }}</p><p class="mt-2 text-xs text-muted">Estimasi dapat berubah mengikuti proses dan pengiriman.</p></div>
                        </div>
                        @break

                    @case('payment')
                        <p class="text-xs font-semibold tracking-wide text-link">01 · PILIH CARA BAYARMU</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Rekening sesuai mata uang.</h2>
                        <p class="mt-3 text-sm leading-relaxed text-muted">Pilihan di panduan ini hanya untuk melihat informasi. Rekening dan jumlah yang harus dibayar tetap mengikuti order di checkout.</p>
                        <div class="mt-5 inline-flex rounded-full bg-mist p-1" role="group" aria-label="Lihat rekening berdasarkan mata uang">
                            @foreach (['JPY' => '¥ Yen', 'IDR' => 'Rp Rupiah'] as $code => $label)
                                <button type="button" @click="currency = @js($code)" :aria-pressed="currency === @js($code)" :class="currency === @js($code) ? 'bg-white text-ink shadow-sm' : 'text-muted'" class="min-h-11 rounded-full px-6 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-action">{{ $label }}</button>
                            @endforeach
                        </div>
                        @foreach ($accounts as $code => $account)
                            <div x-show="currency === @js($code)" @if ($code !== $guideState['currency']) x-cloak @endif class="mt-5 overflow-hidden rounded-tile border border-fog bg-white">
                                <div class="flex items-center justify-between gap-3 bg-mist px-6 py-5"><h3 class="text-lg font-semibold">{{ $code === 'JPY' ? 'Transfer bank Jepang' : 'Transfer Rupiah' }}</h3><span class="rounded-full bg-white px-3 py-1 text-xs font-semibold">{{ $code }}</span></div>
                                @if ($account)
                                    <dl class="divide-y divide-fog px-6">
                                        @foreach (['Bank' => $account->bank_name, 'Nomor rekening' => $account->account_number, 'Atas nama' => $account->account_holder] as $label => $value)
                                            <div class="flex flex-wrap items-center justify-between gap-2 py-4"><dt class="text-sm text-muted">{{ $label }}</dt><dd class="text-right text-sm font-semibold">{{ $value ?: 'Konfirmasi melalui tim toko' }}</dd></div>
                                        @endforeach
                                    </dl>
                                    @if ($account->account_number)
                                        <button type="button" @click="copy(@js($account->account_number), @js($code))" class="mx-6 mb-5 inline-flex min-h-11 items-center gap-2 rounded-xl bg-info-soft px-4 text-sm font-medium text-link"><x-store.policy-icon name="copy" class="size-4" /><span x-text="copied === @js($code) ? 'Nomor rekening tersalin' : 'Salin nomor rekening'">Salin nomor rekening</span></button>
                                    @endif
                                @else
                                    <p class="p-6 text-sm leading-relaxed text-muted">Rekening {{ $code }} belum ditampilkan. Periksa informasi pada checkout atau hubungi tim sebelum melakukan transfer.</p>
                                @endif
                            </div>
                        @endforeach
                        <div x-cloak x-show="copyFailed" class="mt-3 rounded-xl bg-mist p-4"><label for="manual-account-copy" class="text-sm text-muted">Salin nomor ini secara manual:</label><input id="manual-account-copy" :value="copyValue" readonly @focus="$el.select()" class="mt-2 min-h-11 w-full rounded-lg border border-line bg-white px-3 text-sm"></div>
                        <ol class="mt-6 grid gap-4 sm:grid-cols-3">
                            @foreach ([
                                ['Transfer', 'Bayar jumlah tepat yang tertera pada order, ke rekening dengan mata uang yang sama.'],
                                ['Upload bukti', 'Unggah JPG, PNG, atau PDF maksimal '.$limits['proofMb'].' MB dari halaman pembayaran order.'],
                                ['Tunggu verifikasi', 'Bukti diperiksa tim. Pesanan masuk batch setelah pembayaran diterima.'],
                            ] as [$heading, $body])
                                <li class="rounded-card border border-fog p-5"><span class="text-xs font-semibold text-link">0{{ $loop->iteration }}</span><h3 class="mt-3 font-semibold">{{ $heading }}</h3><p class="mt-2 text-sm leading-relaxed text-muted">{{ $body }}</p></li>
                            @endforeach
                        </ol>
                        <p class="mt-4 rounded-2xl bg-info-soft p-4 text-sm leading-relaxed text-info">Batas {{ $limits['paymentHours'] }} jam mulai berlaku ketika order menunggu pembayaran. Jika tipe HP perlu konfirmasi, tunggu tim menyatakan tersedia sebelum membayar.</p>
                        @break

                    @case('cancellation')
                        <p class="text-xs font-semibold tracking-wide text-link">01 · CEK STATUSNYA DULU</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Apa pilihan untuk pesananmu?</h2>
                        <p class="mt-3 text-sm leading-relaxed text-muted">Pilih status yang tertera di halaman lacak order untuk memahami aturan pembatalannya.</p>
                        <div class="mt-6 grid gap-4 sm:grid-cols-[1fr_1.4fr]">
                            <div class="rounded-card border border-fog p-6">
                                <label for="cancel-status" class="text-sm font-semibold">Status pesanan</label>
                                <select id="cancel-status" x-model="selectedStatus" class="mt-3 min-h-12 w-full rounded-xl border border-line bg-white px-3 text-sm focus:border-action focus:outline-action">
                                    @foreach ($guideState['statuses'] as $value => $status)
                                        <option value="{{ $value }}">{{ $status['label'] }}</option>
                                    @endforeach
                                </select>
                                <a href="{{ route('track') }}" wire:navigate class="mt-5 inline-flex min-h-11 items-center gap-2 text-sm text-link hover:underline">Lihat status order <x-store.policy-icon name="arrow" class="size-4" /></a>
                            </div>
                            <div :class="outcome.allowed ? 'bg-good-soft' : 'bg-mist'" class="rounded-card p-6 sm:p-8" role="status" aria-live="polite">
                                <span class="flex size-10 items-center justify-center rounded-full bg-white"><x-store.policy-icon name="cancellation" class="size-5" /></span>
                                <h3 x-text="outcome.heading" class="mt-4 text-xl font-semibold">Pembatalan dapat diajukan.</h3>
                                <p x-text="outcome.body" class="mt-3 text-sm leading-relaxed text-ink-2"></p>
                            </div>
                        </div>
                        <div class="mt-5 rounded-card border border-fog p-6"><h3 class="font-semibold">Cara mengajukan pembatalan</h3><ol class="mt-4 flex list-decimal flex-col gap-3 pl-5 text-sm leading-relaxed text-ink-2"><li>Siapkan kode order dan cek status terbaru.</li><li>Hubungi tim toko, jelaskan bahwa kamu ingin membatalkan pesanan.</li><li>Jika sudah dibayar, siapkan informasi rekening sesuai mata uang pembayaran.</li><li>Tim memeriksa kelayakan, mencatat keputusan, dan menginformasikan proses refund.</li></ol></div>
                        @break

                    @case('complaints')
                        <p class="text-xs font-semibold tracking-wide text-link">01 · CEK BATAS PENGAJUAN</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Masih bisa mengajukan komplain?</h2>
                        <p class="mt-3 text-sm leading-relaxed text-muted">Maksimal {{ $limits['receivedDays'] }} hari setelah diterima, paling lambat {{ $limits['shippedDays'] }} hari setelah dikirim. Kedua batas berlaku, dengan tanggal Jepang (JST).</p>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-5 rounded-card border border-fog p-6">
                                <div><label for="complaint-shipped" class="text-sm font-medium">Tanggal paket dikirim</label><input id="complaint-shipped" type="date" wire:model.live="shippedDate" max="{{ $today }}" class="mt-2 min-h-12 w-full rounded-xl border border-line bg-white px-3 text-sm focus:outline-action"></div>
                                <div><label for="complaint-received" class="text-sm font-medium">Tanggal paket diterima</label><input id="complaint-received" type="date" wire:model.live="receivedDate" max="{{ $today }}" class="mt-2 min-h-12 w-full rounded-xl border border-line bg-white px-3 text-sm focus:outline-action"></div>
                            </div>
                            @php($window = $this->complaintWindow)
                            <div @class(['rounded-card p-6 sm:p-8', 'bg-good-soft' => $window['state'] === 'within', 'bg-warn-soft' => in_array($window['state'], ['invalid', 'expired'], true), 'bg-mist' => $window['state'] === 'empty']) role="status" aria-live="polite" wire:loading.class="opacity-60" wire:target="shippedDate,receivedDate">
                                <x-store.policy-icon name="complaints" class="size-7" />
                                <h3 class="mt-4 text-xl font-semibold">{{ $window['heading'] }}</h3>
                                <p class="mt-3 text-sm leading-relaxed text-ink-2">{{ $window['body'] }}</p>
                                @if (isset($window['deadline']))<p class="mt-5 text-xs text-muted">Batas pengajuan</p><p class="mt-1 font-semibold">{{ $window['deadline'] }}</p>@endif
                            </div>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-muted">Pemeriksaan ini menunjukkan batas waktu. Jenis masalah dan bukti tetap diperiksa tim sebelum komplain disetujui.</p>
                        <h3 class="mt-8 text-xl font-semibold tracking-tight">Jika masalahnya ditanggung toko</h3>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2" role="group" aria-label="Pelajari pilihan penyelesaian">
                            <button type="button" @click="resolution = 'replacement'" :aria-pressed="resolution === 'replacement'" :class="resolution === 'replacement' ? 'border-action bg-info-soft' : 'border-fog bg-white'" class="rounded-card border p-5 text-left focus-visible:outline-action"><x-store.policy-icon name="preorder" class="size-6 text-link" /><span class="mt-3 block font-semibold">Cetak ulang gratis</span><span class="mt-1 block text-sm text-muted">Order penggantian di batch berikutnya.</span></button>
                            <button type="button" @click="resolution = 'refund'" :aria-pressed="resolution === 'refund'" :class="resolution === 'refund' ? 'border-action bg-info-soft' : 'border-fog bg-white'" class="rounded-card border p-5 text-left focus-visible:outline-action"><x-store.policy-icon name="payment" class="size-6 text-link" /><span class="mt-3 block font-semibold">Refund penuh</span><span class="mt-1 block text-sm text-muted">Dana dikembalikan setelah disetujui.</span></button>
                        </div>
                        <div class="mt-3 rounded-2xl bg-mist p-5 text-sm leading-relaxed text-ink-2" aria-live="polite">
                            <p x-show="resolution === 'replacement'">Tim membuat order penggantian gratis yang terhubung dengan order awal. Pesanan mengikuti proses review, produksi, dan pengiriman batch berikutnya.</p>
                            <p x-cloak x-show="resolution === 'refund'">Refund dikembalikan dalam mata uang dan jenis rekening yang sama dengan pembayaran, maksimal 7 hari kerja setelah disetujui. Siapkan informasi rekening untuk tim.</p>
                        </div>
                        @break
                @endswitch
            </section>

            <section id="persiapan" class="scroll-mt-24 rounded-tile bg-mist p-6 sm:p-8">
                @php($checklist = match ($topic) {
                    'preorder' => ['Tipe HP dan desain sudah benar.', 'Alamat penerima berada di Jepang dan sudah lengkap.', 'Jadwal batch serta estimasi tiba sudah dibaca.'],
                    'payment' => ['Mata uang rekening sama dengan order.', 'Jumlah transfer sama dengan total di order.', 'Bukti transfer menampilkan tanggal, tujuan, dan nominal dengan jelas.', 'Kode order disimpan untuk melacak status.'],
                    'cancellation' => ['Kode order dan status terakhir sudah dicek.', 'Alasan pembatalan siap disampaikan.', 'Jika sudah membayar, bukti bayar dan informasi rekening siap.'],
                    'complaints' => ['Kode order dan tanggal pengiriman tersedia.', 'Video unboxing menunjukkan kondisi saat paket dibuka.', 'Foto masalah pada produk terlihat jelas.', 'Penjelasan masalah dan tanggal diterima sudah disiapkan.'],
                })
                <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-semibold tracking-wide text-link">02 · PERSIAPAN</p><h2 class="mt-2 text-2xl font-semibold tracking-tight">Cek sebelum melanjutkan.</h2></div><span x-cloak x-text="checked.length + ' / {{ count($checklist) }} siap'" class="rounded-full bg-white px-3 py-2 text-xs font-medium text-muted"></span></div>
                <div class="mt-5 flex flex-col gap-2">
                    @foreach ($checklist as $index => $line)
                        <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl bg-white p-4 text-sm leading-relaxed"><input type="checkbox" x-model="checked" value="{{ $index }}" class="size-5 shrink-0 accent-action">{{ $line }}</label>
                    @endforeach
                </div>
            </section>

            <section id="aturan" class="scroll-mt-24">
                <p class="text-xs font-semibold tracking-wide text-link">03 · KEBIJAKAN TOKO</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight">Baca ketentuan lengkapnya.</h2>
                <div class="mt-5 rounded-card border border-fog bg-white p-6 text-base leading-relaxed whitespace-pre-line text-ink-2 sm:p-8">{{ $policyText }}</div>
            </section>

            <section id="faq" class="scroll-mt-24">
                @php($questions = match ($topic) {
                    'preorder' => [
                        ['Kapan pesanan masuk batch?', 'Setelah pembayaran diterima tim. Upload bukti saja belum memasukkan order ke batch; tunggu status Dibayar.'],
                        ['Apakah estimasi tiba adalah tanggal pasti?', 'Estimasi membantu merencanakan pesanan. Produksi dan pengiriman dapat memengaruhi waktunya. Periksa pembaruan di halaman lacak order.'],
                        ['Bagaimana kalau tipe HP tidak memiliki preview?', 'Tim mengonfirmasi ketersediaan blank case terlebih dahulu. Batas pembayaran baru berjalan setelah tipe dinyatakan tersedia.'],
                    ],
                    'payment' => [
                        ['Bolehkah memakai mata uang yang berbeda dari order?', 'Gunakan mata uang yang sama dengan order dan rekening yang ditampilkan pada checkout. Hubungi tim jika perlu mengubah pilihan sebelum membayar.'],
                        ['Kenapa status masih Menunggu verifikasi?', 'Bukti transfer diperiksa manual oleh tim. Setelah diterima, status berubah menjadi Dibayar dan order masuk batch aktif.'],
                        ['Apa yang terjadi jika lewat batas bayar?', 'Order yang belum dibayar melewati batas '.$limits['paymentHours'].' jam dibatalkan otomatis. Jika sudah transfer tetapi ada kendala unggahan, segera hubungi tim dengan bukti bayar.'],
                    ],
                    'cancellation' => [
                        ['Apakah order yang sudah dibayar masih bisa dibatalkan?', 'Boleh selama belum masuk produksi. Ajukan kepada tim untuk pemeriksaan status dan refund penuh.'],
                        ['Kenapa pesanan yang diproduksi tidak bisa dibatalkan?', 'Casing dicetak khusus sesuai desain pesanan. Setelah produksi dimulai, pembatalan tidak tersedia.'],
                        ['Kapan dana dikembalikan?', 'Refund maksimal 7 hari kerja setelah disetujui, dalam mata uang dan jenis rekening yang sama dengan pembayaran.'],
                    ],
                    'complaints' => [
                        ['Masalah apa yang dapat diajukan?', 'Salah kirim, salah tipe dari toko, salah cetak, cacat produksi, atau rusak saat pengiriman. Tim mengecek penjelasan, video, dan foto untuk menentukan penyelesaian.'],
                        ['Apakah semua perbedaan hasil cetak ditanggung?', 'Baca kebijakan toko di atas. Perbedaan warna wajar, gambar pelanggan yang buram, kerusakan karena pemakaian, dan salah memilih tipe HP oleh pelanggan termasuk hal yang tidak ditanggung.'],
                        ['Perlu mengirim barang kembali?', 'Jika tim meminta barang salah kirim dikembalikan, tim akan memberikan petunjuk dan ongkir ditanggung toko. Tunggu arahan sebelum mengirim barang.'],
                    ],
                })
                <h2 class="text-2xl font-semibold tracking-tight">Pertanyaan yang sering muncul.</h2>
                <div class="mt-5 divide-y divide-fog">
                    @foreach ($questions as [$question, $answer])
                        <details class="group"><summary class="flex min-h-18 cursor-pointer list-none items-center justify-between gap-4 py-5 text-sm font-semibold focus-visible:outline-action [&::-webkit-details-marker]:hidden">{{ $question }}<x-store.policy-icon name="chevron" class="size-5 text-muted transition-transform group-open:rotate-180" /></summary><p class="pb-6 text-sm leading-relaxed text-muted">{{ $answer }}</p></details>
                    @endforeach
                </div>
            </section>

            <aside class="flex flex-col items-start justify-between gap-5 rounded-tile bg-ink p-6 text-white sm:flex-row sm:items-center sm:p-8">
                <div><h2 class="text-xl font-semibold tracking-tight">Butuh bantuan dengan pesananmu?</h2><p class="mt-2 max-w-lg text-sm leading-relaxed text-white/70">Cek status terbaru dan siapkan kode order agar tim dapat membantu.</p></div>
                @if ($supportPhone)
                    <a href="https://wa.me/{{ $supportPhone }}" class="inline-flex min-h-12 shrink-0 items-center gap-2 rounded-full bg-white px-5 text-sm font-semibold text-ink">Hubungi tim<x-store.policy-icon name="arrow" class="size-4" /></a>
                @else
                    <a href="{{ route('track') }}" wire:navigate class="inline-flex min-h-12 shrink-0 items-center gap-2 rounded-full bg-white px-5 text-sm font-semibold text-ink">Lacak pesanan<x-store.policy-icon name="arrow" class="size-4" /></a>
                @endif
            </aside>

            <nav aria-label="Lanjut membaca" class="grid gap-3 sm:grid-cols-3">
                @foreach ($topics as $key => $item)
                    @if ($key !== $topic)
                        <a href="{{ route('policy.show', ['topic' => $item['slug']]) }}" wire:navigate class="group flex min-h-20 items-center justify-between gap-3 rounded-card border border-fog p-4 hover:border-action"><span class="flex items-center gap-3 text-sm font-semibold"><x-store.policy-icon :name="$key" class="size-5 text-link" />{{ $item['title'] }}</span><x-store.policy-icon name="arrow" class="size-4 text-muted group-hover:text-link" /></a>
                    @endif
                @endforeach
            </nav>
        </div>
    </div>
</div>
