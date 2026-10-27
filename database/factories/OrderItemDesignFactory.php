<?php

namespace Database\Factories;

use App\Enums\DesignReviewStatus;
use App\Models\CaseColor;
use App\Models\CaseType;
use App\Models\OrderItem;
use App\Models\OrderItemDesign;
use App\Models\PhoneModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemDesign>
 */
class OrderItemDesignFactory extends Factory
{
    /**
     * Define the model's default state: a previewed design awaiting review.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory()->customCase(),
            'phone_model_id' => PhoneModel::factory()->withPreview(),
            'phone_model_text' => 'iPhone 17 Pro Max',
            'case_type_id' => CaseType::factory(),
            'case_color_id' => CaseColor::factory(),
            'has_preview' => true,
            'position_note' => null,
            'original_path' => 'designs/'.fake()->uuid().'.png',
            'image_width_px' => 1080,
            'image_height_px' => 1920,
            'pos_x' => 0,
            'pos_y' => 0,
            'scale' => 1,
            'rotation' => 0,
            'rights_agreed_at' => now(),
            'review_status' => DesignReviewStatus::Menunggu,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['review_status' => DesignReviewStatus::Disetujui, 'reviewed_at' => now()]);
    }

    public function withoutPreview(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_model_id' => null,
            'phone_model_text' => 'Fairphone 5',
            'has_preview' => false,
            'position_note' => 'Gambar di tengah',
        ]);
    }
}
