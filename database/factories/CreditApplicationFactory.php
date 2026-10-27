<?php

namespace Database\Factories;

use App\Enums\CreditApplicationStatus;
use App\Models\CreditApplication;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditApplication>
 */
class CreditApplicationFactory extends Factory
{
    /**
     * A ¥150.000 phone, ¥30.000 down payment, 12 months at 2% flat per month.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'KR-'.now('Asia/Tokyo')->format('Ymd').'-'.fake()->unique()->numerify('###'),
            'user_id' => User::factory()->creditVerified(),
            'product_variant_id' => ProductVariant::factory()->phone()->for(Product::factory()->credit()),
            'item_name' => 'iPhone 17 · 256 GB · Black',
            'customer_name' => fake()->name(),
            'customer_phone' => '08012345678',
            'customer_email' => fake()->safeEmail(),
            'cash_price' => 150000,
            'down_payment' => 30000,
            'tenor_months' => 12,
            'margin_percent' => 2,
            'admin_fee' => 0,
            'monthly_installment' => 12400,
            'total_payable' => 178800,
            'status' => CreditApplicationStatus::Diajukan,
        ];
    }

    public function status(CreditApplicationStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
