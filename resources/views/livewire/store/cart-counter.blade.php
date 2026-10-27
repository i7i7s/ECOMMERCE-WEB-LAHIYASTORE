<a href="{{ route('cart') }}" wire:navigate aria-label="Keranjang, {{ $count }} item" class="relative flex size-11 items-center justify-center rounded-full text-ink">
    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 7h12l-1 13H7L6 7z" /><path d="M9 7a3 3 0 0 1 6 0" /></svg>
    @if ($count > 0)
        <span class="absolute top-1.5 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-action px-1 text-[10px] font-bold text-white">{{ $count }}</span>
    @endif
</a>
