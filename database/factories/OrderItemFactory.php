<?php

namespace Database\Factories;

use App\Enums\OrderItemType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state: a regular product line.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'item_type' => OrderItemType::Product,
            'product_variant_id' => ProductVariant::factory(),
            'custom_case_option_id' => null,
            'item_name' => fake()->words(3, true),
            'qty' => 1,
            'unit_price' => 2800,
            'unit_cost_idr' => 85000,
            'subtotal' => 2800,
            'is_packed' => false,
        ];
    }

    /**
     * A custom case line; give it a design with OrderItemDesign::factory().
     */
    public function customCase(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => OrderItemType::CustomCase,
            'product_variant_id' => null,
            'item_name' => 'Custom Casing',
        ]);
    }
}
