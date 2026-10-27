<?php

namespace App\Livewire\Store;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Storefront\OrderTracker;
use App\Support\Storefront\Storefront;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Lacak Order')]
class TrackOrder extends Component
{
    #[Url(as: 'kode', except: '')]
    public string $code = '';

    /**
     * Re-render after the payment panel sent a proof, so the status and timeline move on.
     */
    #[On('payment-submitted')]
    public function refreshAfterPayment(): void {}

    public function render(): View
    {
        $order = $this->code !== '' ? app(OrderTracker::class)->tracked($this->code) : null;
        $order?->load(['items', 'batch', 'statusHistories']);

        return view('livewire.store.track-order', [
            'order' => $order,
            'etaLabel' => $order ? app(Storefront::class)->etaLabel($order->batch) : null,
            'steps' => $order ? $this->timeline($order) : [],
        ]);
    }

    /**
     * @return list<array{label: string, when: string|null, state: 'done'|'current'|'next'}>
     */
    private function timeline(Order $order): array
    {
        $status = $order->status;
        $timeline = OrderStatus::timeline();

        if (in_array($status, [OrderStatus::MenungguKonfirmasi, OrderStatus::MenungguVerifikasi], true)) {
            $timeline[0] = $status;
        }

        $currentIndex = array_search($status, $timeline, true);
        $reachedAt = $order->statusHistories->groupBy(fn ($history) => $history->to_status->value)->map->first();

        return array_map(fn (OrderStatus $step, int $index): array => [
            'label' => $step->label(),
            'when' => $reachedAt->get($step->value)?->created_at->setTimezone('Asia/Tokyo')->locale('id')->translatedFormat('D, j M · H.i').' JST',
            'state' => match (true) {
                $currentIndex === false => 'next',
                $index < $currentIndex => 'done',
                $index === $currentIndex => 'current',
                default => 'next',
            },
        ], $timeline, array_keys($timeline));
    }
}
