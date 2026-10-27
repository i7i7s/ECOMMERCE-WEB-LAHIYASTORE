<?php

namespace App\Livewire\Store;

use App\Support\Storefront\OrderTracker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class TrackForm extends Component
{
    public string $code = '';

    public string $phoneSuffix = '';

    public function track(OrderTracker $tracker): void
    {
        $this->validate([
            'code' => ['required', 'string', 'max:30'],
            'phoneSuffix' => ['required', 'digits:4'],
        ], [
            'phoneSuffix.digits' => 'Isi 4 digit terakhir nomor HP.',
        ], [
            'code' => 'kode order',
            'phoneSuffix' => '4 digit no. HP',
        ]);

        // The phone suffix has only 10.000 combinations, so guessing is throttled per visitor.
        $key = 'track-order:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('code', 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.');

            return;
        }

        $order = $tracker->find($this->code, $this->phoneSuffix);

        if ($order === null) {
            RateLimiter::hit($key, 60);

            $this->addError('code', 'Order tidak ditemukan. Cek lagi kode order dan nomor HP.');

            return;
        }

        $tracker->remember($order);

        $this->redirectRoute('track', ['kode' => $order->code], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.store.track-form');
    }
}
