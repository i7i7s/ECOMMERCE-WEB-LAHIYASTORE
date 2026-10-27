<?php

namespace Tests\Feature\Livewire\Store;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Livewire\Store\Checkout;
use App\Livewire\Store\PaymentPanel;
use App\Models\Address;
use App\Models\CashAccount;
use App\Models\ExchangeRate;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Storefront\Cart;
use App\Support\Storefront\OrderTracker;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function fillCart(): ProductVariant
    {
        $variant = ProductVariant::factory()->create(['price_jpy' => 4800, 'price_idr' => 490000]);

        app(Cart::class)->add([
            'type' => 'product',
            'name' => 'Jaket',
            'detail' => 'Varian M',
            'price_jpy' => 1,
            'price_idr' => 1,
            'refs' => ['product_variant_id' => $variant->id],
        ]);

        return $variant;
    }

    private function withValidAddress(): Testable
    {
        return Livewire::test(Checkout::class)
            ->set('name', 'Raka')
            ->set('phone', '080-1234-5678')
            ->set('postalCode', '530-0001')
            ->set('prefecture', 'Osaka')
            ->set('city', 'Osaka-shi Kita-ku')
            ->set('addressLine1', 'Umeda 1-2-3');
    }

    private function placeOrder(): Testable
    {
        return $this->withValidAddress()
            ->call('toSummary')
            ->set('agreePolicy', true)
            ->call('placeOrder');
    }

    public function test_empty_cart_redirects_to_cart_page(): void
    {
        Livewire::test(Checkout::class)->assertRedirect(route('cart'));
    }

    public function test_address_step_rejects_missing_fields(): void
    {
        $this->fillCart();

        Livewire::test(Checkout::class)
            ->call('toSummary')
            ->assertHasErrors(['name' => 'required', 'phone' => 'required', 'postalCode' => 'required', 'prefecture' => 'required', 'city' => 'required', 'addressLine1' => 'required'])
            ->assertSet('step', 1);
    }

    public function test_address_step_rejects_non_japanese_postal_code(): void
    {
        $this->fillCart();

        $this->withValidAddress()
            ->set('postalCode', '12345')
            ->call('toSummary')
            ->assertHasErrors('postalCode')
            ->assertSee('Kode pos Jepang 7 digit, contoh 530-0001.');
    }

    public function test_order_requires_policy_agreement(): void
    {
        $this->fillCart();

        $this->withValidAddress()
            ->call('toSummary')
            ->call('placeOrder')
            ->assertHasErrors(['agreePolicy' => 'accepted'])
            ->assertSet('step', 2);
    }

    public function test_placing_order_saves_it_with_database_prices_and_clears_cart(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01 05:20', 'UTC'));
        ExchangeRate::factory()->create(['jpy_to_idr' => 105]);
        $this->fillCart();

        $this->placeOrder()
            ->assertHasNoErrors()
            ->assertSet('step', 3)
            ->assertSet('orderCode', 'LS-20261001-001')
            ->assertDispatched('cart-updated')
            ->assertSee('Bayar sebelum');

        $order = Order::sole();
        $this->assertSame(OrderSource::Website, $order->source);
        $this->assertSame(OrderStatus::MenungguPembayaran, $order->status);
        $this->assertSame(4800, $order->total);
        $this->assertSame('2026-10-02 05:20', $order->payment_due_at->format('Y-m-d H:i'));
        $this->assertTrue(app(Cart::class)->items()->isEmpty());
    }

    public function test_signed_in_customer_gets_default_address_and_order_is_linked_to_account(): void
    {
        ExchangeRate::factory()->create();
        $customer = User::factory()->create(['name' => 'Raka']);
        Address::factory()->for($customer)->create(['prefecture' => 'Aichi', 'city' => 'Nagoya-shi', 'is_default' => true]);
        $this->actingAs($customer);
        $this->fillCart();

        Livewire::test(Checkout::class)
            ->assertSet('prefecture', 'Aichi')
            ->assertSet('city', 'Nagoya-shi')
            ->call('toSummary')
            ->set('agreePolicy', true)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::sole();
        $this->assertTrue($order->user->is($customer));
        $this->assertSame('Aichi', $order->prefecture);
        $this->assertSame(1, $customer->addresses()->count());
    }

    public function test_signed_in_customer_can_save_a_new_address(): void
    {
        ExchangeRate::factory()->create();
        $customer = User::factory()->create();
        $this->actingAs($customer);
        $this->fillCart();

        $this->placeOrder()->assertHasNoErrors();

        $address = $customer->addresses()->sole();
        $this->assertSame('Osaka', $address->prefecture);
        $this->assertTrue($address->is_default);
    }

    public function test_shows_error_and_keeps_cart_when_store_has_no_exchange_rate(): void
    {
        $this->fillCart();

        $this->placeOrder()
            ->assertHasErrors('order')
            ->assertSee('kurs JPY→IDR belum diatur admin')
            ->assertSet('step', 2);

        $this->assertSame(0, Order::count());
        $this->assertCount(1, app(Cart::class)->items());
    }

    /**
     * Proof upload itself is covered by PaymentPanelTest; checkout hands over to that panel.
     */
    public function test_placed_order_shows_the_payment_panel_that_can_pay_it(): void
    {
        ExchangeRate::factory()->create();
        CashAccount::factory()->create(['bank_name' => 'Yucho']);
        $this->fillCart();

        $this->placeOrder()
            ->assertSet('step', 3)
            ->assertSeeLivewire(PaymentPanel::class);

        $order = Order::sole();
        $this->assertSame(OrderStatus::MenungguPembayaran, $order->status);
        $this->assertNotNull(app(OrderTracker::class)->tracked($order->code));
    }
}
