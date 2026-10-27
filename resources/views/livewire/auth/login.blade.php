<div>
    <x-store.auth-card title="Masuk" subtitle="Lihat pesananmu dan checkout lebih cepat.">
        <x-store.google-button />

        <form wire:submit="login" class="flex flex-col gap-4">
            <x-store.field label="Email atau no. HP" name="identifier" autocomplete="username" autofocus required />
            <x-store.field label="Password" name="password" type="password" autocomplete="current-password" required />

            <div class="flex items-center justify-between gap-3">
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="remember" class="size-4.5 accent-action">
                    Ingat saya
                </label>
                <a href="{{ route('password.request') }}" wire:navigate class="text-sm text-link hover:underline">Lupa password?</a>
            </div>

            <button type="submit" class="mt-2 min-h-12.5 cursor-pointer rounded-full bg-action text-[17px] font-medium text-white hover:bg-[#0077ed]">
                <span wire:loading.remove wire:target="login">Masuk</span>
                <span wire:loading wire:target="login">Memeriksa…</span>
            </button>
        </form>

        <p class="mt-6 text-center text-[15px] text-muted">
            Belum punya akun? <a href="{{ route('register') }}" wire:navigate class="text-link hover:underline">Daftar</a>
        </p>
        <p class="mt-2 text-center text-[13px] text-muted">
            Atau <a href="{{ route('catalog') }}" wire:navigate class="text-link hover:underline">lihat-lihat katalog dulu</a>.
        </p>
    </x-store.auth-card>
</div>
