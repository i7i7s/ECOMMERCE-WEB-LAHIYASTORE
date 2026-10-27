<?php

namespace App\Livewire\Store;

use App\Support\Storefront\PolicyContent;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Kebijakan Preorder, Retur & Refund')]
class Policy extends Component
{
    public function render(): View
    {
        return view('livewire.store.policy', [
            'sections' => PolicyContent::sections(),
            'topics' => PolicyDetail::TOPICS,
        ]);
    }
}
