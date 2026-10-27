@props(['size' => 'md', 'compact' => false])

{{-- Lahiya Store logo: mark + lowercase wordmark, "store" set small beside it. --}}
<span {{ $attributes->class([
    'inline-flex items-center font-bold tracking-[-0.03em] text-ink',
    'gap-2 text-[19px]' => $size === 'md',
    'gap-3 text-[34px]' => $size === 'lg',
]) }}>
    <x-store.logo-mark @class(['shrink-0', 'size-7' => $size === 'md', 'size-12' => $size === 'lg']) />
    <span class="leading-none">lahiya<span @class(['ml-1 font-medium tracking-normal text-muted', 'text-[13px]' => $size === 'md', 'text-[20px]' => $size === 'lg', 'max-sm:hidden' => $compact])>store</span></span>
</span>
