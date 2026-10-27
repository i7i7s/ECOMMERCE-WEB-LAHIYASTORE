<?php

namespace Database\Factories;

use App\Enums\ProductSaleMode;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => ucfirst($name),
            'slug' => str($name)->slug()->toString(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Sold on credit (installments) instead of the preorder cart.
     */
    public function credit(): static
    {
        return $this->state(fn (array $attributes) => ['sale_mode' => ProductSaleMode::Kredit]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
