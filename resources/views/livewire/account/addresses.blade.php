<div>
    <x-store.account-shell title="Alamat di Jepang">
        @if ($editingId !== null)
            <form wire:submit="save" class="mb-6 flex flex-col gap-4 rounded-[22px] bg-mist p-5 sm:p-6">
                <h2 class="text-[19px] font-semibold">{{ $editingId === 0 ? 'Alamat baru' : 'Ubah alamat' }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-store.field label="Label (opsional)" name="label" placeholder="Rumah, Kantor, ..." />
                    <x-store.field label="Nama penerima" name="recipient_name" autocomplete="name" required />
                    <x-store.field label="No. HP" name="phone" type="tel" autocomplete="tel" required />
                    <x-store.field label="Kode pos" name="postal_code" placeholder="530-0001" autocomplete="postal-code" required />
                    <label class="flex flex-col gap-1.5 text-[13px] text-muted">Prefektur
                        <select wire:model="prefecture" @class(['h-12.5 rounded-xl border bg-white px-4 text-[17px] text-ink', 'border-bad' => $errors->has('prefecture'), 'border-line' => ! $errors->has('prefecture')])>
                            <option value="">Pilih prefektur</option>
                            @foreach ($prefectures as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('prefecture') <span class="text-sm text-bad">{{ $message }}</span> @enderror
                    </label>
                    <x-store.field label="Kota / ward" name="city" autocomplete="address-level2" required />
                    <x-store.field label="Alamat (chome, banchi)" name="address_line1" autocomplete="address-line1" required />
                    <x-store.field label="Gedung & kamar (opsional)" name="address_line2" autocomplete="address-line2" />
                </div>
                <label class="flex cursor-pointer items-center gap-2.5 text-[15px]">
                    <input type="checkbox" wire:model="is_default" class="size-5 accent-action">
                    Jadikan alamat utama
                </label>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="min-h-11 cursor-pointer rounded-full bg-action px-6 text-[15px] font-medium text-white hover:bg-[#0077ed]">Simpan alamat</button>
                    <button type="button" wire:click="cancel" class="min-h-11 cursor-pointer rounded-full px-4 text-[15px] text-link">Batal</button>
                </div>
            </form>
        @endif

        <div class="flex flex-col gap-3">
            @forelse ($addresses as $address)
                <article wire:key="address-{{ $address->id }}" class="flex flex-col gap-3 rounded-[20px] border border-line p-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[17px] font-semibold">{{ $address->label ?: $address->recipient_name }}</span>
                            @if ($address->is_default)
                                <span class="rounded-full bg-info-soft px-2.5 py-0.5 text-xs font-semibold text-info">Utama</span>
                            @endif
                        </div>
                        <span class="text-[15px]">{{ $address->recipient_name }} · {{ $address->phone }}</span>
                        <span class="text-sm leading-relaxed text-ink-2">〒{{ $address->postal_code }} {{ $address->prefecture }}, {{ $address->city }}, {{ $address->address_line1 }}{{ $address->address_line2 ? ', '.$address->address_line2 : '' }}</span>
                    </div>
                    <div class="flex shrink-0 gap-4 text-sm">
                        @unless ($address->is_default)
                            <button type="button" wire:click="makeDefault({{ $address->id }})" class="min-h-9 cursor-pointer text-link hover:underline">Jadikan utama</button>
                        @endunless
                        <button type="button" wire:click="edit({{ $address->id }})" class="min-h-9 cursor-pointer text-link hover:underline">Ubah</button>
                        <button type="button" x-on:click="$store.confirm.ask({ title: 'Hapus alamat?', message: 'Alamat ini akan dihapus dari buku alamatmu.', confirmLabel: 'Hapus', onConfirm: () => $wire.delete({{ $address->id }}) })" class="min-h-9 cursor-pointer text-bad hover:underline">Hapus</button>
                    </div>
                </article>
            @empty
                @if ($editingId === null)
                    <p class="rounded-[20px] bg-mist px-5 py-8 text-center text-[15px] text-muted">Belum ada alamat tersimpan.</p>
                @endif
            @endforelse
        </div>

        @if ($editingId === null && $addresses->count() < $maxAddresses)
            <button type="button" wire:click="create" class="mt-5 min-h-11 cursor-pointer rounded-full border border-action px-5 text-[15px] text-link hover:bg-info-soft">+ Tambah alamat</button>
        @endif
        @error('label') <p class="mt-3 text-sm text-bad">{{ $message }}</p> @enderror
    </x-store.account-shell>
</div>
