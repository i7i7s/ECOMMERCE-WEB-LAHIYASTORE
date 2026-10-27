<?php

namespace Tests\Feature\Livewire\Store;

use App\Livewire\Store\Catalog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function product(Category $category, string $name, int $priceJpy, int $priceIdr = 100000): Product
    {
        return Product::factory()
            ->for($category)
            ->has(ProductVariant::factory()->state(['price_jpy' => $priceJpy, 'price_idr' => $priceIdr]), 'variants')
            ->create(['name' => $name]);
    }

    public function test_category_from_query_string_filters_products(): void
    {
        $jaket = Category::factory()->create(['slug' => 'jaket']);
        $baju = Category::factory()->create(['slug' => 'baju']);
        $this->product($jaket, 'Jaket Denim', 6200);
        $this->product($baju, 'Kaos Oversize', 2400);

        $this->get(route('catalog', ['kategori' => 'jaket']))
            ->assertOk()
            ->assertSee('Jaket Denim')
            ->assertDontSee('Kaos Oversize');
    }

    public function test_hides_inactive_products_and_products_without_available_variants(): void
    {
        $category = Category::factory()->create();
        $this->product($category, 'Tampil', 1000);
        $this->product($category, 'Disembunyikan', 1000)->update(['is_active' => false]);
        $this->product($category, 'Habis', 1000)->variants()->update(['is_available' => false]);
        $this->product(Category::factory()->create(['is_active' => false]), 'Kategori Nonaktif', 1000);

        Livewire::test(Catalog::class)
            ->assertSee('Tampil')
            ->assertDontSee('Disembunyikan')
            ->assertDontSee('Habis')
            ->assertDontSee('Kategori Nonaktif');
    }

    public function test_unknown_category_falls_back_to_all_products(): void
    {
        $this->product(Category::factory()->create(), 'Kaos Oversize', 2400);

        Livewire::test(Catalog::class)
            ->call('selectCategory', 'mainan')
            ->assertSet('category', '')
            ->assertSee('Kaos Oversize');
    }

    public function test_sorting_by_lowest_price_lists_cheapest_product_first(): void
    {
        $category = Category::factory()->create();
        $this->product($category, 'Power Bank', 3500);
        $this->product($category, 'Kabel USB-C', 900);
        $this->product($category, 'Kaos Oversize', 2400);

        Livewire::test(Catalog::class)
            ->set('sort', 'termurah')
            ->assertSeeInOrder(['Kabel USB-C', 'Kaos Oversize', 'Power Bank']);
    }

    public function test_product_with_several_prices_shows_lowest_as_starting_price(): void
    {
        $product = $this->product(Category::factory()->create(), 'Sneakers', 5800);
        ProductVariant::factory()->for($product)->create(['price_jpy' => 5200]);

        Livewire::test(Catalog::class)->assertSee('Mulai ¥5.200');
    }

    public function test_prices_are_shown_in_rupiah_when_idr_is_selected(): void
    {
        $this->product(Category::factory()->create(), 'Kabel USB-C', 900, 92000);

        $this->withSession(['currency' => 'IDR'])
            ->get(route('catalog'))
            ->assertSee('Rp92.000')
            ->assertDontSee('¥900');
    }
}
