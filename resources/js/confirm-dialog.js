/**
 * Store confirmation dialog (resources/views/components/store/confirm-dialog.blade.php), used
 * instead of window.confirm(), which shows the site address ("localhost says") in a browser box.
 *
 * Usage: x-on:click="$store.confirm.ask({ message: 'Hapus alamat ini?', confirmLabel: 'Hapus', onConfirm: () => $wire.delete(1) })"
 */
export const registerConfirmDialog = (Alpine) => {
    Alpine.store('confirm', {
        open: false,
        title: '',
        message: '',
        confirmLabel: 'Ya, lanjutkan',
        cancelLabel: 'Batal',
        danger: true,
        onConfirm: null,

        ask({ title = 'Yakin?', message = '', confirmLabel = 'Ya, lanjutkan', cancelLabel = 'Batal', danger = true, onConfirm = null }) {
            Object.assign(this, { title, message, confirmLabel, cancelLabel, danger, onConfirm, open: true });
        },

        accept() {
            const callback = this.onConfirm;
            this.close();

            if (typeof callback === 'function') {
                callback();
            }
        },

        close() {
            this.open = false;
            this.onConfirm = null;
        },
    });
};
