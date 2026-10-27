/**
 * Alpine component for the custom case editor (resources/views/livewire/store/custom-case.blade.php).
 *
 * The original file is uploaded to Livewire as soon as it is picked; the editor
 * keeps the position, zoom and rotation, renders a small JPEG thumbnail and sends
 * those parameters to Livewire's addToCart(). Printing is done by the vendor, so
 * there is no resolution check here.
 */
export default (config) => ({
    src: null,
    fileName: '',
    fileError: '',
    imageWidth: 0,
    imageHeight: 0,
    zoom: 1,
    rotation: 0,
    x: 0,
    y: 0,
    drag: null,
    positionNote: '',
    agreeRights: false,
    agreeColor: false,
    agreeNoPreview: false,
    saving: false,
    uploading: false,
    uploaded: false,

    get hasPreview() {
        return this.$wire.hasPreview;
    },

    get blockedReason() {
        if (!this.src) {
            return 'Upload gambar dulu.';
        }

        if (this.uploading) {
            return 'Mengunggah gambar…';
        }

        if (!this.uploaded) {
            return 'Gambar gagal diunggah. Pilih ulang gambarnya.';
        }

        if (!this.agreeRights || !this.agreeColor || (!this.hasPreview && !this.agreeNoPreview)) {
            return 'Centang semua persetujuan untuk melanjutkan.';
        }

        return '';
    },

    get imageStyle() {
        return `transform: translate(${this.x}px, ${this.y}px) rotate(${this.rotation}deg) scale(${this.zoom})`;
    },

    pick(event) {
        const file = event.target.files[0];
        this.fileError = '';

        if (!file) {
            return;
        }

        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            this.fileError = 'Format harus JPG atau PNG.';

            return;
        }

        if (file.size > config.maxUploadMb * 1024 * 1024) {
            this.fileError = `Ukuran file maksimal ${config.maxUploadMb} MB.`;

            return;
        }

        const url = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            if (this.src) {
                URL.revokeObjectURL(this.src);
            }

            this.src = url;
            this.fileName = file.name;
            this.imageWidth = image.naturalWidth;
            this.imageHeight = image.naturalHeight;
            this.reset();
            this.upload(file);
        };

        image.onerror = () => {
            this.fileError = 'Gambar tidak bisa dibaca.';
        };

        image.src = url;
    },

    /** Sends the original file to the server (private storage) for the vendor's print files. */
    upload(file) {
        this.uploading = true;
        this.uploaded = false;

        this.$wire.upload(
            'designImage',
            file,
            () => {
                this.uploading = false;
                this.uploaded = true;
            },
            () => {
                this.uploading = false;
                this.fileError = navigator.onLine
                    ? 'Gambar gagal diunggah. Pilih ulang gambarnya, atau coba gambar yang lebih kecil.'
                    : 'Kamu sedang offline. Sambungkan internet, lalu pilih ulang gambarnya.';
            },
        );
    },

    /** Clears the picked image after it was added to the cart; each cart line needs its own upload. */
    clearImage() {
        if (this.src) {
            URL.revokeObjectURL(this.src);
        }

        this.src = null;
        this.fileName = '';
        this.imageWidth = 0;
        this.imageHeight = 0;
        this.uploaded = false;
        this.positionNote = '';
        this.reset();
    },

    reset() {
        this.zoom = 1;
        this.rotation = 0;
        this.x = 0;
        this.y = 0;
    },

    rotate() {
        this.rotation = (this.rotation + 90) % 360;
    },

    startDrag(event) {
        if (!this.src) {
            return;
        }

        this.drag = { startX: event.clientX, startY: event.clientY, x: this.x, y: this.y };
        event.currentTarget.setPointerCapture(event.pointerId);
    },

    moveDrag(event) {
        if (!this.drag) {
            return;
        }

        this.x = this.drag.x + event.clientX - this.drag.startX;
        this.y = this.drag.y + event.clientY - this.drag.startY;
    },

    endDrag() {
        this.drag = null;
    },

    /** Small JPEG of what the preview box shows, for the cart and checkout. */
    async thumbnail() {
        const box = this.$refs.printArea;

        if (!this.src || !box) {
            return null;
        }

        const image = new Image();
        image.src = this.src;
        await image.decode();

        const width = 180;
        const factor = width / box.clientWidth;
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = Math.round(box.clientHeight * factor);

        const context = canvas.getContext('2d');
        const drawWidth = box.clientWidth * factor;
        const drawHeight = drawWidth * (this.imageHeight / this.imageWidth);

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.translate(canvas.width / 2 + this.x * factor, canvas.height / 2 + this.y * factor);
        context.rotate((this.rotation * Math.PI) / 180);
        context.scale(this.zoom, this.zoom);
        context.drawImage(image, -drawWidth / 2, -drawHeight / 2, drawWidth, drawHeight);
        context.setTransform(1, 0, 0, 1, 0, 0);
        await this.drawCamera(context, canvas.width, canvas.height);

        return canvas.toDataURL('image/jpeg', 0.8);
    },

    /** Draws the phone's camera overlay (an inline SVG) on top of the thumbnail. */
    async drawCamera(context, width, height) {
        const svg = this.$refs.camera;

        if (!svg) {
            return;
        }

        const camera = new Image();
        camera.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(new XMLSerializer().serializeToString(svg));

        try {
            await camera.decode();
            context.drawImage(camera, 0, 0, width, height);
        } catch {
            // The thumbnail is still useful without the camera.
        }
    },

    async submit() {
        if (this.blockedReason || this.saving) {
            return;
        }

        this.saving = true;

        try {
            await this.$wire.addToCart({
                file_name: this.fileName,
                image_width: this.imageWidth,
                image_height: this.imageHeight,
                zoom: this.zoom,
                rotation: this.rotation,
                pos_x: this.x,
                pos_y: this.y,
                thumbnail: await this.thumbnail(),
                position_note: this.hasPreview ? null : this.positionNote || null,
                agree_rights: this.agreeRights,
                agree_color: this.agreeColor,
                agree_no_preview: this.hasPreview ? null : this.agreeNoPreview,
            });

            if (this.$wire.added) {
                this.clearImage();
            }
        } finally {
            this.saving = false;
        }
    },
});
