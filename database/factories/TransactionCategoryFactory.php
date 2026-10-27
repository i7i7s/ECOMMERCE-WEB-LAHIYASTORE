<?php

namespace Database\Factories;

use App\Enums\CashFlow;
use App\Enums\CategoryScope;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionCategory>
 */
class TransactionCategoryFactory extends Factory
{
    /**
     * Define the model's default state: a custom operational expense category.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => str($name)->slug()->toString(),
            'type' => CashFlow::Keluar,
            'scope' => CategoryScope::Operasional,
            'is_system' => false,
        ];
    }
}
