<?php

namespace Database\Seeders;

use App\Enums\ProductSaleMode;
use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Support\PhoneRender;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * iPhones sold on credit, iPhone 16 up to iPhone 18 Pro Max, with official colours and
 * Apple Store Japan prices (tax included, after the July 2026 price change; models Apple no
 * longer sells use their last official price). Pictures are drawn renders (PhoneRender);
 * the admin can replace them with photos and adjust every price.
 *
 * Safe to run again: existing products, variants and photos are left as the admin edited them.
 */
class PhoneCreditCatalogSeeder extends Seeder
{
    /**
     * slug => [name, mockup, screen size in inches, colours (name => hex), storage GB => price JPY]
     *
     * @var array<string, array{string, string, string, array<string, string>, array<int, int>}>
     */
    private const PHONES = [
        'iphone-16e' => ['iPhone 16e', 'iphone-16e', '6,1', ['Black' => '#2e2e30', 'White' => '#f3f3ef'], [128 => 99800, 256 => 114800]],
        'iphone-16' => ['iPhone 16', 'iphone-16', '6,1', ['Black' => '#3b3b3d', 'White' => '#f2f2ee', 'Pink' => '#f1b6c6', 'Teal' => '#a8d5cc', 'Ultramarine' => '#7b8be0'], [128 => 124800, 256 => 149800]],
        'iphone-16-plus' => ['iPhone 16 Plus', 'iphone-16-plus', '6,7', ['Black' => '#3b3b3d', 'White' => '#f2f2ee', 'Pink' => '#f1b6c6', 'Teal' => '#a8d5cc', 'Ultramarine' => '#7b8be0'], [128 => 139800, 256 => 154800]],
        'iphone-16-pro' => ['iPhone 16 Pro', 'iphone-16-pro', '6,3', ['Black Titanium' => '#3c3c3e', 'White Titanium' => '#efeee9', 'Natural Titanium' => '#c2bcb2', 'Desert Titanium' => '#c9b59c'], [128 => 159800, 256 => 174800, 512 => 204800]],
        'iphone-16-pro-max' => ['iPhone 16 Pro Max', 'iphone-16-pro-max', '6,9', ['Black Titanium' => '#3c3c3e', 'White Titanium' => '#efeee9', 'Natural Titanium' => '#c2bcb2', 'Desert Titanium' => '#c9b59c'], [256 => 189800, 512 => 219800, 1024 => 249800]],
        'iphone-17e' => ['iPhone 17e', 'iphone-17e', '6,1', ['Black' => '#2e2e30', 'White' => '#f3f3ef', 'Soft Pink' => '#f4d6dc'], [256 => 107800, 512 => 142800]],
        'iphone-17' => ['iPhone 17', 'iphone-17', '6,3', ['Black' => '#353537', 'White' => '#f4f4f1', 'Mist Blue' => '#a9c1dc', 'Sage' => '#b7c4a7', 'Lavender' => '#cdbfe3'], [256 => 142800, 512 => 177800]],
        'iphone-air' => ['iPhone Air', 'iphone-air', '6,5', ['Space Black' => '#2b2b2e', 'Cloud White' => '#f3f3f1', 'Light Gold' => '#e9dcc3', 'Sky Blue' => '#c5daea'], [256 => 177800, 512 => 212800, 1024 => 247800]],
        'iphone-17-pro' => ['iPhone 17 Pro', 'iphone-17-pro', '6,3', ['Cosmic Orange' => '#d9773a', 'Deep Blue' => '#3c4a5e', 'Silver' => '#e3e4e6'], [256 => 194800, 512 => 229800, 1024 => 264800]],
        'iphone-17-pro-max' => ['iPhone 17 Pro Max', 'iphone-17-pro-max', '6,9', ['Cosmic Orange' => '#d9773a', 'Deep Blue' => '#3c4a5e', 'Silver' => '#e3e4e6'], [256 => 214800, 512 => 249800, 1024 => 284800, 2048 => 354800]],
        'iphone-18-pro' => ['iPhone 18 Pro', 'iphone-18-pro', '6,3', ['Black' => '#2b2b2d', 'Silver' => '#e2e3e5', 'Glacier' => '#cfe0ea', 'Burgundy' => '#6b2a35'], [256 => 219800, 512 => 254800, 1024 => 324800, 2048 => 429800]],
        'iphone-18-pro-max' => ['iPhone 18 Pro Max', 'iphone-18-pro-max', '6,9', ['Black' => '#2b2b2d', 'Silver' => '#e2e3e5', 'Glacier' => '#cfe0ea', 'Burgundy' => '#6b2a35'], [256 => 239800, 512 => 274800, 1024 => 344800, 2048 => 449800]],
    ];

    public function run(): void
    {
        $category = Category::firstOrCreate(['slug' => 'iphone'], ['name' => 'iPhone', 'sort_order' => 0]);
        $rate = (float) (ExchangeRate::effectiveOn()?->jpy_to_idr ?? 110);
        $disk = Storage::disk('public');

        foreach (self::PHONES as $slug => [$name, $mockup, $screen, $colors, $prices]) {
            $storages = collect(array_keys($prices))->map(fn (int $gb): string => $gb >= 1024 ? ($gb / 1024).' TB' : "{$gb} GB")->implode(', ');

            $product = Product::firstOrCreate(['slug' => $slug], [
                'category_id' => $category->id,
                'name' => $name,
                'sale_mode' => ProductSaleMode::Kredit,
                'description' => "{$name} dengan layar {$screen} inci, pilihan {$storages}. Bisa dicicil lewat Lahiya Store setelah akunmu diverifikasi admin. Gambar adalah ilustrasi; warna asli bisa sedikit berbeda.",
            ]);

            $sort = 0;

            foreach ($colors as $color => $hex) {
                $path = "products/iphone/{$slug}-".Str::slug($color).'.svg';
                $disk->put($path, PhoneRender::svg($mockup, $hex));
                $product->images()->firstOrCreate(['path' => $path], ['color_name' => $color, 'sort_order' => $sort++]);

                foreach ($prices as $gb => $priceJpy) {
                    $storage = $gb >= 1024 ? ($gb / 1024).' TB' : "{$gb} GB";

                    $product->variants()->firstOrCreate(['sku' => strtoupper(Str::slug("{$slug}-{$gb}-{$color}"))], [
                        'name' => "{$storage} · {$color}",
                        'color_name' => $color,
                        'color_hex' => $hex,
                        'storage_gb' => $gb,
                        'price_jpy' => $priceJpy,
                        'price_idr' => (int) (round($priceJpy * $rate / 1000) * 1000),
                    ]);
                }
            }
        }
    }
}
