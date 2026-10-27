<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('login:raka@example.com|127.0.0.1');
        $this->customer = User::factory()->create(['email' => 'raka@example.com', 'phone' => '08012345678', 'password' => 'rahasia123']);
    }

    public function test_login_page_renders_for_guests(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Email atau no. HP');
    }

    public function test_signed_in_user_is_sent_to_account_instead_of_login_page(): void
    {
        $this->actingAs($this->customer)->get(route('login'))->assertRedirect(route('account.orders'));
    }

    public function test_customer_signs_in_with_email(): void
    {
        Livewire::test(Login::class)
            ->set('identifier', 'Raka@Example.com')
            ->set('password', 'rahasia123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('account.orders'));

        $this->assertAuthenticatedAs($this->customer);
    }

    public function test_customer_signs_in_with_phone_in_any_format(): void
    {
        Livewire::test(Login::class)
            ->set('identifier', '080-1234-5678')
            ->set('password', 'rahasia123')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($this->customer);
    }

    public function test_wrong_password_is_rejected_with_a_generic_message(): void
    {
        Livewire::test(Login::class)
            ->set('identifier', 'raka@example.com')
            ->set('password', 'salah')
            ->call('login')
            ->assertHasErrors('identifier')
            ->assertSee('Email/no. HP atau password salah.');

        $this->assertGuest();
    }

    public function test_deactivated_account_cannot_sign_in(): void
    {
        $this->customer->update(['is_active' => false]);

        Livewire::test(Login::class)
            ->set('identifier', 'raka@example.com')
            ->set('password', 'rahasia123')
            ->call('login')
            ->assertHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_locks_out_after_five_wrong_passwords(): void
    {
        $component = Livewire::test(Login::class)->set('identifier', 'raka@example.com');

        foreach (range(1, 5) as $attempt) {
            $component->set('password', "salah-{$attempt}")->call('login');
        }

        $component->set('password', 'rahasia123')
            ->call('login')
            ->assertSee('Terlalu banyak percobaan.');

        $this->assertGuest();
    }

    public function test_returns_to_checkout_when_asked(): void
    {
        $this->get(route('login', ['kembali' => 'checkout']));

        Livewire::test(Login::class)
            ->set('identifier', 'raka@example.com')
            ->set('password', 'rahasia123')
            ->call('login')
            ->assertRedirect(route('checkout'));
    }

    public function test_ignores_unknown_return_targets(): void
    {
        $this->get(route('login', ['kembali' => 'https://evil.example']));

        Livewire::test(Login::class)
            ->set('identifier', 'raka@example.com')
            ->set('password', 'rahasia123')
            ->call('login')
            ->assertRedirect(route('account.orders'));
    }

    public function test_logout_signs_the_user_out(): void
    {
        $this->actingAs($this->customer)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
