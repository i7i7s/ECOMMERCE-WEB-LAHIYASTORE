<div class="mx-auto max-w-5xl px-4 pt-12 pb-24 sm:px-6">
    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">Katalog</h1>
    @if ($this->products->isNotEmpty() && $this->products->every(fn (array $product): bool => $product['credit']))
        <p class="mt-2 text-lg text-muted sm:text-[19px]">Beli dengan cicilan bulanan. Verifikasi akun cukup lewat WhatsApp.</p>
    @else
        <p class="mt-2 text-lg text-muted sm:text-[19px]">Produk preorder: pesan sebelum {{ $cutoffLabel }}, tiba {{ $etaLabel }}.@if ($this->products->contains('credit', true)) iPhone bisa dibeli dengan kredit. @endif</p>
    @endif

    <div class="mt-7 flex flex-wrap items-center justify-between gap-4">
        <div role="tablist" aria-label="Kategori" class="flex flex-wrap gap-2">
            @foreach (['' => 'Semua'] + $categories as $slug => $name)
                <button
                    type="button"
                    role="tab"
                    wire:key="cat-{{ $slug ?: 'semua' }}"
                    wire:click="selectCategory('{{ $slug }}')"
                    aria-selected="{{ $category === $slug ? 'true' : 'false' }}"
                    @class([
                        'min-h-10 cursor-pointer rounded-full px-4 text-sm transition',
                        'bg-ink text-white' => $category === $slug,
                        'bg-mist text-ink hover:bg-fog' => $category !== $slug,
                    ])
                >{{ $name }}</button>
            @endforeach
        </div>
        <label class="flex items-center gap-2 text-sm text-muted">Urutkan
            <select wire:model.live="sort" class="h-10 rounded-[10px] border border-line bg-white px-3 text-sm text-ink">
                <option value="terbaru">Terbaru</option>
                <option value="termurah">Harga terendah</option>
                <option value="termahal">Harga tertinggi</option>
            </select>
        </label>
    </div>

    @if ($category === '' && $customPrice)
        <a href="{{ route('custom') }}" wire:navigate class="mt-7 grid items-center gap-6 rounded-tile bg-black px-6 py-8 text-mist sm:grid-cols-[1fr_auto] sm:px-9">
            <span class="flex flex-col gap-2">
                <span class="text-sm font-semibold text-[#ff9f4a]">Custom Casing</span>
                <span class="text-3xl leading-tight font-bold tracking-tight sm:text-[34px]">Desain sendiri, mulai {{ $customPrice }}.</span>
                <span class="text-[15px] text-[#a1a1a6]">iPhone &amp; Samsung dengan preview 2D. Tipe lain tanpa preview, dikonfirmasi tim.</span>
                <span class="mt-2 inline-flex min-h-11 items-center self-start rounded-full bg-action px-5 text-[15px] text-white">Mulai desain</span>
            </span>
            <x-store.case-mockup class="hidden w-[143px] justify-self-center sm:block" />
        </a>
    @endif

    <div class="mt-7 grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:grid-cols-4">
        @forelse ($this->products as $product)
            <a href="{{ route('product.show', $product['slug']) }}" wire:navigate wire:key="product-{{ $product['slug'] }}" class="group flex flex-col gap-2.5 text-ink">
                <span class="relative overflow-hidden rounded-[22px] transition group-hover:opacity-90">
                    <x-store.product-image :path="$product['image']" :alt="$product['name']" class="aspect-square w-full" />
                    @if ($product['credit'])
                        <span class="absolute top-3 left-3 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-link">Bisa kredit</span>
                    @else
                        <span class="absolute top-3 left-3 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-eyebrow">Preorder</span>
                    @endif
                </span>
                <span class="text-xs text-muted">{{ $product['category'] }}</span>
                <span class="-mt-1.5 text-[17px] font-semibold">{{ $product['name'] }}</span>
                <span class="text-[15px]">{{ $product['price_label'] }}</span>
                @if ($product['monthly_label'])
                    <span class="-mt-1.5 text-[13px] font-semibold text-link">{{ $product['monthly_label'] }}</span>
                @endif
                <span class="text-xs text-muted">{{ implode(' · ', $product['variants']) }}</span>
            </a>
        @empty
            <p class="col-span-full py-12 text-center text-muted">Belum ada produk di kategori ini.</p>
        @endforelse
    </div>

    <p class="mt-10 text-center text-[13px] text-muted">Gratis ongkir ke seluruh Jepang · Bayar transfer bank Jepang (¥) atau BCA (Rp)</p>
</div>
