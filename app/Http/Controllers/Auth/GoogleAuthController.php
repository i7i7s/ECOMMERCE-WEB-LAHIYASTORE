<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SignInWithGoogle;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * "Masuk dengan Google" for customers (Laravel Socialite). The callback URL is built from the
 * current host, so it has to be listed as an authorized redirect URI in Google Cloud Console.
 */
class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            return to_route('login')->with('auth_error', 'Login dengan Google belum diaktifkan.');
        }

        return $this->google()->redirect();
    }

    public function callback(Request $request, SignInWithGoogle $signInWithGoogle): RedirectResponse
    {
        if ($request->filled('error')) {
            return to_route('login')->with('auth_error', 'Login dengan Google dibatalkan.');
        }

        try {
            $user = $signInWithGoogle->handle($this->google()->user());
        } catch (BusinessRuleException $exception) {
            return to_route('login')->with('auth_error', $exception->getMessage());
        } catch (Throwable $exception) {
            Log::warning('Google sign-in failed', ['error' => $exception->getMessage()]);

            return to_route('login')->with('auth_error', 'Login dengan Google gagal. Coba lagi sebentar lagi.');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('account.orders'));
    }

    private function google(): AbstractProvider
    {
        /** @var AbstractProvider $provider */
        $provider = Socialite::driver('google');

        return $provider->redirectUrl(route('auth.google.callback'));
    }
}
