<?php

namespace App\Console\Commands;

use App\Actions\Orders\CancelOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:cancel-expired')]
#[Description('Cancel orders whose payment window has passed (PRD §6.11)')]
class CancelExpiredOrders extends Command
{
    public function handle(CancelOrder $cancelOrder): int
    {
        $cancelled = 0;

        Order::query()
            ->where('status', OrderStatus::MenungguPembayaran)
            ->where('payment_due_at', '<', now())
            ->each(function (Order $order) use ($cancelOrder, &$cancelled): void {
                $cancelOrder->handle($order, null, 'Lewat batas waktu pembayaran');
                $cancelled++;
            });

        $this->info("{$cancelled} order dibatalkan.");

        return self::SUCCESS;
    }
}
