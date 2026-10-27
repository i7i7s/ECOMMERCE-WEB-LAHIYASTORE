<?php

namespace App\Support\Storefront;

use App\Models\Order;

/**
 * Public order lookup (ORD-13): an order is shown only after the visitor proves it is theirs
 * with the order code and the last 4 digits of the phone number, or placed it in this session.
 * The phone digits never travel in the URL.
 */
class OrderTracker
{
    private const SESSION_KEY = 'tracked_orders';

    public function find(string $code, string $phoneSuffix): ?Order
    {
        $order = Order::query()->where('code', strtoupper(trim($code)))->first();

        if ($order === null || strlen($phoneSuffix) !== 4 || ! hash_equals($order->phoneSuffix(), $phoneSuffix)) {
            return null;
        }

        return $order;
    }

    public function remember(Order $order): void
    {
        session()->put(self::SESSION_KEY, array_values(array_unique([...session(self::SESSION_KEY, []), $order->code])));
    }

    /**
     * The order, if this visitor verified it in this session or owns it through their account.
     */
    public function tracked(string $code): ?Order
    {
        $order = Order::query()->where('code', strtoupper(trim($code)))->first();

        if ($order === null) {
            return null;
        }

        $ownsOrder = $order->user_id !== null && $order->user_id === auth()->id();

        return $ownsOrder || in_array($order->code, session(self::SESSION_KEY, []), true) ? $order : null;
    }
}
