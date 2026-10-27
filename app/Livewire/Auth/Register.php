<?php

namespace App\Livewire\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PhoneNumber;
use App\Support\ReturnTo;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Daftar')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        ReturnTo::rememberFromQuery();
    }

    public function register(): void
    {
        $this->email = Str::lower(trim($this->email));
        $normalizedPhone = PhoneNumber::normalize($this->phone);

        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'phone' => ['required', 'regex:/^[0-9+\-\s()]{9,20}$/', function (string $attribute, mixed $value, \Closure $fail) use ($normalizedPhone): void {
                if (User::query()->where('phone', $normalizedPhone)->exists()) {
                    $fail('No. HP ini sudah terdaftar. Silakan masuk.');
                }
            }],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk.',
            'phone.regex' => 'Nomor HP hanya boleh angka, spasi, + atau -.',
        ], [
            'name' => 'nama',
            'phone' => 'no. HP',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $normalizedPhone,
            'password' => $this->password,
            'role' => UserRole::Customer,
        ]);

        event(new Registered($user));

        Auth::login($user, remember: true);
        session()->regenerate();

        $this->redirectIntended(route('account.orders'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
