<?php

namespace Database\Factories;

use App\Enums\CreditVerification;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Customer,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Admin]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Staff]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    /**
     * Customer the admin verified for credit purchases.
     */
    public function creditVerified(?int $limit = null): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => $attributes['phone'] ?? '0801234'.fake()->unique()->numerify('####'),
            'credit_status' => CreditVerification::Terverifikasi,
            'credit_verified_at' => now(),
            'credit_limit' => $limit,
        ]);
    }
}
