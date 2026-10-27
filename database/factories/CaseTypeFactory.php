<?php

namespace Database\Factories;

use App\Models\CaseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseType>
 */
class CaseTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Softcase', 'Hardcase', 'Glass case', 'Leather case']);

        return [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'default_price_jpy' => 2800,
            'default_price_idr' => 285000,
            'default_cost_price_idr' => 85000,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
