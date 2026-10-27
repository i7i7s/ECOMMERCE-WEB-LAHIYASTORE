<div class="mx-auto max-w-5xl px-4 pt-8 pb-24 sm:px-6">
    <a href="{{ route('catalog', ['kategori' => $product->category->slug]) }}" wire:navigate class="text-sm text-link hover:underline">‹ {{ $product->category->name }}</a>

    <div class="mt-6 grid gap-10 md:grid-cols-2">
        <div class="relative overflow-hidden rounded-tile">
            <x-store.product-image :path="$product->images->first()?->path" :alt="$product->name" class="aspect-square w-full" />
            <span class="absolute top-4 left-4 rounded-full bg-white px-3 py-1 text-xs font-semibold text-eyebrow">Preorder</span>
        </div>

        <form wire:submit="addToCart" class="flex flex-col gap-5">
            <div>
                <p class="text-sm text-muted">{{ $product->category->name }}</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight sm:text-[40px]">{{ $product->name }}</h1>
                <p class="mt-2 text-[21px]">{{ $price }}</p>
                @if ($product->description)
                    <p class="mt-3 text-[17px] leading-relaxed text-ink-2">{{ $product->description }}</p>
                @endif
            </div>

            @if ($product->variants->count() > 1)
                <fieldset>
                    <legend class="mb-2.5 text-sm font-semibold text-muted">Pilih varian</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($product->variants as $option)
                            <label wire:key="variant-{{ $option->id }}" @class([
                                'flex min-h-11 min-w-14 cursor-pointer items-center justify-center rounded-xl border px-4 text-[15px]',
                                'border-action ring-1 ring-action' => $variantId === $option->id,
                                'border-line hover:border-muted' => $variantId !== $option->id,
                            ])>
                                <input type="radio" wire:model.live.number="variantId" value="{{ $option->id }}" class="sr-only">
                                {{ $option->name }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif
            @error('variantId') <p class="text-sm text-bad">{{ $message }}</p> @enderror

            <label class="flex items-center gap-3 text-sm text-muted">Jumlah
                <select wire:model.number="qty" class="h-11 rounded-xl border border-line bg-white px-3 text-[15px] text-ink">
                    @foreach (range(1, $maxQty) as $number)
                        <option value="{{ $number }}">{{ $number }}</option>
                    @endforeach
                </select>
            </label>

            <div class="rounded-2xl bg-mist px-4 py-3.5 text-sm leading-relaxed">
                Preorder · masuk batch {{ $batch->code }}<br>
                <strong class="font-semibold">Estimasi tiba {{ $etaLabel }}</strong> · gratis ongkir di Jepang
            </div>

            <button type="submit" class="min-h-12.5 cursor-pointer rounded-full bg-action text-[17px] font-medium text-white hover:bg-[#0077ed]">
                <span wire:loading.remove wire:target="addToCart">Tambah ke keranjang</span>
                <span wire:loading wire:target="addToCart">Menambahkan…</span>
            </button>

            @if ($added)
                <p role="status" class="flex items-center justify-between rounded-2xl bg-good-soft px-4 py-3 text-sm text-good">
                    Ditambahkan ke keranjang.
                    <a href="{{ route('cart') }}" wire:navigate class="font-semibold text-good underline">Lihat keranjang</a>
                </p>
            @endif
        </form>
    </div>
</div>
