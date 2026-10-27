<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret']);
    }

    /**
     * Makes Socialite return this Google account on the callback.
     */
    private function googleReturns(string $email, string $id = 'google-123', bool $verified = true, string $name = 'Sari Wulandari'): void
    {
        $googleUser = (new SocialiteUser)
            ->setRaw(['email_verified' => $verified])
            ->map(['id' => $id, 'name' => $name, 'email' => $email]);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_login_page_offers_google(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Masuk dengan Google')->assertSee(route('auth.google.redirect'), false);
    }

    public function test_redirect_sends_the_visitor_to_google_with_the_callback_of_this_host(): void
    {
        $this->get(route('auth.google.redirect'))
            ->assertRedirectContains('accounts.google.com')
            ->assertRedirectContains(urlencode(route('auth.google.callback')));
    }

    public function test_new_google_account_creates_a_verified_customer_and_signs_in(): void
    {
        $this->googleReturns('sari@gmail.com');

        $this->get(route('auth.google.callback', ['code' => 'abc', 'state' => 'xyz']))
            ->assertRedirect(route('account.orders'));

        $user = User::where('email', 'sari@gmail.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame('Sari Wulandari', $user->name);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_existing_customer_with_the_same_email_is_linked_and_keeps_the_account(): void
    {
        $customer = User::factory()->create(['email' => 'raka@gmail.com', 'name' => 'Raka']);
        $this->googleReturns('Raka@Gmail.com', 'google-999', name: 'Raka P');

        $this->get(route('auth.google.callback', ['code' => 'abc']));

        $this->assertAuthenticatedAs($customer);
        $this->assertSame('google-999', $customer->refresh()->google_id);
        $this->assertSame('Raka', $customer->name);
        $this->assertSame(1, User::count());
    }

    public function test_staff_accounts_cannot_sign_in_with_google(): void
    {
        User::factory()->staff()->create(['email' => 'staff@lahiya.test']);
        $this->googleReturns('staff@lahiya.test');

        $this->get(route('auth.google.callback', ['code' => 'abc']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('auth_error', 'Akun admin dan staff masuk lewat halaman admin, bukan dengan Google.');

        $this->assertGuest();
    }

    public function test_unverified_google_email_is_refused(): void
    {
        $this->googleReturns('new@example.com', verified: false);

        $this->get(route('auth.google.callback', ['code' => 'abc']))->assertSessionHas('auth_error');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_cancelling_on_google_returns_to_login_with_a_message(): void
    {
        $this->followingRedirects()
            ->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertSee('Login dengan Google dibatalkan.');
    }

    public function test_after_google_login_the_customer_returns_to_the_cart(): void
    {
        $this->get(route('cart'));
        $this->googleReturns('sari@gmail.com');

        $this->get(route('auth.google.callback', ['code' => 'abc']))->assertRedirect(route('cart'));
    }
}
