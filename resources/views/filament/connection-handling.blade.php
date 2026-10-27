{{--
    Admin panel: offline banner and readable messages for failed requests (the store has the
    same in resources/js/connection.js). Inline because the panel does not load the store's
    Vite bundle. Filament's own JS notifications go through a Livewire request, so they cannot
    report a dropped connection; this toast is plain DOM instead.
--}}
<div style="position: fixed; left: 50%; bottom: 1rem; z-index: 60; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; width: max-content; max-width: calc(100% - 2rem); transform: translateX(-50%); font-size: 0.875rem; line-height: 1.45">
    <div id="lahiya-offline" role="status" aria-live="polite" hidden style="padding: 0.75rem 1rem; border-radius: 0.75rem; background: #1d1d1f; color: #fff; box-shadow: 0 10px 30px rgb(0 0 0 / 0.2)">
        Kamu sedang offline. Perubahan belum tersimpan sampai koneksi kembali.
    </div>
    {{-- No display rule on the element itself: it would override the hidden attribute. --}}
    <div id="lahiya-request-error" role="alert" hidden style="max-width: 28rem; padding: 0.75rem 1rem; border-radius: 0.75rem; background: #fff; color: #1d1d1f; box-shadow: 0 10px 30px rgb(0 0 0 / 0.15), 0 0 0 1px rgb(0 0 0 / 0.08)">
        <div style="display: flex; gap: 0.75rem; align-items: flex-start">
            <span style="flex: 1"><strong data-title style="display: block"></strong><span data-body style="color: #6e6e73"></span></span>
            <button type="button" data-reload hidden onclick="location.reload()" style="border: 0; background: none; color: #0071e3; font: inherit; font-weight: 600; cursor: pointer">Muat ulang</button>
            <button type="button" data-close aria-label="Tutup" style="border: 0; background: none; color: #86868b; font: inherit; cursor: pointer">✕</button>
        </div>
    </div>
</div>
<script>
    (() => {
        const debug = @js((bool) config('app.debug'));
        const banner = document.getElementById('lahiya-offline');
        const toast = document.getElementById('lahiya-request-error');
        let hideTimer = null;

        const show = ([title, body, reload]) => {
            toast.querySelector('[data-title]').textContent = title;
            toast.querySelector('[data-body]').textContent = body;
            toast.querySelector('[data-reload]').hidden = ! reload;
            toast.hidden = false;
            clearTimeout(hideTimer);
            hideTimer = setTimeout(() => (toast.hidden = true), reload ? 12000 : 7000);
        };

        toast.querySelector('[data-close]').addEventListener('click', () => (toast.hidden = true));
        window.addEventListener('offline', () => (banner.hidden = false));
        window.addEventListener('online', () => (banner.hidden = true));
        banner.hidden = navigator.onLine;

        const messageFor = (status) => {
            if (! navigator.onLine) return ['Kamu sedang offline', 'Periksa koneksi internet, lalu ulangi tindakan terakhir.', false];
            if (status === 419) return ['Sesi sudah habis', 'Muat ulang halaman, lalu ulangi tindakan terakhir.', true];
            if (status === 401 || status === 403) return ['Tidak punya akses', 'Akunmu tidak boleh melakukan tindakan ini.', false];
            if (status === 404) return ['Data tidak ditemukan', 'Mungkin sudah dihapus atau berubah.', true];
            if (status === 413) return ['File terlalu besar', 'Pilih file yang lebih kecil.', false];
            if (status === 429) return ['Terlalu banyak percobaan', 'Tunggu sebentar, lalu coba lagi.', false];
            if (status === 503) return ['Sedang perawatan', 'Coba lagi beberapa menit lagi.', true];
            if (status >= 500) return ['Gagal memproses data', 'Ada gangguan di server. Coba lagi; kalau masih gagal, cek log aplikasi.', true];

            return ['Gagal terhubung ke server', 'Periksa koneksi internet, lalu coba lagi.', false];
        };

        const register = () => window.Livewire.hook('request', ({ fail }) => {
            fail(({ status, content, preventDefault }) => {
                // A request that never reached the server comes as status 503 without a body.
                const reachedServer = content !== null && content !== undefined;

                if (debug && reachedServer && status >= 500 && status !== 503) return;

                preventDefault();
                show(messageFor(reachedServer ? status : 0));
            });
        });

        window.Livewire ? register() : document.addEventListener('livewire:init', register);
    })();
</script>
