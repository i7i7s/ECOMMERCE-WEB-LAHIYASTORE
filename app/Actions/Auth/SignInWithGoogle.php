<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as GoogleUser;

/**
 * Finds or creates the customer account for a Google sign-in. An existing account with the same
 * (Google-verified) email is linked to the Google id, so customers keep their orders. Admin and
 * staff accounts are not signed in this way; they use the admin login.
 */
class SignInWithGoogle
{
    public function handle(GoogleUser $googleUser): User
    {
        $email = Str::lower((string) $googleUser->getEmail());
        $verified = (bool) ($googleUser->getRaw()['email_verified'] ?? $googleUser->getRaw()['verified_email'] ?? false);

        if ($email === '' || ! $verified) {
            throw new BusinessRuleException('Email akun Google ini belum terverifikasi. Pakai akun Google lain atau daftar dengan email.');
        }

        $user = User::query()->where('google_id', $googleUser->getId())->first()
            ?? User::query()->where('email', $email)->first();

        if ($user !== null && $user->role !== UserRole::Customer) {
            throw new BusinessRuleException('Akun admin dan staff masuk lewat halaman admin, bukan dengan Google.');
        }

        if ($user !== null && ! $user->is_active) {
            throw new BusinessRuleException('Akun ini dinonaktifkan. Hubungi admin toko.');
        }

        $user ??= User::create([
            'name' => Str::limit(trim((string) $googleUser->getName()) ?: Str::before($email, '@'), 255, ''),
            'email' => $email,
            // Random password: the customer can set their own later with "Lupa password".
            'password' => Str::random(40),
            'role' => UserRole::Customer,
        ]);

        $user->forceFill([
            'google_id' => $googleUser->getId(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }
}
