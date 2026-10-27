<?php

namespace Database\Factories;

use App\Models\CashAccount;
use App\Models\CashReconciliation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashReconciliation>
 */
class CashReconciliationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cash_account_id' => CashAccount::factory(),
            'reconciled_on' => now('Asia/Tokyo')->toDateString(),
            'system_balance' => 100000,
            'actual_balance' => 100000,
            'difference' => 0,
            'note' => null,
        ];
    }
}
