<?php

namespace Tests\Feature\Livewire\Store;

use App\Livewire\Store\ProductShow;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Storefront\Cart;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductShowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unknown_product_returns_404(): void
    {
        $this->get(route('product.show', 'tidak-ada'))->assertNotFound();
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = Product::factory()->inactive()->hasVariants(1)->create();

        $this->get(route('product.show', $product->slug))->assertNotFound();
    }

    public function test_adding_a_variant_puts_it_in_the_cart(): void
    {
        $product = Product::factory()->create(['name' => 'Jaket Windbreaker']);
        ProductVariant::factory()->for($product)->create(['name' => 'M - Hitam']);
        $large = ProductVariant::factory()->for($product)->create(['name' => 'L - Hitam', 'price_jpy' => 4800, 'price_idr' => 490000]);

        Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->set('variantId', $large->id)
            ->set('qty', 2)
            ->call('addToCart')
            ->assertHasNoErrors()
            ->assertSet('added', true)
            ->assertDispatched('cart-updated');

        $item = app(Cart::class)->items()->sole();
        $this->assertSame('Jaket Windbreaker', $item['name']);
        $this->assertSame('Varian L - Hitam', $item['detail']);
        $this->assertSame(2, $item['qty']);
        $this->assertSame(4800, $item['price_jpy']);
        $this->assertSame($large->id, $item['refs']['product_variant_id']);
    }

    public function test_rejects_a_variant_of_another_product(): void
    {
        $product = Product::factory()->hasVariants(1)->create();
        $otherVariant = ProductVariant::factory()->create();

        Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->set('variantId', $otherVariant->id)
            ->call('addToCart')
            ->assertHasErrors(['variantId' => 'exists']);

        $this->assertTrue(app(Cart::class)->items()->isEmpty());
    }

    public function test_rejects_an_unavailable_variant(): void
    {
        $product = Product::factory()->hasVariants(1)->create();
        $soldOut = ProductVariant::factory()->for($product)->create(['is_available' => false]);

        Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->set('variantId', $soldOut->id)
            ->call('addToCart')
            ->assertHasErrors(['variantId' => 'exists'])
            ->assertSee('Varian ini sedang tidak tersedia.');
    }
}
