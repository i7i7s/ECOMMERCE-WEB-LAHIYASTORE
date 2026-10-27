<?php

namespace App\Livewire\Store;

use App\Support\Storefront\Cart;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    #[On('cart-updated')]
    public function refreshCount(): void
    {
        // Re-render with the latest session cart.
    }

    public function render(): View
    {
        return view('livewire.store.cart-counter', [
            'count' => app(Cart::class)->count(),
        ]);
    }
}
