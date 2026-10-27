<?php

namespace Database\Factories;

use App\Enums\PhoneModelSource;
use App\Models\PhoneBrand;
use App\Models\PhoneModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhoneModel>
 */
class PhoneModelFactory extends Factory
{
    /**
     * Define the model's default state: a model without a mockup (no preview).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Phone '.fake()->unique()->bothify('?? ###');

        return [
            'phone_brand_id' => PhoneBrand::factory(),
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'source' => PhoneModelSource::Manual,
            'external_id' => null,
            'mockup_path' => null,
            'camera_mask_path' => null,
            'print_width_mm' => null,
            'print_height_mm' => null,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }

    /**
     * A model with mockup and print size, so it gets the 2D editor.
     */
    public function withPreview(float $widthMm = 78, float $heightMm = 163): static
    {
        return $this->state(fn (array $attributes) => [
            'mockup_path' => 'mockups/'.$attributes['slug'].'.png',
            'print_width_mm' => $widthMm,
            'print_height_mm' => $heightMm,
        ]);
    }
}
