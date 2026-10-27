<?php

namespace Tests\Feature\Livewire\Store;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PreorderBatch;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TrackOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_order_code_alone_does_not_reveal_the_order(): void
    {
        $order = Order::factory()->create(['customer_name' => 'Raka Rahasia']);

        $this->get(route('track', ['kode' => $order->code]))
            ->assertOk()
            ->assertSee('4 digit terakhir no. HP')
            ->assertDontSee('Raka Rahasia')
            ->assertDontSee('Riwayat status');
    }

    public function test_verified_order_shows_status_timeline_and_batch_estimate(): void
    {
        $batch = PreorderBatch::factory()->create(['code' => 'PO-2026-W40', 'cutoff_at' => CarbonImmutable::parse('2026-10-04 23:59', 'Asia/Tokyo')->utc()]);
        $order = Order::factory()->for($batch, 'batch')->status(OrderStatus::Diproduksi)->create();

        $this->withSession(['tracked_orders' => [$order->code]])
            ->get(route('track', ['kode' => $order->code]))
            ->assertOk()
            ->assertSee('Batch PO-2026-W40')
            ->assertSee('Pesanan sudah tidak bisa dibatalkan.')
            ->assertSee('Estimasi tiba 18–25 Okt 2026')
            ->assertSeeInOrder(['Menunggu pembayaran', 'Dibayar', 'Diproduksi', 'Dikirim ke Jepang', 'Tiba di Jepang', 'Dikirim', 'Selesai']);
    }

    public function test_shipped_batch_shows_the_eta_entered_by_admin(): void
    {
        $batch = PreorderBatch::factory()->create(['eta_date' => '2026-10-20']);
        $order = Order::factory()->for($batch, 'batch')->status(OrderStatus::DikirimKeJepang)->create();

        $this->withSession(['tracked_orders' => [$order->code]])
            ->get(route('track', ['kode' => $order->code]))
            ->assertSee('Estimasi tiba sekitar 20 Okt 2026');
    }
}
