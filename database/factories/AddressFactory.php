<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => 'Rumah',
            'recipient_name' => fake()->name(),
            'phone' => '080-'.fake()->numerify('####-####'),
            'postal_code' => fake()->numerify('###-####'),
            'prefecture' => fake()->randomElement(['Tokyo', 'Osaka', 'Aichi', 'Saitama']),
            'city' => fake()->city(),
            'address_line1' => fake()->numerify('#-#-#'),
            'address_line2' => null,
            'is_default' => false,
        ];
    }
}
