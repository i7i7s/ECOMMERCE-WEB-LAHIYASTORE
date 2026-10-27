<div>
    <x-store.auth-card title="Password baru">
        <form wire:submit="resetPassword" class="flex flex-col gap-4">
            <x-store.field label="Email" name="email" type="email" autocomplete="email" required />
            <x-store.field label="Password baru" name="password" type="password" autocomplete="new-password" required hint="Minimal 8 karakter." />
            <x-store.field label="Ulangi password baru" name="password_confirmation" type="password" autocomplete="new-password" required />
            <button type="submit" class="mt-2 min-h-12.5 cursor-pointer rounded-full bg-action text-[17px] font-medium text-white hover:bg-[#0077ed]">Simpan password</button>
        </form>
    </x-store.auth-card>
</div>
