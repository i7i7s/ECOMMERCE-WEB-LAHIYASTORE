<div>
    <x-store.auth-card title="Buat akun" subtitle="Simpan alamat di Jepang dan pantau semua pesananmu.">
        <x-store.google-button label="Daftar dengan Google" />

        <form wire:submit="register" class="flex flex-col gap-4">
            <x-store.field label="Nama" name="name" autocomplete="name" autofocus required />
            <x-store.field label="Email" name="email" type="email" autocomplete="email" required hint="Dipakai untuk login dan reset password." />
            <x-store.field label="No. HP" name="phone" type="tel" autocomplete="tel" placeholder="080-1234-5678" required />
            <x-store.field label="Password" name="password" type="password" autocomplete="new-password" required hint="Minimal 8 karakter." />
            <x-store.field label="Ulangi password" name="password_confirmation" type="password" autocomplete="new-password" required />

            <button type="submit" class="mt-2 min-h-12.5 cursor-pointer rounded-full bg-action text-[17px] font-medium text-white hover:bg-[#0077ed]">
                <span wire:loading.remove wire:target="register">Daftar</span>
                <span wire:loading wire:target="register">Membuat akun…</span>
            </button>
            <p class="text-center text-xs text-muted">Dengan mendaftar, kamu setuju dengan <a href="{{ route('policy') }}" class="text-link hover:underline">kebijakan toko</a>.</p>
        </form>

        <p class="mt-6 text-center text-[15px] text-muted">
            Sudah punya akun? <a href="{{ route('login') }}" wire:navigate class="text-link hover:underline">Masuk</a>
        </p>
    </x-store.auth-card>
</div>
