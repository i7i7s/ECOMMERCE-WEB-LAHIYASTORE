<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PreorderBatch;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ScheduledCommandsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_cancels_orders_past_their_payment_window_only(): void
    {
        $expired = Order::factory()->create(['payment_due_at' => now()->subMinute()]);
        $stillOpen = Order::factory()->create(['payment_due_at' => now()->addHour()]);
        $awaitingVerification = Order::factory()->status(OrderStatus::MenungguVerifikasi)->create(['payment_due_at' => now()->subDay()]);

        $this->artisan('orders:cancel-expired')->assertSuccessful();

        $this->assertSame(OrderStatus::Dibatalkan, $expired->refresh()->status);
        $this->assertSame('Lewat batas waktu pembayaran', $expired->cancel_reason);
        $this->assertSame(OrderStatus::MenungguPembayaran, $stillOpen->refresh()->status);
        $this->assertSame(OrderStatus::MenungguVerifikasi, $awaitingVerification->refresh()->status);
    }

    public function test_closes_the_batch_after_cutoff_and_opens_the_next_one(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-04 15:00', 'UTC')); // Monday 00:00 in Tokyo.
        $batch = PreorderBatch::factory()->create(['code' => 'PO-2026-W40', 'cutoff_at' => CarbonImmutable::parse('2026-10-04 23:59', 'Asia/Tokyo')->utc()]);

        $this->artisan('batches:close-due')->assertSuccessful();

        $this->assertSame(BatchStatus::Ditutup, $batch->refresh()->status);
        // Monthly preorder: the next batch is October's.
        $this->assertSame('PO-2026-10', PreorderBatch::open()->sole()->code);
    }

    public function test_leaves_the_batch_open_before_cutoff(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-04 14:00', 'UTC')); // Sunday 23:00 in Tokyo.
        $batch = PreorderBatch::factory()->create(['cutoff_at' => CarbonImmutable::parse('2026-10-04 23:59', 'Asia/Tokyo')->utc()]);

        $this->artisan('batches:close-due')->assertSuccessful();

        $this->assertSame(BatchStatus::Dibuka, $batch->refresh()->status);
        $this->assertSame(1, PreorderBatch::count());
    }
}
