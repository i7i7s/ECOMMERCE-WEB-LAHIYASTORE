<?php

namespace Database\Factories;

use App\Models\CreditApplication;
use App\Models\CreditInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditInstallment>
 */
class CreditInstallmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'credit_application_id' => CreditApplication::factory(),
            'sequence' => 1,
            'due_date' => now('Asia/Tokyo')->addMonth()->toDateString(),
            'amount' => 12400,
            'paid_at' => null,
        ];
    }
}
