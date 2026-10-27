<?php

namespace Tests\Feature\Actions\Orders;

use App\Actions\Orders\ShipOrder;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CashAccount;
use App\Models\ExchangeRate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ShipOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);
        ExchangeRate::factory()->create(['effective_date' => now('Asia/Tokyo')->subDay()->toDateString(), 'jpy_to_idr' => 105]);
        $this->staff = User::factory()->staff()->create();
    }

    private function arrivedOrder(bool $packed): Order
    {
        $order = Order::factory()->paid()->status(OrderStatus::TibaDiJepang)->create();
        OrderItem::factory()->count(2)->for($order)->create(['is_packed' => $packed]);

        return $order;
    }

    public function test_shipping_requires_every_item_to_be_checked(): void
    {
        $order = $this->arrivedOrder(packed: false);

        $this->expectExceptionMessage('Centang semua item');

        app(ShipOrder::class)->handle($order, $this->staff, ['courier' => 'Yamato', 'tracking_number' => '1234']);
    }

    public function test_packed_order_is_shipped_with_courier_tracking_and_packer(): void
    {
        $order = $this->arrivedOrder(packed: true);

        app(ShipOrder::class)->handle($order, $this->staff, ['courier' => 'Yamato', 'tracking_number' => '4567-8901-2345']);

        $order->refresh();
        $this->assertSame(OrderStatus::Dikirim, $order->status);
        $this->assertSame('Yamato', $order->domestic_courier);
        $this->assertSame('4567-8901-2345', $order->tracking_number);
        $this->assertTrue($order->packer->is($this->staff));
        $this->assertNotNull($order->shipped_at);
        $this->assertDatabaseCount('cash_transactions', 0);
    }

    public function test_domestic_postage_is_booked_as_an_expense_of_the_order_and_batch(): void
    {
        $order = $this->arrivedOrder(packed: true);
        $cash = CashAccount::where('name', 'Kas Tunai')->sole();

        app(ShipOrder::class)->handle($order, $this->staff, ['courier' => 'Japan Post', 'tracking_number' => 'JP1', 'shipping_cost' => 520, 'cash_account_id' => $cash->id]);

        $expense = $cash->cashTransactions()->sole();
        $this->assertSame('ongkir_domestik', $expense->category->slug);
        $this->assertSame(520, $expense->amount);
        $this->assertTrue($expense->order->is($order));
        $this->assertSame($order->preorder_batch_id, $expense->preorder_batch_id);
    }

    public function test_only_arrived_orders_can_be_shipped(): void
    {
        $order = Order::factory()->paid()->create();

        $this->expectException(BusinessRuleException::class);

        app(ShipOrder::class)->handle($order, $this->staff, ['courier' => 'Yamato', 'tracking_number' => '1']);
    }

    public function test_shipped_orders_complete_automatically_after_seven_days(): void
    {
        $old = Order::factory()->status(OrderStatus::Dikirim)->create(['shipped_at' => now()->subDays(8)]);
        $recent = Order::factory()->status(OrderStatus::Dikirim)->create(['shipped_at' => now()->subDays(2)]);

        $this->artisan('orders:complete-delivered')->assertSuccessful();

        $this->assertSame(OrderStatus::Selesai, $old->refresh()->status);
        $this->assertNotNull($old->completed_at);
        $this->assertSame(OrderStatus::Dikirim, $recent->refresh()->status);
    }
}
