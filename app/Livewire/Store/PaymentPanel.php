<?php

namespace App\Livewire\Store;

use App\Actions\Payments\SubmitPaymentProof;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\Setting;
use App\Support\Storefront\OrderTracker;
use App\Support\Storefront\Storefront;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Bank details and transfer proof upload for one order (PAY-01). Used right after checkout and on
 * the tracking page, so a customer can pay later or upload again after a rejected proof.
 * Only visitors who may track the order (OrderTracker) can see or use it.
 */
class PaymentPanel extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $orderCode = '';

    /** Checkout shows the deadline in its own header. */
    #[Locked]
    public bool $showDue = true;

    public ?TemporaryUploadedFile $proof = null;

    public bool $proofUploaded = false;

    public function mount(string $orderCode, bool $showDue = true): void
    {
        abort_if(app(OrderTracker::class)->tracked($orderCode) === null, 404);

        $this->orderCode = $orderCode;
        $this->showDue = $showDue;
    }

    public function uploadProof(SubmitPaymentProof $submitPaymentProof, OrderTracker $tracker): void
    {
        $maxMb = (int) Setting::valueOf('max_proof_upload_mb', '5');

        $this->validate(
            ['proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:'.($maxMb * 1024)]],
            [
                'proof.required' => 'Pilih foto atau PDF bukti transfer.',
                'proof.mimes' => 'Bukti harus berupa JPG, PNG, atau PDF.',
                'proof.max' => "Ukuran bukti maksimal {$maxMb} MB.",
                'proof.uploaded' => 'Bukti gagal diunggah. Periksa koneksi lalu coba lagi.',
            ],
        );

        $order = $tracker->tracked($this->orderCode);

        if ($order === null) {
            $this->addError('proof', 'Order tidak ditemukan.');

            return;
        }

        if ($this->isOverdue($order)) {
            $this->addError('proof', 'Batas waktu pembayaran sudah lewat.');

            return;
        }

        try {
            $submitPaymentProof->handle($order, $this->proof);
        } catch (BusinessRuleException $exception) {
            $this->addError('proof', $exception->getMessage());

            return;
        }

        $this->reset('proof');
        $this->proofUploaded = true;
        $this->dispatch('payment-submitted');
    }

    private function isOverdue(Order $order): bool
    {
        return $order->status === OrderStatus::MenungguPembayaran && $order->payment_due_at?->isPast();
    }

    public function render(): View
    {
        $order = app(OrderTracker::class)->tracked($this->orderCode);
        $whatsapp = Setting::valueOf('store_whatsapp');

        return view('livewire.store.payment-panel', [
            'order' => $order,
            'status' => $order?->status,
            'account' => $order ? app(Storefront::class)->checkoutAccount($order->currency) : null,
            'rejection' => $order?->status === OrderStatus::MenungguPembayaran
                ? $order->payments()->where('status', PaymentStatus::Ditolak)->latest()->value('reject_reason')
                : null,
            'overdue' => $order !== null && $this->isOverdue($order),
            'paymentDue' => $order?->payment_due_at?->setTimezone('Asia/Tokyo')->locale('id')->translatedFormat('l, j M Y · H.i').' JST',
            'maxMb' => (int) Setting::valueOf('max_proof_upload_mb', '5'),
            'whatsappUrl' => $whatsapp ? 'https://wa.me/'.preg_replace('/\D/', '', $whatsapp) : null,
        ]);
    }
}
