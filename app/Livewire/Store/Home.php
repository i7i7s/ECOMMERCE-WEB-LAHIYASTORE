<?php

namespace App\Livewire\Store;

use App\Enums\Currency;
use App\Models\Category;
use App\Support\Storefront\Storefront;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Home extends Component
{
    public function render(): View
    {
        $storefront = app(Storefront::class);
        $currency = Currency::current();
        $customPrice = $storefront->lowestCustomCasePrice($currency);

        return view('livewire.store.home', [
            'batch' => $storefront->openBatch(),
            'cutoffLabel' => $storefront->cutoffLabel(),
            'etaLabel' => $storefront->etaLabel(),
            'categories' => Category::active()->orderBy('sort_order')->pluck('name', 'slug'),
            'customPrice' => $customPrice === null ? null : $currency->format($customPrice),
        ]);
    }
}
