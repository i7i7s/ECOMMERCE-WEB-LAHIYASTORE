<?php

namespace Tests\Feature\Actions\Payments;

use App\Actions\Payments\AcceptPayment;
use App\Enums\BatchStatus;
use App\Enums\CashFlow;
use App\Enums\Currency;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CashAccount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PreorderBatch;
use App\Models\User;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AcceptPaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    private function pendingPayment(Currency $currency = Currency::Jpy, int $amount = 2800): Payment
    {
        $order = Order::factory()->status(OrderStatus::MenungguVerifikasi)->create(['currency' => $currency, 'total' => $amount, 'exchange_rate' => 105]);
        $account = CashAccount::where('currency', $currency)->where('type', 'bank')->firstOrFail();

        return Payment::factory()->for($order)->for($account)->create(['currency' => $currency, 'amount' => $amount]);
    }

    public function test_books_income_in_base_currency_and_puts_order_in_the_open_batch(): void
    {
        $payment = $this->pendingPayment();
        $openBatch = PreorderBatch::open()->sole();

        app(AcceptPayment::class)->handle($payment, $this->admin);

        $payment->refresh();
        $this->assertSame(PaymentStatus::Diterima, $payment->status);
        $this->assertTrue($payment->verifier->is($this->admin));

        $transaction = $payment->cashTransaction;
        $this->assertSame(CashFlow::Masuk, $transaction->type);
        $this->assertSame('penjualan', $transaction->category->slug);
        $this->assertSame(2800, $transaction->amount);
        $this->assertSame(294000, $transaction->amount_base);
        $this->assertSame(2800, $payment->cashAccount->balance());

        $order = $payment->order->refresh();
        $this->assertSame(OrderStatus::Dibayar, $order->status);
        $this->assertTrue($order->batch->is($openBatch));
        $this->assertNotNull($order->paid_at);
        $this->assertNull($order->payment_due_at);
    }

    public function test_rupiah_payment_is_booked_as_is(): void
    {
        $payment = $this->pendingPayment(Currency::Idr, 285000);

        app(AcceptPayment::class)->handle($payment, $this->admin);

        $this->assertSame(285000, $payment->refresh()->cashTransaction->amount_base);
    }

    public function test_opens_a_batch_when_none_is_open(): void
    {
        PreorderBatch::query()->update(['status' => BatchStatus::Ditutup]);
        $payment = $this->pendingPayment();

        app(AcceptPayment::class)->handle($payment, $this->admin);

        $this->assertSame(BatchStatus::Dibuka, $payment->order->refresh()->batch->status);
    }

    public function test_staff_cannot_accept_payments(): void
    {
        $payment = $this->pendingPayment();

        $this->expectException(BusinessRuleException::class);

        try {
            app(AcceptPayment::class)->handle($payment, User::factory()->staff()->create());
        } finally {
            $this->assertSame(PaymentStatus::Menunggu, $payment->refresh()->status);
            $this->assertNull($payment->cashTransaction);
        }
    }

    public function test_a_payment_cannot_be_accepted_twice(): void
    {
        $payment = $this->pendingPayment();
        app(AcceptPayment::class)->handle($payment, $this->admin);

        $this->expectExceptionMessage('Pembayaran ini sudah diproses.');

        app(AcceptPayment::class)->handle($payment->refresh(), $this->admin);
    }
}
