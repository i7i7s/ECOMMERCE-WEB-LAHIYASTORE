<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sends_a_reset_link_to_a_registered_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'raka@example.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'raka@example.com')
            ->call('sendLink')
            ->assertSet('sent', true);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_unknown_email_gets_the_same_answer(): void
    {
        Notification::fake();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'tidak-ada@example.com')
            ->call('sendLink')
            ->assertSet('sent', true);

        Notification::assertNothingSent();
    }

    public function test_valid_token_sets_a_new_password(): void
    {
        $user = User::factory()->create(['email' => 'raka@example.com']);
        $token = Password::createToken($user);

        Livewire::withQueryParams(['email' => 'raka@example.com'])
            ->test(ResetPassword::class, ['token' => $token])
            ->set('password', 'barubaru123')
            ->set('password_confirmation', 'barubaru123')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('barubaru123', $user->refresh()->password));
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'raka@example.com', 'password' => 'lama12345']);

        Livewire::test(ResetPassword::class, ['token' => 'palsu'])
            ->set('email', 'raka@example.com')
            ->set('password', 'barubaru123')
            ->set('password_confirmation', 'barubaru123')
            ->call('resetPassword')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('lama12345', $user->refresh()->password));
    }
}
