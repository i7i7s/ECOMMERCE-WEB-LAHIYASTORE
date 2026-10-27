<?php

namespace App\Livewire\Account;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profil')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $profileSaved = false;

    public bool $passwordSaved = false;

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = (string) $user->email;
        $this->phone = (string) $user->phone;
    }

    public function saveProfile(): void
    {
        $user = auth()->user();
        $this->email = Str::lower(trim($this->email));
        $normalizedPhone = PhoneNumber::normalize($this->phone);

        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user)],
            'phone' => ['required', 'regex:/^[0-9+\-\s()]{9,20}$/', function (string $attribute, mixed $value, \Closure $fail) use ($normalizedPhone, $user): void {
                if (User::query()->where('phone', $normalizedPhone)->whereKeyNot($user->id)->exists()) {
                    $fail('No. HP ini sudah dipakai akun lain.');
                }
            }],
        ], ['email.unique' => 'Email ini sudah dipakai akun lain.'], ['name' => 'nama', 'phone' => 'no. HP']);

        $user->update(['name' => $this->name, 'email' => $this->email, 'phone' => $normalizedPhone]);
        $this->phone = $normalizedPhone;
        $this->profileSaved = true;
    }

    public function savePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], ['current_password.current_password' => 'Password saat ini salah.'], ['current_password' => 'password saat ini', 'password' => 'password baru']);

        auth()->user()->update(['password' => $this->password]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->passwordSaved = true;
    }

    public function render(): View
    {
        return view('livewire.account.profile');
    }
}
