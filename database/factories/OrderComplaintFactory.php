<?php

namespace Database\Factories;

use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderComplaint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderComplaint>
 */
class OrderComplaintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->status(OrderStatus::Dikirim),
            'type' => ComplaintType::SalahCetak,
            'description' => fake()->sentence(),
            'evidence_path' => null,
            'status' => ComplaintStatus::Terbuka,
        ];
    }
}
