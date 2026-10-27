<?php

namespace Tests\Feature\Livewire\Auth;

use App\Enums\UserRole;
use App\Livewire\Auth\Register;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function fill(array $overrides = []): Testable
    {
        $data = [
            'name' => 'Dewi',
            'email' => 'Dewi@Example.com',
            'phone' => '080 9876 5432',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            ...$overrides,
        ];

        $component = Livewire::test(Register::class);

        foreach ($data as $field => $value) {
            $component->set($field, $value);
        }

        return $component;
    }

    public function test_creates_a_customer_account_and_signs_in(): void
    {
        $this->fill()
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('account.orders'));

        $user = User::sole();
        $this->assertSame('dewi@example.com', $user->email);
        $this->assertSame('08098765432', $user->phone);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_rejects_an_email_that_is_already_registered(): void
    {
        User::factory()->create(['email' => 'dewi@example.com']);

        $this->fill()
            ->call('register')
            ->assertHasErrors(['email' => 'unique'])
            ->assertSee('Email ini sudah terdaftar. Silakan masuk.');
    }

    public function test_rejects_a_phone_already_registered_in_another_format(): void
    {
        User::factory()->create(['phone' => '08098765432']);

        $this->fill()
            ->call('register')
            ->assertHasErrors('phone')
            ->assertSee('No. HP ini sudah terdaftar. Silakan masuk.');
    }

    public function test_requires_matching_password_of_at_least_8_characters(): void
    {
        $this->fill(['password' => 'pendek', 'password_confirmation' => 'beda'])
            ->call('register')
            ->assertHasErrors('password');

        $this->assertSame(0, User::count());
    }
}
