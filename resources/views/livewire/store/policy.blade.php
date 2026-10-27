@php
    $labels = ['preorder' => 'Preorder', 'payment' => 'Pembayaran', 'cancellation' => 'Pembatalan', 'complaints' => 'Retur & refund'];
    $hints = ['preorder' => 'Dari pesanan hingga sampai di Jepang.', 'payment' => 'Pilihan pembayaran dan batas waktunya.', 'cancellation' => 'Ketahui kapan pesanan bisa dibatalkan.', 'complaints' => 'Bukti yang perlu disiapkan dan pilihan penyelesaian.'];
@endphp
<div
    x-data="{
        query: '',
        copied: null,
        failedId: null,
        shareUrl: '',
        matches(element) { return element.textContent.toLocaleLowerCase('id').includes(this.query.trim().toLocaleLowerCase('id')); },
        count() { return [...(this.$refs.topics?.querySelectorAll('details') ?? [])].filter(element => this.matches(element)).length; },
        reveal(id) {
            const topic = [...this.$refs.topics.querySelectorAll('details')].find(element => element.id === id);
            if (topic) { this.query = ''; topic.open = true; }
        },
        expand(value) { this.$refs.topics.querySelectorAll('details').forEach(element => element.open = value); },
        async copyLink(id, path) {
            const url = new URL(path, window.location.origin);
            this.copied = null;
            this.failedId = null;
            try {
                await navigator.clipboard.writeText(url.href);
                this.copied = id;
                setTimeout(() => { if (this.copied === id) this.copied = null; }, 2500);
            } catch {
                this.shareUrl = url.href;
                this.failedId = id;
            }
        }
    }"
    x-init="$nextTick(() => reveal(window.location.hash.slice(1)))"
    @hashchange.window="reveal(window.location.hash.slice(1))"
    class="mx-auto max-w-5xl px-4 pt-12 pb-24 sm:px-6 sm:pt-20"
>
    <header class="mx-auto max-w-3xl text-center">
        <span class="inline-flex rounded-full bg-info-soft px-4 py-2 text-xs font-semibold tracking-wide text-info">PANDUAN BELANJA</span>
        <h1 class="mt-5 text-4xl leading-tight font-bold tracking-tight sm:text-6xl">Belanja tenang.<br><span class="text-muted">Semua jelas di sini.</span></h1>
        <p class="mx-auto mt-5 max-w-xl text-lg leading-relaxed text-muted">Kenali cara preorder &amp; kebijakan Lahiya. Pilih topik untuk membuka panduan lengkap, atau cari ringkasan sebelum memesan.</p>
    </header>

    <nav aria-label="Pilih topik kebijakan" class="mt-10 grid grid-cols-2 gap-3 sm:mt-12 sm:grid-cols-4">
        @foreach ($sections as $key => [$title, $text])
            @php($anchor = $key === 'complaints' ? 'refund' : $key)
            <a href="{{ route('policy.show', ['topic' => $topics[$key]['slug']]) }}" wire:navigate class="group flex min-h-28 flex-col justify-between gap-5 rounded-card border border-fog bg-white p-5 transition-colors hover:border-action hover:bg-info-soft focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-action">
                <span class="flex items-center justify-between">
                    <span class="flex size-11 items-center justify-center rounded-2xl bg-info-soft text-link"><x-store.policy-icon :name="$key" class="size-6" /></span>
                    <x-store.policy-icon name="arrow" class="size-4 text-muted transition-transform group-hover:translate-x-1 motion-reduce:transition-none" />
                </span>
                <span class="text-base font-semibold group-hover:text-link">{{ $labels[$key] }}</span>
                <span class="text-xs text-muted">Buka panduan lengkap</span>
            </a>
        @endforeach
    </nav>

    <section aria-labelledby="policy-heading" class="mt-12 sm:mt-16">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="policy-heading" class="text-2xl font-semibold tracking-tight">Cara preorder &amp; kebijakan</h2>
                <p class="mt-1.5 text-sm text-muted">Buka setiap bagian untuk membaca selengkapnya.</p>
            </div>
            <div x-cloak class="flex gap-4 text-sm">
                <button type="button" @click="expand(true)" class="min-h-11 text-link hover:underline focus-visible:outline-2 focus-visible:outline-action">Buka semua</button>
                <button type="button" @click="expand(false)" class="min-h-11 text-muted hover:text-ink hover:underline focus-visible:outline-2 focus-visible:outline-action">Tutup semua</button>
            </div>
        </div>
        <div x-cloak class="mt-5">
            <label for="policy-search" class="sr-only">Cari dalam kebijakan</label>
            <div class="flex items-center gap-3 rounded-2xl border border-line bg-white px-4 focus-within:border-action focus-within:ring-2 focus-within:ring-action/15">
                <svg class="size-5 shrink-0 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>
                <input id="policy-search" type="search" x-model.debounce.150ms="query" @keydown.escape="query = ''" placeholder="Cari, misalnya: refund, bukti transfer, Jepang" autocomplete="off" class="min-h-14 w-full min-w-0 bg-transparent text-base outline-none placeholder:text-muted" aria-controls="policy-topics">
            </div>
            <p x-show="query.trim()" x-text="count() + ' topik ditemukan'" role="status" aria-live="polite" class="mt-3 text-sm text-muted"></p>
        </div>

        <div id="policy-topics" x-ref="topics" class="mt-6 flex flex-col gap-4">
            @foreach ($sections as $key => [$title, $text])
                <details id="{{ $key === 'complaints' ? 'refund' : $key }}" @if ($loop->first) open @endif x-show="matches($el)" x-effect="if (query.trim()) $el.open = true" class="group scroll-mt-28 overflow-hidden rounded-card border border-fog bg-white open:border-line">
                    <summary class="flex min-h-24 cursor-pointer list-none items-center gap-4 p-5 select-none focus-visible:outline-2 focus-visible:-outline-offset-4 focus-visible:outline-action sm:p-7 [&::-webkit-details-marker]:hidden">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-info-soft text-link"><x-store.policy-icon :name="$key" class="size-6" /></span>
                        <span class="flex-1">
                            <span class="block text-lg font-semibold tracking-tight sm:text-xl">{{ $title }}</span>
                            <span class="mt-1 block text-sm font-normal text-muted">{{ $hints[$key] }}</span>
                        </span>
                        <x-store.policy-icon name="chevron" class="size-5 text-muted transition-transform group-open:rotate-180 motion-reduce:transition-none" />
                    </summary>
                    <div class="border-t border-fog px-5 py-6 sm:px-7 sm:pl-22">
                        <div class="max-w-3xl text-base leading-relaxed whitespace-pre-line text-ink-2 sm:text-[17px]">{{ $text }}</div>
                        <a href="{{ route('policy.show', ['topic' => $topics[$key]['slug']]) }}" wire:navigate class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-full bg-action px-5 text-sm font-medium text-white hover:bg-link">Baca panduan {{ $labels[$key] }}<x-store.policy-icon name="arrow" class="size-4" /></a>
                        @php($sectionAnchor = $key === 'complaints' ? 'refund' : $key)
                        <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-fog pt-4">
                            <a href="{{ route('policy.show', ['topic' => $topics[$key]['slug']]) }}" wire:navigate class="inline-flex min-h-11 items-center gap-2 rounded-xl px-3 text-sm text-link hover:bg-info-soft focus-visible:outline-2 focus-visible:outline-action">
                                <x-store.policy-icon name="link" class="size-4" /> Tautan panduan lengkap
                            </a>
                            <button x-cloak type="button" @click="copyLink(@js($sectionAnchor), @js(route('policy.show', ['topic' => $topics[$key]['slug']])))" aria-label="Salin tautan {{ $title }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-fog px-3 text-sm font-medium text-ink-2 transition-colors hover:border-action hover:text-link focus-visible:outline-2 focus-visible:outline-action">
                                <span x-show="copied !== @js($sectionAnchor)"><x-store.policy-icon name="copy" class="size-4" /></span>
                                <span x-show="copied === @js($sectionAnchor)" class="text-good"><x-store.policy-icon name="check" class="size-4" /></span>
                                <span x-text="copied === @js($sectionAnchor) ? 'Tersalin' : 'Salin tautan'">Salin tautan</span>
                            </button>
                            <span x-cloak x-show="copied === @js($sectionAnchor)" role="status" class="text-xs text-good">Tautan siap dibagikan.</span>
                        </div>
                        <div x-cloak x-show="failedId === @js($sectionAnchor)" class="mt-3 rounded-xl bg-mist p-3">
                            <label for="share-{{ $sectionAnchor }}" class="text-sm text-muted">Salin tautan ini secara manual:</label>
                            <input id="share-{{ $sectionAnchor }}" type="text" readonly :value="shareUrl" @focus="$el.select()" class="mt-2 min-h-11 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink outline-none focus:border-action">
                        </div>
                    </div>
                </details>
            @endforeach
        </div>
        <div x-cloak x-show="query.trim() && count() === 0" class="mt-6 rounded-card bg-mist px-6 py-12 text-center">
            <p class="text-xl font-semibold">Topiknya belum ditemukan.</p>
            <p class="mt-2 text-muted">Coba kata yang lebih singkat, seperti “bayar” atau “komplain”.</p>
            <button type="button" @click="query = ''; $nextTick(() => document.getElementById('policy-search').focus())" class="mt-5 min-h-11 rounded-full bg-action px-5 text-sm font-medium text-white hover:bg-link">Tampilkan semua topik</button>
        </div>
    </section>
    <aside class="mt-10 flex flex-col items-start justify-between gap-6 rounded-tile bg-mist p-6 sm:flex-row sm:items-center sm:p-8">
        <div>
            <h2 class="text-xl font-semibold tracking-tight">Sudah punya pesanan?</h2>
            <p class="mt-2 max-w-lg text-sm leading-relaxed text-muted">Lihat status pesananmu untuk mengetahui tahap proses dan informasi pengiriman terbaru.</p>
        </div>
        <a href="{{ route('track') }}" wire:navigate class="inline-flex min-h-12 shrink-0 items-center justify-center gap-3 rounded-full bg-action px-6 text-sm font-medium text-white hover:bg-link focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-action">Lacak pesanan <x-store.policy-icon name="arrow" class="size-4" /></a>
    </aside>
    <p class="mx-auto mt-7 max-w-xl text-center text-xs leading-relaxed text-muted">Dengan checkout, kamu menyetujui kebijakan di halaman ini. Baca kembali sebelum menyelesaikan pesanan.</p>
</div>
