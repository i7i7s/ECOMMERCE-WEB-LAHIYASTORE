<?php

namespace App\Livewire\Account;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Order history of the signed-in customer (ORD-18).
 */
#[Title('Pesanan saya')]
class Orders extends Component
{
    use WithPagination;

    public function render(): View
    {
        return view('livewire.account.orders', [
            'orders' => auth()->user()->orders()->withCount('items')->latest('id')->paginate(10),
        ]);
    }
}
