<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Lupa password')]
class ForgotPassword extends Component
{
    public string $email = '';

    public bool $sent = false;

    public function sendLink(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink(['email' => Str::lower(trim($this->email))]);

        if ($status === Password::ResetThrottled) {
            $this->addError('email', 'Tunggu sebentar sebelum meminta link lagi.');

            return;
        }

        // Same answer whether or not the email exists, so accounts cannot be discovered.
        $this->sent = true;
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password');
    }
}
