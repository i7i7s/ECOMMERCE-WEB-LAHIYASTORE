<?php

namespace Database\Factories;

use App\Models\CaseColor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseColor>
 */
class CaseColorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->colorName();

        return [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'hex_code' => fake()->hexColor(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
