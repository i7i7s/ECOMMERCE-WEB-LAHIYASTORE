<?php

namespace App\Support\Storefront;

use App\Enums\Currency;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Guest cart kept in the session.
 *
 * Prices are snapshots for display; the order is re-priced from the database when it is placed.
 *
 * @phpstan-type CartItem array{id: string, type: 'product'|'custom_case', name: string, detail: string, price_jpy: int, price_idr: int, qty: int, has_preview: bool, needs_confirmation: bool, thumbnail: string|null, image: string|null, position_note: string|null, refs: array<string, int|float|string|null>}
 */
class Cart
{
    private const SESSION_KEY = 'cart.items';

    public const MAX_QTY = 9;

    /**
     * @return Collection<int, CartItem>
     */
    public function items(): Collection
    {
        return collect(session(self::SESSION_KEY, []))->values();
    }

    /**
     * @param  array{type: 'product'|'custom_case', name: string, detail: string, price_jpy: int, price_idr: int, qty?: int, has_preview?: bool, needs_confirmation?: bool, thumbnail?: string|null, image?: string|null, position_note?: string|null, refs?: array<string, int|float|string|null>}  $item
     */
    public function add(array $item): void
    {
        $items = session(self::SESSION_KEY, []);

        $items[] = [
            'id' => (string) Str::uuid(),
            'type' => $item['type'],
            'name' => $item['name'],
            'detail' => $item['detail'],
            'price_jpy' => $item['price_jpy'],
            'price_idr' => $item['price_idr'],
            'qty' => min(self::MAX_QTY, max(1, $item['qty'] ?? 1)),
            'has_preview' => $item['has_preview'] ?? true,
            'needs_confirmation' => $item['needs_confirmation'] ?? false,
            'thumbnail' => $item['thumbnail'] ?? null,
            'image' => $item['image'] ?? null,
            'position_note' => $item['position_note'] ?? null,
            'refs' => $item['refs'] ?? [],
        ];

        session([self::SESSION_KEY => $items]);
    }

    public function updateQuantity(string $id, int $qty): void
    {
        $items = collect(session(self::SESSION_KEY, []))
            ->map(fn (array $item): array => $item['id'] === $id
                ? [...$item, 'qty' => min(self::MAX_QTY, max(1, $qty))]
                : $item)
            ->all();

        session([self::SESSION_KEY => $items]);
    }

    public function remove(string $id): void
    {
        session([self::SESSION_KEY => collect(session(self::SESSION_KEY, []))
            ->reject(fn (array $item): bool => $item['id'] === $id)
            ->values()
            ->all()]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return (int) $this->items()->sum('qty');
    }

    public function subtotal(Currency $currency): int
    {
        $column = $currency === Currency::Jpy ? 'price_jpy' : 'price_idr';

        return (int) $this->items()->sum(fn (array $item): int => $item[$column] * $item['qty']);
    }

    /**
     * Whether any item (a phone model without a mockup) must be confirmed by staff before payment.
     */
    public function needsConfirmation(): bool
    {
        return $this->items()->contains('needs_confirmation', true);
    }
}
