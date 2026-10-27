<?php

namespace Tests\Feature\Livewire\Store;

use App\Livewire\Store\CartPage;
use App\Models\User;
use App\Support\Storefront\Cart;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function addItem(array $overrides = []): string
    {
        $cart = app(Cart::class);
        $cart->add([
            'type' => 'product',
            'name' => 'Jaket Windbreaker',
            'detail' => 'Varian M - Hitam',
            'price_jpy' => 4800,
            'price_idr' => 490000,
            ...$overrides,
        ]);

        return $cart->items()->last()['id'];
    }

    public function test_empty_cart_offers_ways_to_start_shopping(): void
    {
        $this->actingAs(User::factory()->create())->get(route('cart'))
            ->assertOk()
            ->assertSee('Keranjangmu masih kosong.');
    }

    public function test_changing_quantity_updates_the_total(): void
    {
        $id = $this->addItem();

        Livewire::test(CartPage::class)
            ->call('increment', $id)
            ->assertDispatched('cart-updated')
            ->assertSee('Subtotal (2 item)')
            ->assertSee('¥9.600')
            ->call('decrement', $id)
            ->assertSee('Subtotal (1 item)');
    }

    public function test_quantity_stays_between_1_and_9(): void
    {
        $id = $this->addItem(['qty' => 9]);

        Livewire::test(CartPage::class)->call('increment', $id);
        $this->assertSame(9, app(Cart::class)->items()->sole()['qty']);

        app(Cart::class)->updateQuantity($id, 1);
        Livewire::test(CartPage::class)->call('decrement', $id);
        $this->assertSame(1, app(Cart::class)->items()->sole()['qty']);
    }

    public function test_removing_an_item_empties_the_cart(): void
    {
        $id = $this->addItem();

        Livewire::test(CartPage::class)
            ->call('remove', $id)
            ->assertSee('Keranjangmu masih kosong.');
    }

    public function test_item_without_preview_shows_confirmation_notice(): void
    {
        $this->addItem(['type' => 'custom_case', 'name' => 'Custom Casing (tanpa preview)', 'needs_confirmation' => true]);

        Livewire::test(CartPage::class)->assertSee('Ada item tanpa preview.');
    }

    public function test_total_is_shown_in_rupiah_when_idr_is_selected(): void
    {
        $this->addItem();

        $this->actingAs(User::factory()->create())
            ->withSession(['currency' => 'IDR', 'cart.items' => session('cart.items')])
            ->get(route('cart'))
            ->assertSee('Rp490.000');
    }
}
