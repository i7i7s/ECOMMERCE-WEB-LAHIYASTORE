<?php

namespace Tests\Feature\Actions\Orders;

use App\Actions\Orders\CancelOrder;
use App\Actions\Payments\AcceptPayment;
use App\Enums\CashFlow;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CashAccount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CancelOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_cancelling_a_paid_order_refunds_it_from_the_same_account(): void
    {
        $this->seed(SystemSeeder::class);
        $admin = User::factory()->admin()->create();
        $account = CashAccount::where('name', 'Bank Jepang')->sole();
        $order = Order::factory()->status(OrderStatus::MenungguVerifikasi)->create(['total' => 2800, 'exchange_rate' => 105]);
        app(AcceptPayment::class)->handle(Payment::factory()->for($order)->for($account)->create(['amount' => 2800]), $admin);

        app(CancelOrder::class)->handle($order->refresh(), $admin, 'Pelanggan berubah pikiran');

        $order->refresh();
        $this->assertSame(OrderStatus::Dibatalkan, $order->status);
        $this->assertSame('Pelanggan berubah pikiran', $order->cancel_reason);
        $refund = $account->cashTransactions()->where('type', CashFlow::Keluar)->sole();
        $this->assertSame('refund', $refund->category->slug);
        $this->assertSame(2800, $refund->amount);
        $this->assertSame(294000, $refund->amount_base);
        $this->assertSame(0, $account->balance());
    }

    public function test_unpaid_order_is_cancelled_without_refund(): void
    {
        $order = Order::factory()->create();

        app(CancelOrder::class)->handle($order, null, 'Lewat batas waktu pembayaran');

        $this->assertSame(OrderStatus::Dibatalkan, $order->refresh()->status);
        $this->assertNull($order->statusHistories()->first()->user_id);
        $this->assertDatabaseCount('cash_transactions', 0);
    }

    public function test_order_in_production_cannot_be_cancelled(): void
    {
        $order = Order::factory()->status(OrderStatus::Diproduksi)->create();

        $this->expectException(BusinessRuleException::class);

        app(CancelOrder::class)->handle($order, User::factory()->admin()->create(), 'Terlambat');
    }

    public function test_only_admin_can_cancel_a_paid_order(): void
    {
        $order = Order::factory()->paid()->create();

        $this->expectExceptionMessage('Hanya admin yang bisa membatalkan order yang sudah dibayar.');

        app(CancelOrder::class)->handle($order, User::factory()->staff()->create(), 'Batal');
    }
}
