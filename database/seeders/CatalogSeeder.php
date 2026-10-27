<?php

namespace Database\Seeders;

use App\Enums\PhoneModelSource;
use App\Models\CaseType;
use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\PhoneBrand;
use App\Models\PhoneModel;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample catalog for local development: products, phone models and custom case prices.
 * Prices and print sizes are examples, not the shop's real values.
 */
class CatalogSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const CATEGORIES = [
        'elektronik' => 'Elektronik',
        'baju' => 'Baju',
        'sepatu' => 'Sepatu',
        'celana' => 'Celana',
        'jaket' => 'Jaket',
    ];

    /**
     * slug => [category, name, description, price JPY, price IDR, variants]
     *
     * @var array<string, array{string, string, string, int, int, list<string>}>
     */
    private const PRODUCTS = [
        'power-bank-10000' => ['elektronik', 'Power Bank 10.000 mAh', 'Ringkas, cukup untuk dua kali isi daya HP.', 3500, 360000, ['Default']],
        'kaos-oversize' => ['baju', 'Kaos Oversize', 'Katun tebal, potongan longgar.', 2400, 245000, ['S', 'M', 'L', 'XL']],
        'sneakers-kanvas' => ['sepatu', 'Sneakers Kanvas', 'Ringan untuk dipakai sehari-hari.', 5800, 590000, ['39', '40', '41', '42', '43', '44']],
        'jaket-windbreaker' => ['jaket', 'Jaket Windbreaker', 'Tahan angin, cocok untuk musim gugur.', 4800, 490000, ['M - Hitam', 'L - Hitam']],
        'celana-cargo' => ['celana', 'Celana Cargo', 'Banyak saku, bahan kuat.', 3900, 395000, ['S', 'M', 'L']],
        'kabel-usb-c' => ['elektronik', 'Kabel USB-C 1 m', 'Mendukung fast charging.', 900, 92000, ['Default']],
        'hoodie-basic' => ['baju', 'Hoodie Basic', 'Fleece lembut di bagian dalam.', 4200, 430000, ['M', 'L']],
        'jaket-denim' => ['jaket', 'Jaket Denim', 'Denim klasik, warna biru tua.', 6200, 630000, ['M', 'L', 'XL']],
    ];

    /**
     * [brand, model, print width mm, print height mm]; null size = no mockup (no preview).
     *
     * @var list<array{string, string, float|null, float|null}>
     */
    private const PHONE_MODELS = [
        ['Apple', 'iPhone 17 Pro Max', 78.0, 163.4],
        ['Apple', 'iPhone 17 Pro', 71.9, 150.0],
        ['Apple', 'iPhone 17', 71.5, 149.6],
        ['Apple', 'iPhone 16 Pro Max', 77.6, 163.0],
        ['Apple', 'iPhone 16', 71.6, 147.6],
        ['Samsung', 'Samsung Galaxy S25 Ultra', 77.6, 162.8],
        ['Samsung', 'Samsung Galaxy S25', 70.5, 146.9],
        ['Google', 'Google Pixel 9', null, null],
        ['Xiaomi', 'Xiaomi 14T', null, null],
        ['OPPO', 'OPPO Reno12', null, null],
        ['Sony', 'Sony Xperia 10 VI', null, null],
        ['Sharp', 'AQUOS sense9', null, null],
    ];

    public function run(): void
    {
        $this->seedProducts();
        $this->seedPhoneModels();

        // Example rate so orders can be placed locally; the owner sets the real one weekly.
        ExchangeRate::firstOrCreate(['effective_date' => now('Asia/Tokyo')->startOfWeek()->toDateString()], ['jpy_to_idr' => 105]);
    }

    private function seedProducts(): void
    {
        $sort = 0;

        foreach (self::CATEGORIES as $slug => $name) {
            Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => $sort++]);
        }

        foreach (self::PRODUCTS as $slug => [$category, $name, $description, $priceJpy, $priceIdr, $variants]) {
            $product = Product::firstOrCreate(['slug' => $slug], [
                'category_id' => Category::where('slug', $category)->value('id'),
                'name' => $name,
                'description' => $description,
            ]);

            foreach ($variants as $index => $variant) {
                $product->variants()->firstOrCreate(['sku' => strtoupper($slug).'-'.($index + 1)], [
                    'name' => $variant,
                    'price_jpy' => $priceJpy,
                    'price_idr' => $priceIdr,
                    'cost_price_idr' => (int) round($priceIdr * 0.55, -3),
                ]);
            }
        }
    }

    private function seedPhoneModels(): void
    {
        $caseTypes = CaseType::all();

        foreach (self::PHONE_MODELS as $index => [$brandName, $name, $widthMm, $heightMm]) {
            $brand = PhoneBrand::firstOrCreate(['name' => $brandName], ['sort_order' => $brandName === 'Apple' ? 0 : 10]);
            $slug = Str::slug($name);
            $hasPreview = $widthMm !== null;

            $model = PhoneModel::firstOrCreate(['slug' => $slug], [
                'phone_brand_id' => $brand->id,
                'name' => $name,
                'source' => PhoneModelSource::Manual,
                'mockup_path' => $hasPreview ? "mockups/{$slug}.png" : null,
                'print_width_mm' => $widthMm,
                'print_height_mm' => $heightMm,
                'is_featured' => $hasPreview,
                'sort_order' => $index,
            ]);

            if (! $hasPreview) {
                continue;
            }

            foreach ($caseTypes as $caseType) {
                $model->customCaseOptions()->firstOrCreate(['case_type_id' => $caseType->id], [
                    'price_jpy' => $caseType->default_price_jpy,
                    'price_idr' => $caseType->default_price_idr,
                    'cost_price_idr' => $caseType->default_cost_price_idr,
                ]);
            }
        }
    }
}
