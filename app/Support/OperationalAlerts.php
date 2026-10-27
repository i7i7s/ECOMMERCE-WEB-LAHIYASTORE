<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminOperationalNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

class OperationalAlerts
{
    public static function send(string $subject, string $message, string $link): void
    {
        $admins = User::query()->where('role', UserRole::Admin)->where('is_active', true)->get();
        Notification::send($admins, new AdminOperationalNotification($subject, $message, $link));
    }

    public static function stalled(): Builder
    {
        $proof = now()->subHours((int) Setting::valueOf('proof_unverified_alert_hours', '12'));
        $packing = now()->subDays((int) Setting::valueOf('arrived_unshipped_alert_days', '2'));
        $confirmation = now()->subWeekdays((int) Setting::valueOf('confirmation_reminder_business_days', '2'));

        return Order::query()->where(function (Builder $query) use ($proof, $packing, $confirmation): void {
            $query->where(fn (Builder $q) => $q->where('status', OrderStatus::MenungguVerifikasi)->where('updated_at', '<', $proof))
                ->orWhere(fn (Builder $q) => $q->where('status', OrderStatus::TibaDiJepang)->where('updated_at', '<', $packing))
                ->orWhere(fn (Builder $q) => $q->where('status', OrderStatus::MenungguKonfirmasi)->where('created_at', '<', $confirmation));
        });
    }
}
