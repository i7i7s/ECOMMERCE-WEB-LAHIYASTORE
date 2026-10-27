<?php

namespace App\Livewire\Store;

use App\Enums\Currency;
use App\Enums\OrderStatus;
use App\Models\Setting;
use App\Support\Storefront\PolicyContent;
use App\Support\Storefront\Storefront;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PolicyDetail extends Component
{
    public const TOPICS = [
        'preorder' => ['slug' => 'preorder', 'title' => 'Preorder', 'heading' => 'Dibuat untukmu.', 'accent' => 'Perjalanannya jelas.', 'intro' => 'Dari gambar pilihanmu sampai paket di depan pintu. Kenali setiap tahap pesanan dan jadwal batch kami.'],
        'payment' => ['slug' => 'pembayaran', 'title' => 'Pembayaran', 'heading' => 'Yen atau Rupiah.', 'accent' => 'Kamu yang pilih.', 'intro' => 'Pahami rekening tujuan, bukti yang perlu diunggah, dan apa yang terjadi setelah kamu membayar.'],
        'cancellation' => ['slug' => 'pembatalan', 'title' => 'Pembatalan', 'heading' => 'Rencana berubah?', 'accent' => 'Cek pilihanmu.', 'intro' => 'Tahap pesanan menentukan pilihan pembatalan. Lihat statusnya dulu, lalu ikuti langkah yang sesuai.'],
        'complaints' => ['slug' => 'retur-refund', 'title' => 'Retur & refund', 'heading' => 'Ada yang kurang pas?', 'accent' => 'Mari kita periksa.', 'intro' => 'Ketahui batas pengajuan, siapkan bukti, dan pahami pilihan penyelesaian untuk masalah pada pesanan.'],
    ];

    #[Locked]
    public string $topic = '';

    public string $shippedDate = '';

    public string $receivedDate = '';

    public function mount(string $topic): void
    {
        abort_unless(in_array($topic, array_column(self::TOPICS, 'slug'), true), 404);
        foreach (self::TOPICS as $name => $metadata) {
            if ($metadata['slug'] === $topic) {
                $this->topic = $name;
                break;
            }
        }
    }

    /** @return array{state: string, heading: string, body: string, deadline?: string} */
    #[Computed]
    public function complaintWindow(): array
    {
        if ($this->shippedDate === '' || $this->receivedDate === '') {
            return ['state' => 'empty', 'heading' => 'Isi dua tanggal di samping.', 'body' => 'Kami akan menunjukkan batas pengajuan berdasarkan tanggal pengiriman dan penerimaan paket.'];
        }
        $validator = Validator::make([
            'shipped' => $this->shippedDate, 'received' => $this->receivedDate,
        ], ['shipped' => ['date_format:Y-m-d'], 'received' => ['date_format:Y-m-d']]);
        if ($validator->fails()) {
            return ['state' => 'invalid', 'heading' => 'Periksa kembali tanggalnya.', 'body' => 'Gunakan tanggal kalender yang valid.'];
        }
        $today = CarbonImmutable::now('Asia/Tokyo')->startOfDay();
        $shipped = CarbonImmutable::parse($this->shippedDate, 'Asia/Tokyo')->startOfDay();
        $received = CarbonImmutable::parse($this->receivedDate, 'Asia/Tokyo')->startOfDay();
        if ($received->lt($shipped) || $received->gt($today) || $shipped->gt($today)) {
            return ['state' => 'invalid', 'heading' => 'Urutan tanggal belum sesuai.', 'body' => 'Tanggal diterima harus sesudah atau sama dengan tanggal pengiriman, dan tidak melewati hari ini di Jepang.'];
        }
        $receivedDeadline = $received->addDays((int) Setting::valueOf('complaint_days_after_received', '3'));
        $shippedDeadline = $shipped->addDays((int) Setting::valueOf('complaint_days_after_shipped', '10'));
        $deadline = $receivedDeadline->lt($shippedDeadline) ? $receivedDeadline : $shippedDeadline;
        $expired = $today->gt($deadline);

        return [
            'state' => $expired ? 'expired' : 'within',
            'heading' => $expired ? 'Batas waktu sudah lewat.' : 'Masih dalam batas pengajuan.',
            'body' => $expired ? 'Hubungi tim bila ada hal yang perlu dijelaskan tentang pesananmu.' : 'Siapkan video unboxing, foto masalah, dan kode order untuk diperiksa tim.',
            'deadline' => $deadline->locale('id')->translatedFormat('j F Y').' JST',
        ];
    }

    /** @return array<string, array{label: string, allowed: bool, heading: string, body: string}> */
    private function cancellationStatuses(): array
    {
        $statuses = [];
        foreach (OrderStatus::cases() as $status) {
            $allowed = $status->canTransitionTo(OrderStatus::Dibatalkan);
            $heading = $allowed ? 'Pembatalan dapat diajukan.' : 'Pesanan tidak bisa dibatalkan.';
            $body = $allowed ? 'Hubungi tim dengan kode order. Tim akan memeriksa status terbaru sebelum memproses permintaan.' : 'Pesanan sudah masuk tahap produksi atau sesudahnya. Jika ada masalah setelah diterima, lihat panduan retur dan refund.';
            if ($status === OrderStatus::Dibayar) {
                $body = 'Selama belum diproduksi, kamu boleh mengajukan pembatalan dengan refund penuh. Tim memeriksa status dan memproses pengembalian dana.';
            } elseif ($status === OrderStatus::MenungguVerifikasi) {
                $body = 'Bukti bayar sedang diperiksa. Hubungi tim agar pembayaran dan permintaan pembatalan diperiksa bersama.';
            } elseif ($status === OrderStatus::Dibatalkan) {
                $heading = 'Pesanan sudah dibatalkan.';
                $body = 'Lihat riwayat pesanan. Jika sebelumnya sudah dibayar, hubungi tim untuk informasi pengembalian dana.';
            }
            $statuses[$status->value] = ['label' => $status->getLabel(), 'allowed' => $allowed, 'heading' => $heading, 'body' => $body];
        }

        return $statuses;
    }

    public function render(): View
    {
        abort_unless(isset(self::TOPICS[$this->topic]), 404);
        $storefront = app(Storefront::class);
        $settings = Setting::query()->pluck('value', 'key');
        $limits = [
            'etaMin' => (int) ($settings['eta_min_days'] ?? 14),
            'etaMax' => (int) ($settings['eta_max_days'] ?? 21),
            'paymentHours' => (int) ($settings['payment_window_hours'] ?? 24),
            'proofMb' => (int) ($settings['max_proof_upload_mb'] ?? 5),
            'receivedDays' => (int) ($settings['complaint_days_after_received'] ?? 3),
            'shippedDays' => (int) ($settings['complaint_days_after_shipped'] ?? 10),
        ];
        $topic = self::TOPICS[$this->topic];
        $accounts = [];
        if ($this->topic === 'payment') {
            foreach (Currency::cases() as $currency) {
                $accounts[$currency->value] = $storefront->checkoutAccount($currency);
            }
        }

        return view('livewire.store.policy-detail', [
            'metadata' => $topic, 'topics' => self::TOPICS, 'limits' => $limits,
            'policyText' => PolicyContent::sections()[$this->topic][1],
            'batch' => $storefront->openBatch(), 'cutoffLabel' => $storefront->cutoffLabel(),
            'etaLabel' => $storefront->etaLabel(), 'accounts' => $accounts,
            'today' => CarbonImmutable::now('Asia/Tokyo')->toDateString(),
            'supportPhone' => preg_replace('/\\D/', '', (string) ($settings['store_whatsapp'] ?? '')),
            'guideState' => ['currency' => Currency::current()->value, 'statuses' => $this->cancellationStatuses()],
        ])->title($topic['title'].' · Panduan Lahiya');
    }
}
