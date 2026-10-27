<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Support\PhoneNumber;
use App\Support\ReturnTo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Masuk')]
class Login extends Component
{
    /** Email or phone number. */
    public string $identifier = '';

    public string $password = '';

    public bool $remember = true;

    public function mount(): void
    {
        ReturnTo::rememberFromQuery();
    }

    public function login(): void
    {
        $this->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [], ['identifier' => 'email atau no. HP', 'password' => 'password']);

        $key = 'login:'.Str::lower($this->identifier).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('identifier', 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.');

            return;
        }

        $user = $this->findUser();

        if ($user === null || ! $user->is_active || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($key, 60);
            $this->addError('identifier', 'Email/no. HP atau password salah.');

            return;
        }

        RateLimiter::clear($key);
        Auth::login($user, $this->remember);
        session()->regenerate();

        $this->redirectIntended(route('account.orders'), navigate: true);
    }

    private function findUser(): ?User
    {
        $identifier = trim($this->identifier);

        return str_contains($identifier, '@')
            ? User::query()->where('email', Str::lower($identifier))->first()
            : User::query()->where('phone', PhoneNumber::normalize($identifier))->first();
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
