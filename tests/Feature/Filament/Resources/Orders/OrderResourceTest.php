<?php

namespace Tests\Feature\Filament\Resources\Orders;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\CashAccount;
use App\Models\ExchangeRate;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OrderResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);
        ExchangeRate::factory()->create(['jpy_to_idr' => 105]);
    }

    private function orderAwaitingVerification(): Order
    {
        $order = Order::factory()->status(OrderStatus::MenungguVerifikasi)->create(['total' => 2800]);
        Payment::factory()->for($order)->for(CashAccount::where('name', 'Bank Jepang')->sole())->create(['amount' => 2800]);

        return $order;
    }

    public function test_admin_accepts_payment_from_the_order_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $order = $this->orderAwaitingVerification();

        Livewire::test(ViewOrder::class, ['record' => $order->getKey()])
            ->callAction('acceptPayment')
            ->assertNotified('Pembayaran diterima');

        $this->assertSame(OrderStatus::Dibayar, $order->refresh()->status);
        $this->assertSame(PaymentStatus::Diterima, $order->payments()->sole()->status);
    }

    public function test_admin_rejection_requires_a_reason(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $order = $this->orderAwaitingVerification();

        Livewire::test(ViewOrder::class, ['record' => $order->getKey()])
            ->callAction('rejectPayment', ['reason' => ''])
            ->assertHasActionErrors(['reason' => 'required']);

        $this->assertSame(OrderStatus::MenungguVerifikasi, $order->refresh()->status);
    }

    public function test_staff_does_not_see_payment_verification(): void
    {
        $this->actingAs(User::factory()->staff()->create());
        $order = $this->orderAwaitingVerification();

        Livewire::test(ViewOrder::class, ['record' => $order->getKey()])
            ->assertActionHidden('acceptPayment')
            ->assertActionHidden('rejectPayment');
    }

    /**
     * @return array<string, mixed>
     */
    private function manualOrderData(bool $paid): array
    {
        return [
            'source' => OrderSource::Whatsapp->value,
            'currency' => 'JPY',
            'customer_name' => 'Dewi',
            'customer_phone' => '080-1111-2222',
            'postal_code' => '460-0008',
            'prefecture' => 'Aichi',
            'city' => 'Nagoya-shi',
            'address_line1' => 'Sakae 1-1',
            'already_paid' => $paid,
            'cash_account_id' => $paid ? CashAccount::where('name', 'Bank Jepang')->value('id') : null,
            'payment_proof' => $paid ? UploadedFile::fake()->image('bukti.jpg') : null,
            'lines' => [['type' => 'product', 'qty' => 2, 'product_variant_id' => ProductVariant::factory()->create(['price_jpy' => 2400])->id]],
        ];
    }

    public function test_staff_manual_paid_order_waits_for_admin_verification(): void
    {
        Storage::fake('local');
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff);

        Livewire::test(CreateOrder::class)
            ->fillForm($this->manualOrderData(paid: true))
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::sole();
        $this->assertSame(OrderSource::Whatsapp, $order->source);
        $this->assertSame(4800, $order->total);
        $this->assertTrue($order->creator->is($staff));
        $this->assertSame(OrderStatus::MenungguVerifikasi, $order->status);
        Storage::disk('local')->assertExists($order->payments()->sole()->proof_path);
    }

    public function test_admin_manual_paid_order_is_paid_immediately(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateOrder::class)
            ->fillForm($this->manualOrderData(paid: true))
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::sole();
        $this->assertSame(OrderStatus::Dibayar, $order->status);
        $this->assertNotNull($order->preorder_batch_id);
    }

    public function test_unpaid_manual_order_awaits_payment(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        Livewire::test(CreateOrder::class)
            ->fillForm($this->manualOrderData(paid: false))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(OrderStatus::MenungguPembayaran, Order::sole()->status);
    }
}
