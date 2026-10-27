<?php

namespace Tests\Feature\Actions\Batches;

use App\Actions\Batches\AdvanceBatch;
use App\Actions\Batches\CloseBatch;
use App\Actions\Batches\EnsureOpenBatch;
use App\Actions\Batches\MoveOrderToOpenBatch;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemDesign;
use App\Models\PreorderBatch;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Covers CloseBatch, AdvanceBatch, EnsureOpenBatch and MoveOrderToOpenBatch together,
 * since they implement one lifecycle (PRD §6.1, §7.4).
 */
class BatchLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-01 05:00', 'UTC'));
        $this->admin = User::factory()->admin()->create();
    }

    private function batch(BatchStatus $status = BatchStatus::Dibuka): PreorderBatch
    {
        return PreorderBatch::factory()->status($status)->create([
            'code' => 'PO-2026-W40',
            'cutoff_at' => CarbonImmutable::parse('2026-10-04 23:59', 'Asia/Tokyo')->utc(),
        ]);
    }

    private function paidOrderWithDesign(PreorderBatch $batch, bool $approved): Order
    {
        $order = Order::factory()->paid($batch)->create();
        $design = OrderItemDesign::factory()->for(OrderItem::factory()->customCase()->for($order))->create();

        if ($approved) {
            $design->update(['review_status' => 'disetujui']);
        }

        return $order;
    }

    public function test_closing_a_batch_early_opens_the_following_period(): void
    {
        $batch = $this->batch();

        $next = app(CloseBatch::class)->handle($batch);

        $this->assertSame(BatchStatus::Ditutup, $batch->refresh()->status);
        $this->assertNotNull($batch->closed_at);
        // The store runs monthly batches now, so the weekly W40 batch is followed by October.
        $this->assertSame('PO-2026-10', $next->code);
        $this->assertSame(BatchStatus::Dibuka, $next->status);
    }

    public function test_ensure_open_batch_reuses_the_open_one(): void
    {
        $batch = $this->batch();

        $this->assertTrue(app(EnsureOpenBatch::class)->handle()->is($batch));
        $this->assertSame(1, PreorderBatch::count());
    }

    public function test_batch_cannot_go_to_production_with_designs_awaiting_review(): void
    {
        $batch = $this->batch(BatchStatus::Ditutup);
        $this->paidOrderWithDesign($batch, approved: false);

        try {
            app(AdvanceBatch::class)->handle($batch, $this->admin);
            $this->fail('Batch should not be produced.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame('1 desain belum disetujui. Selesaikan review atau pindahkan order ke batch berikutnya.', $exception->getMessage());
        }

        $this->assertSame(BatchStatus::Ditutup, $batch->refresh()->status);
    }

    public function test_producing_a_batch_moves_its_paid_orders_and_records_each_change(): void
    {
        $batch = $this->batch(BatchStatus::Ditutup);
        $order = $this->paidOrderWithDesign($batch, approved: true);
        $cancelled = Order::factory()->for($batch, 'batch')->status(OrderStatus::Dibatalkan)->create();

        app(AdvanceBatch::class)->handle($batch, $this->admin);

        $this->assertSame(BatchStatus::Diproduksi, $batch->refresh()->status);
        $this->assertNotNull($batch->produced_at);
        $this->assertSame(OrderStatus::Diproduksi, $order->refresh()->status);
        $this->assertSame('Batch PO-2026-W40: Diproduksi', $order->statusHistories()->first()->note);
        $this->assertSame(OrderStatus::Dibatalkan, $cancelled->refresh()->status);
    }

    public function test_shipping_to_japan_requires_tracking_and_eta(): void
    {
        $batch = $this->batch(BatchStatus::Diproduksi);

        $this->expectExceptionMessage('Isi kurir, nomor resi internasional, dan ETA.');

        app(AdvanceBatch::class)->handle($batch, $this->admin, ['intl_courier' => 'EMS']);
    }

    public function test_shipping_to_japan_saves_tracking_and_moves_orders(): void
    {
        $batch = $this->batch(BatchStatus::Diproduksi);
        $order = Order::factory()->for($batch, 'batch')->status(OrderStatus::Diproduksi)->create();

        app(AdvanceBatch::class)->handle($batch, $this->admin, ['intl_courier' => 'EMS', 'intl_tracking_number' => 'EE123456789ID', 'eta_date' => '2026-10-20']);

        $batch->refresh();
        $this->assertSame(BatchStatus::DikirimKeJepang, $batch->status);
        $this->assertSame('EE123456789ID', $batch->intl_tracking_number);
        $this->assertSame('2026-10-20', $batch->eta_date->toDateString());
        $this->assertSame(OrderStatus::DikirimKeJepang, $order->refresh()->status);
    }

    public function test_batch_finishes_only_when_every_order_is_shipped(): void
    {
        $batch = $this->batch(BatchStatus::TibaDiJepang);
        $waiting = Order::factory()->for($batch, 'batch')->status(OrderStatus::TibaDiJepang)->create();

        try {
            app(AdvanceBatch::class)->handle($batch, $this->admin);
            $this->fail('Batch should not finish.');
        } catch (BusinessRuleException) {
            $this->assertSame(BatchStatus::TibaDiJepang, $batch->refresh()->status);
        }

        $waiting->update(['status' => OrderStatus::Dikirim]);
        app(AdvanceBatch::class)->handle($batch, $this->admin);

        $this->assertSame(BatchStatus::Selesai, $batch->refresh()->status);
    }

    public function test_open_batch_must_be_closed_before_production(): void
    {
        $this->expectExceptionMessage('Tutup batch dulu sebelum diproduksi.');

        app(AdvanceBatch::class)->handle($this->batch(), $this->admin);
    }

    public function test_staff_cannot_change_batch_status(): void
    {
        $this->expectException(BusinessRuleException::class);

        app(AdvanceBatch::class)->handle($this->batch(BatchStatus::Ditutup), User::factory()->staff()->create());
    }

    public function test_order_with_unfinished_design_moves_to_the_open_batch(): void
    {
        $closed = $this->batch(BatchStatus::Ditutup);
        $order = $this->paidOrderWithDesign($closed, approved: false);

        app(MoveOrderToOpenBatch::class)->handle($order, $this->admin);

        $order->refresh();
        $this->assertSame('PO-2026-10', $order->batch->code);
        $this->assertSame('Dipindah dari batch PO-2026-W40 ke PO-2026-10', $order->statusHistories()->first()->note);
        app(AdvanceBatch::class)->handle($closed, $this->admin);
        $this->assertSame(BatchStatus::Diproduksi, $closed->refresh()->status);
    }
}
