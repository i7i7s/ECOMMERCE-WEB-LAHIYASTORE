<?php

namespace Tests\Feature\Actions\Payments;

use App\Actions\Payments\RejectPayment;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RejectPaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_rejected_proof_reopens_a_24_hour_payment_window(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01 05:00', 'UTC'));
        $order = Order::factory()->status(OrderStatus::MenungguVerifikasi)->create();
        $payment = Payment::factory()->for($order)->create();

        app(RejectPayment::class)->handle($payment, User::factory()->admin()->create(), 'Nominal tidak sesuai');

        $this->assertSame(PaymentStatus::Ditolak, $payment->refresh()->status);
        $this->assertSame('Nominal tidak sesuai', $payment->reject_reason);
        $order->refresh();
        $this->assertSame(OrderStatus::MenungguPembayaran, $order->status);
        $this->assertSame('2026-10-02 05:00', $order->payment_due_at->format('Y-m-d H:i'));
        $this->assertSame('Bukti ditolak: Nominal tidak sesuai', $order->statusHistories()->first()->note);
    }
}
