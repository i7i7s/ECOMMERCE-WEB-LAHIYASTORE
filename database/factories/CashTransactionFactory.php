<?php

namespace Database\Factories;

use App\Enums\CashFlow;
use App\Enums\Currency;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashTransaction>
 */
class CashTransactionFactory extends Factory
{
    /**
     * Define the model's default state: ¥2.800 coming into a JPY account at Rp105/¥.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cash_account_id' => CashAccount::factory(),
            'transaction_category_id' => TransactionCategory::factory(),
            'type' => CashFlow::Masuk,
            'currency' => Currency::Jpy,
            'amount' => 2800,
            'exchange_rate' => 105,
            'amount_base' => 294000,
            'transaction_date' => now('Asia/Tokyo')->toDateString(),
        ];
    }

    public function outgoing(): static
    {
        return $this->state(fn (array $attributes) => ['type' => CashFlow::Keluar]);
    }

    public function voided(): static
    {
        return $this->state(fn (array $attributes) => ['voided_at' => now(), 'void_reason' => 'Salah input']);
    }
}
