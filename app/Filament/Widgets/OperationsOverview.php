<?php

namespace App\Filament\Widgets;

use App\Enums\ComplaintStatus;
use App\Enums\DesignReviewStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderComplaint;
use App\Models\OrderItemDesign;
use App\Support\OperationalAlerts;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Perlu tindakan';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->is_active && in_array($user->role, [UserRole::Admin, UserRole::Staff], true);
    }

    public function boot(): void
    {
        abort_unless(static::canView(), 403);
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Menunggu konfirmasi', Order::where('status', OrderStatus::MenungguKonfirmasi)->count())->url(route('filament.admin.resources.orders.index', ['filters' => ['status' => ['values' => ['menunggu_konfirmasi']]]])),
            Stat::make('Verifikasi pembayaran', Order::where('status', OrderStatus::MenungguVerifikasi)->count())->url(route('filament.admin.resources.orders.index', ['filters' => ['status' => ['values' => ['menunggu_verifikasi']]]])),
            Stat::make('Review desain', OrderItemDesign::where('review_status', DesignReviewStatus::Menunggu)->whereHas('orderItem.order', fn ($q) => $q->where('status', OrderStatus::Dibayar))->count())->url(route('filament.admin.resources.order-item-designs.index')),
            Stat::make('Antrean packing', Order::where('status', OrderStatus::TibaDiJepang)->count())->url(route('filament.admin.pages.packing')),
            Stat::make('Komplain terbuka', OrderComplaint::where('status', ComplaintStatus::Terbuka)->count())->url(route('filament.admin.resources.order-complaints.index')),
            Stat::make('Order macet', OperationalAlerts::stalled()->count())->color('warning')->url(route('filament.admin.resources.orders.index', ['filters' => ['stalled' => ['isActive' => true]]])),
        ];
    }
}
