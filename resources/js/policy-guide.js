export default (config) => ({
    stage: 0,
    currency: config.currency,
    selectedStatus: 'dibayar',
    checked: [],
    resolution: 'replacement',
    copied: '',
    copyFailed: false,
    copyValue: '',
    get outcome() {
        return config.statuses[this.selectedStatus] ?? config.statuses.dibayar;
    },
    async copy(value, key) {
        this.copyFailed = false;
        this.copyValue = value;
        try {
            await navigator.clipboard.writeText(value);
            this.copied = key;
            setTimeout(() => { if (this.copied === key) this.copied = ''; }, 2500);
        } catch {
            this.copyFailed = true;
        }
    },
});
