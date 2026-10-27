<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PreorderBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state: a website order in JPY awaiting payment.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'LS-'.now('Asia/Tokyo')->format('Ymd').'-'.fake()->unique()->numerify('###'),
            'source' => OrderSource::Website,
            'currency' => Currency::Jpy,
            'exchange_rate' => 105,
            'customer_name' => fake()->name(),
            'customer_phone' => '080-'.fake()->numerify('####-####'),
            'postal_code' => fake()->numerify('###-####'),
            'prefecture' => 'Osaka',
            'city' => 'Osaka-shi Kita-ku',
            'address_line1' => fake()->numerify('Umeda #-#-#'),
            'address_line2' => null,
            'subtotal' => 2800,
            'total' => 2800,
            'status' => OrderStatus::MenungguPembayaran,
            'payment_due_at' => now()->addDay(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    /**
     * A paid order inside the given (or a new open) batch.
     */
    public function paid(?PreorderBatch $batch = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Dibayar,
            'preorder_batch_id' => $batch ?? PreorderBatch::factory(),
            'payment_due_at' => null,
            'paid_at' => now(),
        ]);
    }

    public function idr(): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => Currency::Idr,
            'subtotal' => 285000,
            'total' => 285000,
        ]);
    }
}
