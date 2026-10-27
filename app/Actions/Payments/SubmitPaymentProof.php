<?php

namespace App\Actions\Payments;

use App\Actions\Orders\TransitionOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CashAccount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\OperationalAlerts;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Stores a bank transfer proof (kept private, PRD §9) and sends the order to verification.
 */
class SubmitPaymentProof
{
    public const PROOF_DISK = 'local';

    public function __construct(private TransitionOrderStatus $transition) {}

    /**
     * @param  UploadedFile|string  $proof  An upload, or a path already stored on the proof disk (admin form uploads).
     */
    public function handle(Order $order, UploadedFile|string $proof, ?CashAccount $account = null, ?User $by = null): Payment
    {
        if ($order->status !== OrderStatus::MenungguPembayaran) {
            throw new BusinessRuleException('Order ini tidak sedang menunggu pembayaran.');
        }

        $account ??= CashAccount::query()
            ->where('currency', $order->currency)
            ->where('is_active', true)
            ->orderByDesc('show_at_checkout')
            ->first();

        if ($account === null || $account->currency !== $order->currency) {
            throw new BusinessRuleException("Belum ada rekening {$order->currency->value} untuk menerima pembayaran.");
        }

        $path = $proof instanceof UploadedFile
            ? $proof->store("payment-proofs/{$order->code}", self::PROOF_DISK)
            : $this->moveStoredProof($proof, $order->code);

        return DB::transaction(function () use ($order, $account, $path, $by): Payment {
            $payment = $order->payments()->create([
                'cash_account_id' => $account->id,
                'currency' => $order->currency,
                'amount' => $order->total,
                'method' => PaymentMethod::TransferManual,
                'proof_path' => $path,
                'status' => PaymentStatus::Menunggu,
            ]);

            $this->transition->handle($order, OrderStatus::MenungguVerifikasi, $by, 'Bukti transfer diupload');

            OperationalAlerts::send('Bukti pembayaran baru', "{$order->code} menunggu verifikasi pembayaran.", route('filament.admin.resources.orders.view', ['record' => $order->id]));

            return $payment;
        });
    }

    private function moveStoredProof(string $path, string $orderCode): string
    {
        $disk = Storage::disk(self::PROOF_DISK);

        if (! $disk->exists($path)) {
            throw new BusinessRuleException('File bukti transfer tidak ditemukan. Upload ulang.');
        }

        $target = "payment-proofs/{$orderCode}/".basename($path);
        $disk->move($path, $target);

        return $target;
    }
}
