@props(['label', 'name', 'type' => 'text', 'hint' => null])

{{-- Labelled input bound to a Livewire property, with its validation error. --}}
<label class="flex flex-col gap-1.5 text-[13px] text-muted">
    {{ $label }}
    <input
        type="{{ $type }}"
        wire:model="{{ $name }}"
        {{ $attributes->class([
            'h-12.5 rounded-xl border bg-white px-4 text-[17px] text-ink',
            'border-bad' => $errors->has($name),
            'border-line' => ! $errors->has($name),
        ]) }}
    >
    @if ($hint)
        <span class="text-xs">{{ $hint }}</span>
    @endif
    @error($name) <span class="text-sm text-bad">{{ $message }}</span> @enderror
</label>
