<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('batches:close-due')->everyMinute()->withoutOverlapping();
Schedule::command('orders:cancel-expired')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('orders:complete-delivered')->hourly()->withoutOverlapping();
Schedule::command('exchange-rates:fetch')->hourly()->withoutOverlapping();
Schedule::command('phones:sync')
    ->dailyAt('03:00')
    ->timezone('Asia/Tokyo')
    ->when(fn (): bool => match (Setting::valueOf('phone_sync_frequency', 'monthly')) {
        'daily' => true,
        'weekly' => now('Asia/Tokyo')->isMonday(),
        default => now('Asia/Tokyo')->day === 1,
    });

Schedule::command('operations:send-alerts')->hourly()->withoutOverlapping();
