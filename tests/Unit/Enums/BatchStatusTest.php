<?php

namespace Tests\Unit\Enums;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class BatchStatusTest extends TestCase
{
    public function test_batch_moves_through_statuses_in_order_and_stops_at_selesai(): void
    {
        $sequence = [];

        for ($status = BatchStatus::Dibuka; $status !== null; $status = $status->next()) {
            $sequence[] = $status;
        }

        $this->assertSame(BatchStatus::cases(), $sequence);
    }

    public function test_batch_status_maps_to_order_status_of_its_orders(): void
    {
        $this->assertNull(BatchStatus::Dibuka->orderStatus());
        $this->assertNull(BatchStatus::Ditutup->orderStatus());
        $this->assertSame(OrderStatus::Diproduksi, BatchStatus::Diproduksi->orderStatus());
        $this->assertSame(OrderStatus::DikirimKeJepang, BatchStatus::DikirimKeJepang->orderStatus());
        $this->assertSame(OrderStatus::TibaDiJepang, BatchStatus::TibaDiJepang->orderStatus());
        $this->assertNull(BatchStatus::Selesai->orderStatus());
    }
}
