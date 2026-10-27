<?php

namespace App\Livewire\Store;

use App\Enums\Currency;
use App\Support\Storefront\Cart;
use App\Support\Storefront\Storefront;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Keranjang')]
class CartPage extends Component
{
    public function increment(string $id, Cart $cart): void
    {
        $item = $cart->items()->firstWhere('id', $id);

        if ($item !== null) {
            $cart->updateQuantity($id, $item['qty'] + 1);
            $this->dispatch('cart-updated');
        }
    }

    public function decrement(string $id, Cart $cart): void
    {
        $item = $cart->items()->firstWhere('id', $id);

        if ($item !== null) {
            $cart->updateQuantity($id, $item['qty'] - 1);
            $this->dispatch('cart-updated');
        }
    }

    public function remove(string $id, Cart $cart): void
    {
        $cart->remove($id);
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        $cart = app(Cart::class);
        $currency = Currency::current();
        $priceColumn = $currency === Currency::Jpy ? 'price_jpy' : 'price_idr';

        return view('livewire.store.cart-page', [
            'items' => $cart->items()->map(fn (array $item): array => [
                ...$item,
                'line_total' => $currency->format($item[$priceColumn] * $item['qty']),
            ]),
            'count' => $cart->count(),
            'subtotal' => $currency->format($cart->subtotal($currency)),
            'currency' => $currency,
            'needsConfirmation' => $cart->needsConfirmation(),
            'batch' => app(Storefront::class)->openBatch(),
            'etaLabel' => app(Storefront::class)->etaLabel(),
            'maxQty' => Cart::MAX_QTY,
        ]);
    }
}
