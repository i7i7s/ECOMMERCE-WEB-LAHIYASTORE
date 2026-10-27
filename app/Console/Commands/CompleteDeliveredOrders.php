<?php

namespace App\Console\Commands;

use App\Actions\Orders\CompleteOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:complete-delivered')]
#[Description('Mark orders as completed a set number of days after shipping (PRD §6.3, default 7)')]
class CompleteDeliveredOrders extends Command
{
    public function handle(CompleteOrder $completeOrder): int
    {
        $days = (int) Setting::valueOf('auto_complete_days', '7');

        Order::query()
            ->where('status', OrderStatus::Dikirim)
            ->where('shipped_at', '<=', now()->subDays($days))
            ->each(fn (Order $order) => $completeOrder->handle($order));

        return self::SUCCESS;
    }
}
