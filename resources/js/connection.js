/**
 * Connection and request failures in the store (resources/views/components/store/connection-status.blade.php).
 *
 * - Shows a banner while the browser is offline and a short "back online" note afterwards.
 * - Turns failed Livewire requests into a readable toast instead of Livewire's default
 *   "page expired" confirm or error modal (the modal is kept in debug mode for developers).
 * - Stops wire:navigate links while offline, so the visitor stays on the page.
 */
const messages = {
    offline: 'Kamu sedang offline. Periksa koneksi internet, lalu coba lagi.',
    network: 'Gagal terhubung ke server. Periksa koneksi internet, lalu coba lagi.',
    expired: 'Sesi halaman ini sudah habis. Muat ulang halaman untuk melanjutkan.',
    forbidden: 'Kamu tidak punya akses untuk tindakan ini.',
    notFound: 'Data tidak ditemukan. Mungkin sudah dihapus atau berubah.',
    tooMany: 'Terlalu banyak percobaan. Tunggu sebentar, lalu coba lagi.',
    tooLarge: 'File terlalu besar untuk diunggah.',
    server: 'Gagal memuat data karena ada gangguan di server. Coba lagi sebentar lagi.',
    maintenance: 'Toko sedang dalam perawatan. Coba lagi beberapa menit lagi.',
};

const messageFor = (status) => {
    if (!navigator.onLine) {
        return { text: messages.offline, reload: false };
    }

    switch (status) {
        case 419:
            return { text: messages.expired, reload: true };
        case 401:
        case 403:
            return { text: messages.forbidden, reload: false };
        case 404:
            return { text: messages.notFound, reload: true };
        case 413:
            return { text: messages.tooLarge, reload: false };
        case 429:
            return { text: messages.tooMany, reload: false };
        case 503:
            return { text: messages.maintenance, reload: true };
        default:
            return { text: status >= 500 ? messages.server : messages.network, reload: status >= 500 };
    }
};

export const registerConnectionHandling = (Alpine, Livewire) => {
    const debug = document.documentElement.dataset.debug === '1';
    let hideTimer = null;

    Alpine.store('connection', {
        online: navigator.onLine,
        backOnline: false,
        toast: null,

        notify(text, reload = false) {
            this.toast = { text, reload };
            clearTimeout(hideTimer);
            hideTimer = setTimeout(() => (this.toast = null), reload ? 12000 : 7000);
        },

        dismiss() {
            this.toast = null;
        },
    });

    const store = () => Alpine.store('connection');

    window.addEventListener('offline', () => {
        store().online = false;
        store().backOnline = false;
    });

    window.addEventListener('online', () => {
        store().online = true;
        store().backOnline = true;
        setTimeout(() => (store().backOnline = false), 3000);
    });

    Livewire.hook('request', ({ fail }) => {
        fail(({ status, content, preventDefault }) => {
            // Livewire reports a request that never reached the server as status 503 without a body.
            const reachedServer = content !== null && content !== undefined;

            // Keep Laravel's error page in a modal while developing, it shows the exception.
            if (debug && reachedServer && status >= 500 && status !== 503) {
                return;
            }

            preventDefault();

            const { text, reload } = messageFor(reachedServer ? status : 0);
            store().notify(text, reload);
        });
    });

    document.addEventListener('livewire:navigate', (event) => {
        if (!navigator.onLine) {
            event.preventDefault();
            store().notify(messages.offline);
        }
    });
};

/**
 * Service worker that only shows public/offline.html when a page cannot be loaded at all.
 */
export const registerOfflinePage = () => {
    if ('serviceWorker' in navigator && window.isSecureContext) {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Without it the browser shows its own offline page; nothing else depends on it.
        });
    }
};
