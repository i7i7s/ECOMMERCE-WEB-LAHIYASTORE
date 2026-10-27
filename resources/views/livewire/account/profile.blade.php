<div>
    <x-store.account-shell title="Profil">
        <div class="flex flex-col gap-6">
            <form wire:submit="saveProfile" class="flex flex-col gap-4 rounded-[22px] bg-mist p-5 sm:p-6">
                <h2 class="text-[19px] font-semibold">Data diri</h2>
                <x-store.field label="Nama" name="name" autocomplete="name" required />
                <x-store.field label="Email" name="email" type="email" autocomplete="email" required />
                <x-store.field label="No. HP" name="phone" type="tel" autocomplete="tel" required />
                <div class="flex flex-wrap items-center gap-4">
                    <button type="submit" class="min-h-11 cursor-pointer rounded-full bg-action px-6 text-[15px] font-medium text-white hover:bg-[#0077ed]">Simpan</button>
                    @if ($profileSaved)
                        <span role="status" class="text-sm text-good">Profil tersimpan.</span>
                    @endif
                </div>
            </form>

            <form wire:submit="savePassword" class="flex flex-col gap-4 rounded-[22px] bg-mist p-5 sm:p-6">
                <h2 class="text-[19px] font-semibold">Ganti password</h2>
                <x-store.field label="Password saat ini" name="current_password" type="password" autocomplete="current-password" required />
                <x-store.field label="Password baru" name="password" type="password" autocomplete="new-password" required hint="Minimal 8 karakter." />
                <x-store.field label="Ulangi password baru" name="password_confirmation" type="password" autocomplete="new-password" required />
                <div class="flex flex-wrap items-center gap-4">
                    <button type="submit" class="min-h-11 cursor-pointer rounded-full bg-action px-6 text-[15px] font-medium text-white hover:bg-[#0077ed]">Ganti password</button>
                    @if ($passwordSaved)
                        <span role="status" class="text-sm text-good">Password diganti.</span>
                    @endif
                </div>
            </form>
        </div>
    </x-store.account-shell>
</div>
