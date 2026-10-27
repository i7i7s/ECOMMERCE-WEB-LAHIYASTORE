<?php

namespace Tests\Feature\Database\Seeders;

use App\Enums\ProductSaleMode;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\PhoneCreditCatalogSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhoneCreditCatalogSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeds_iphone_16_to_18_pro_max_on_credit_with_a_picture_per_colour(): void
    {
        Storage::fake('public');

        $this->seed(PhoneCreditCatalogSeeder::class);

        $this->assertSame(12, Product::where('sale_mode', ProductSaleMode::Kredit)->count());

        $proMax = Product::where('slug', 'iphone-18-pro-max')->sole();
        $this->assertSame(['Black', 'Silver', 'Glacier', 'Burgundy'], $proMax->images->pluck('color_name')->all());
        $this->assertSame(16, $proMax->variants()->count());
        $this->assertSame(239800, $proMax->variants()->where('storage_gb', 256)->where('color_name', 'Glacier')->value('price_jpy'));
        Storage::disk('public')->assertExists('products/iphone/iphone-18-pro-max-burgundy.svg');
        $this->assertStringContainsString('<svg', Storage::disk('public')->get('products/iphone/iphone-18-pro-max-burgundy.svg'));
    }

    public function test_running_again_keeps_prices_the_admin_changed(): void
    {
        Storage::fake('public');
        $this->seed(PhoneCreditCatalogSeeder::class);
        ProductVariant::where('sku', 'IPHONE-16-128-BLACK')->update(['price_jpy' => 99000]);
        $count = ProductVariant::count();

        $this->seed(PhoneCreditCatalogSeeder::class);

        $this->assertSame(99000, ProductVariant::where('sku', 'IPHONE-16-128-BLACK')->value('price_jpy'));
        $this->assertSame($count, ProductVariant::count());
    }
}
