<?php

namespace Database\Factories;

use App\Enums\CashAccountType;
use App\Enums\Currency;
use App\Models\CashAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashAccount>
 */
class CashAccountFactory extends Factory
{
    /**
     * Define the model's default state: a Japanese bank account in JPY.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Bank Jepang',
            'type' => CashAccountType::Bank,
            'currency' => Currency::Jpy,
            'bank_name' => 'Bank Jepang',
            'account_number' => fake()->numerify('#######'),
            'account_holder' => fake()->name(),
            'show_at_checkout' => true,
            'is_active' => true,
        ];
    }

    public function idr(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'BCA',
            'currency' => Currency::Idr,
            'bank_name' => 'BCA',
        ]);
    }
}
