<?php

namespace Database\Factories;

use App\Models\PhoneBrand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhoneBrand>
 */
class PhoneBrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'external_id' => null,
            'sort_order' => 0,
        ];
    }
}
