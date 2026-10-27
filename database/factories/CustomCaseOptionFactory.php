<?php

namespace Database\Factories;

use App\Models\CaseType;
use App\Models\CustomCaseOption;
use App\Models\PhoneModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomCaseOption>
 */
class CustomCaseOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone_model_id' => PhoneModel::factory()->withPreview(),
            'case_type_id' => CaseType::factory(),
            'price_jpy' => 2800,
            'price_idr' => 285000,
            'cost_price_idr' => 85000,
            'is_available' => true,
        ];
    }
}
