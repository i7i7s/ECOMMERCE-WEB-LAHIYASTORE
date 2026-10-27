<?php

namespace App\Console\Commands;

use App\Enums\BatchStatus;
use App\Models\PreorderBatch;
use App\Support\OperationalAlerts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendOperationalAlerts extends Command
{
    protected $signature = 'operations:send-alerts';

    protected $description = 'Kirim pengingat order macet dan cutoff batch kepada admin';

    public function handle(): int
    {
        foreach (OperationalAlerts::stalled()->orderBy('id')->cursor() as $order) {
            $key = 'operations:order:'.$order->id.':'.$order->status->value;
            if (Cache::add($key, true, now()->addDay())) {
                try {
                    OperationalAlerts::send('Order perlu tindakan', $order->code.' — '.$order->status->getLabel(), route('filament.admin.resources.orders.view', ['record' => $order->id]));
                } catch (\Throwable $exception) {
                    Cache::forget($key);
                    throw $exception;
                }
            }
        }

        $batches = PreorderBatch::query()->where('status', BatchStatus::Dibuka)
            ->whereBetween('cutoff_at', [now(), now()->addDay()])->get();

        foreach ($batches as $batch) {
            $key = 'operations:cutoff:'.$batch->id.':'.$batch->cutoff_at->timestamp;
            if (Cache::add($key, true, now()->addDays(2))) {
                try {
                    OperationalAlerts::send('Cutoff batch kurang dari 24 jam', $batch->code.' ditutup '.$batch->cutoff_at->timezone('Asia/Tokyo')->format('d-m-Y H:i').' JST.', route('filament.admin.resources.preorder-batches.view', ['record' => $batch->id]));
                } catch (\Throwable $exception) {
                    Cache::forget($key);
                    throw $exception;
                }
            }
        }

        return self::SUCCESS;
    }
}
