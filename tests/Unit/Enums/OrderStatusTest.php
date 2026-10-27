<?php

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    /**
     * Every arrow in the order state diagram (PRD §16.2).
     *
     * @return array<string, array{OrderStatus, OrderStatus}>
     */
    public static function allowedTransitions(): array
    {
        return [
            'confirmed by staff' => [OrderStatus::MenungguKonfirmasi, OrderStatus::MenungguPembayaran],
            'blank case unavailable' => [OrderStatus::MenungguKonfirmasi, OrderStatus::Dibatalkan],
            'proof uploaded' => [OrderStatus::MenungguPembayaran, OrderStatus::MenungguVerifikasi],
            'gateway webhook' => [OrderStatus::MenungguPembayaran, OrderStatus::Dibayar],
            'payment window expired' => [OrderStatus::MenungguPembayaran, OrderStatus::Dibatalkan],
            'proof rejected' => [OrderStatus::MenungguVerifikasi, OrderStatus::MenungguPembayaran],
            'proof accepted' => [OrderStatus::MenungguVerifikasi, OrderStatus::Dibayar],
            'cancelled by admin' => [OrderStatus::MenungguVerifikasi, OrderStatus::Dibatalkan],
            'cancelled before production' => [OrderStatus::Dibayar, OrderStatus::Dibatalkan],
            'batch produced' => [OrderStatus::Dibayar, OrderStatus::Diproduksi],
            'batch shipped' => [OrderStatus::Diproduksi, OrderStatus::DikirimKeJepang],
            'batch arrived' => [OrderStatus::DikirimKeJepang, OrderStatus::TibaDiJepang],
            'packed and shipped' => [OrderStatus::TibaDiJepang, OrderStatus::Dikirim],
            'received' => [OrderStatus::Dikirim, OrderStatus::Selesai],
        ];
    }

    #[DataProvider('allowedTransitions')]
    public function test_allows_transition_from_the_state_diagram(OrderStatus $from, OrderStatus $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
    }

    public function test_diagram_has_no_other_transitions(): void
    {
        $allowedCount = array_sum(array_map(fn (OrderStatus $status): int => count($status->allowedTransitions()), OrderStatus::cases()));

        $this->assertSame(count(self::allowedTransitions()), $allowedCount);
    }

    public function test_produced_order_can_no_longer_be_cancelled(): void
    {
        $this->assertFalse(OrderStatus::Diproduksi->canTransitionTo(OrderStatus::Dibatalkan));
    }

    public function test_finished_and_cancelled_orders_are_final(): void
    {
        $this->assertSame([], OrderStatus::Selesai->allowedTransitions());
        $this->assertSame([], OrderStatus::Dibatalkan->allowedTransitions());
    }
}
