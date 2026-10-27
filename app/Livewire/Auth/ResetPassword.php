<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Atur ulang password')]
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset([
            'email' => Str::lower(trim($this->email)),
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'token' => $this->token,
        ], function (User $user, string $password): void {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PasswordReset) {
            $this->addError('email', 'Link reset tidak valid atau sudah kedaluwarsa. Minta link baru.');

            return;
        }

        session()->flash('status', 'Password berhasil diubah. Silakan masuk.');

        $this->redirectRoute('login', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password');
    }
}
