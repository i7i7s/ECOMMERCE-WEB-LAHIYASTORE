<div>
    <x-store.auth-card title="Lupa password" subtitle="Kami kirim link untuk membuat password baru ke emailmu.">
        @if ($sent)
            <p role="status" class="rounded-2xl bg-good-soft px-4 py-3 text-[15px] leading-relaxed text-good">
                Kalau email tersebut terdaftar, link reset sudah dikirim. Cek kotak masuk dan folder spam. Link berlaku 60 menit.
            </p>
        @else
            <form wire:submit="sendLink" class="flex flex-col gap-4">
                <x-store.field label="Email" name="email" type="email" autocomplete="email" autofocus required />
                <button type="submit" class="mt-2 min-h-12.5 cursor-pointer rounded-full bg-action text-[17px] font-medium text-white hover:bg-[#0077ed]">
                    <span wire:loading.remove wire:target="sendLink">Kirim link reset</span>
                    <span wire:loading wire:target="sendLink">Mengirim…</span>
                </button>
            </form>
        @endif

        <p class="mt-6 text-center text-[15px] text-muted">
            <a href="{{ route('login') }}" wire:navigate class="text-link hover:underline">Kembali ke halaman masuk</a>
        </p>
    </x-store.auth-card>
</div>
