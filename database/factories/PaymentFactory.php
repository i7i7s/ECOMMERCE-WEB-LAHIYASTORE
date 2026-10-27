<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CashAccount;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state: a manual JPY transfer awaiting verification.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'cash_account_id' => CashAccount::factory(),
            'currency' => Currency::Jpy,
            'amount' => 2800,
            'method' => PaymentMethod::TransferManual,
            'proof_path' => 'payment-proofs/'.fake()->uuid().'.jpg',
            'status' => PaymentStatus::Menunggu,
        ];
    }
}
