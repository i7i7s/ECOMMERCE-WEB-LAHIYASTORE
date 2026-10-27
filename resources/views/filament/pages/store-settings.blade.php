<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="save">
            Simpan pengaturan
        </x-filament::button>
    </form>
</x-filament-panels::page>
