<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $priceJpy = fake()->numberBetween(9, 60) * 100;

        return [
            'product_id' => Product::factory(),
            'phone_model_id' => null,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'name' => 'Default',
            'price_jpy' => $priceJpy,
            'price_idr' => $priceJpy * 100,
            'cost_price_idr' => $priceJpy * 50,
            'is_available' => true,
        ];
    }

    /**
     * A phone variant: colour and storage, priced in yen like the credit catalog.
     */
    public function phone(string $color = 'Black', int $storageGb = 256, int $priceJpy = 150000): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "{$storageGb} GB · {$color}",
            'color_name' => $color,
            'color_hex' => '#2b2b2d',
            'storage_gb' => $storageGb,
            'price_jpy' => $priceJpy,
            'price_idr' => $priceJpy * 110,
            'cost_price_idr' => 0,
        ]);
    }
}
