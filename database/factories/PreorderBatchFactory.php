<?php

namespace Database\Factories;

use App\Enums\BatchStatus;
use App\Models\PreorderBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PreorderBatch>
 */
class PreorderBatchFactory extends Factory
{
    /**
     * Define the model's default state: an open batch closing this Sunday 23:59 JST.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Times are stored in UTC; build the cutoff in Tokyo time, then convert.
        $cutoff = now('Asia/Tokyo')->endOfWeek()->setTime(23, 59)->utc();

        return [
            'code' => 'PO-'.fake()->unique()->numerify('####-W##'),
            'status' => BatchStatus::Dibuka,
            'cutoff_at' => $cutoff,
        ];
    }

    public function status(BatchStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
