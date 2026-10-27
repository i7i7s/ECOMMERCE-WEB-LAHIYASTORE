<?php

namespace App\Actions\Orders;

use App\Enums\Currency;
use App\Enums\DesignReviewStatus;
use App\Enums\OrderItemType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CaseColor;
use App\Models\CaseType;
use App\Models\CustomCaseOption;
use App\Models\ExchangeRate;
use App\Models\Order;
use App\Models\PhoneModel;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Support\OperationalAlerts;
use App\Support\OrderCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Creates an order from cart items (website checkout or manual WA/TikTok input).
 *
 * Prices and costs are read from the database, never trusted from the cart, and
 * snapshotted on each item together with the exchange rate (PRD §11.2 #3).
 */
class PlaceOrder
{
    public const DESIGN_DISK = 'local';

    /**
     * @param  array{name: string, phone: string, postal_code: string, prefecture: string, city: string, address_line1: string, address_line2?: string|null}  $customer
     * @param  iterable<array<string, mixed>>  $items  Cart items (see App\Support\Storefront\Cart).
     * @param  User|null  $createdBy  Staff who entered a manual order.
     * @param  User|null  $account  Customer account the order belongs to (null = guest checkout).
     */
    public function handle(array $customer, iterable $items, Currency $currency, OrderSource $source, ?User $createdBy = null, ?User $account = null): Order
    {
        $items = collect($items)->values();

        if ($items->isEmpty()) {
            throw new BusinessRuleException('Keranjang masih kosong.');
        }

        $rate = ExchangeRate::effectiveOn();

        if ($rate === null) {
            throw new BusinessRuleException('Toko belum siap menerima pesanan: kurs JPY→IDR belum diatur admin.');
        }

        $lines = $items->map(fn (array $item): array => $this->price($item, $currency));
        $needsConfirmation = $lines->contains(fn (array $line): bool => $line['design'] !== null && ! $line['design']['has_preview']);

        return DB::transaction(function () use ($customer, $lines, $currency, $source, $createdBy, $account, $rate, $needsConfirmation): Order {
            $subtotal = $lines->sum(fn (array $line): int => $line['item']['subtotal']);
            $status = $needsConfirmation ? OrderStatus::MenungguKonfirmasi : OrderStatus::MenungguPembayaran;

            $order = Order::create([
                'code' => OrderCode::next(),
                'user_id' => $account?->id,
                'source' => $source,
                'currency' => $currency,
                'exchange_rate' => $rate->jpy_to_idr,
                'customer_name' => $customer['name'],
                'customer_phone' => $customer['phone'],
                'postal_code' => $customer['postal_code'],
                'prefecture' => $customer['prefecture'],
                'city' => $customer['city'],
                'address_line1' => $customer['address_line1'],
                'address_line2' => $customer['address_line2'] ?? null,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'status' => $status,
                'payment_due_at' => $needsConfirmation ? null : now()->addHours((int) Setting::valueOf('payment_window_hours', '24')),
                'created_by' => $createdBy?->id,
            ]);

            foreach ($lines as $index => $line) {
                $orderItem = $order->items()->create($line['item']);

                if ($line['design'] !== null) {
                    $orderItem->design()->create([
                        ...$line['design'],
                        'original_path' => $this->moveDesignFile($line['design']['original_path'], $order->code, $index + 1),
                    ]);
                }
            }

            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => $status,
                'note' => 'Order dibuat dari '.$source->getLabel(),
                'user_id' => $createdBy?->id,
                'created_at' => now(),
            ]);

            OperationalAlerts::send(
                $needsConfirmation ? 'Order perlu konfirmasi' : 'Order baru',
                "{$order->code}: ".($needsConfirmation ? 'tipe HP perlu dikonfirmasi sebelum pembayaran.' : 'order baru masuk dari '.$source->getLabel().'.'),
                route('filament.admin.resources.orders.view', ['record' => $order->id]),
            );

            return $order;
        });
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{item: array<string, mixed>, design: array<string, mixed>|null}
     */
    private function price(array $item, Currency $currency): array
    {
        $refs = $item['refs'] ?? [];
        $qty = max(1, min(99, (int) ($item['qty'] ?? 1)));

        if (($item['type'] ?? null) === 'product') {
            $variant = ProductVariant::with('product')
                ->whereKey($refs['product_variant_id'] ?? null)
                ->where('is_available', true)
                ->whereRelation('product', 'is_active', true)
                ->first();

            if ($variant === null) {
                throw new BusinessRuleException("{$item['name']} sudah tidak tersedia. Hapus dari keranjang untuk melanjutkan.");
            }

            if ($variant->product->isSoldOnCredit()) {
                throw new BusinessRuleException("{$variant->product->name} hanya dijual lewat kredit. Ajukan dari halaman produknya.");
            }

            $unitPrice = $variant->priceIn($currency);

            return ['item' => [
                'item_type' => OrderItemType::Product,
                'product_variant_id' => $variant->id,
                'item_name' => $variant->product->name.($variant->name === 'Default' ? '' : ' · '.$variant->name),
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'unit_cost_idr' => $variant->cost_price_idr,
                'subtotal' => $unitPrice * $qty,
            ], 'design' => null];
        }

        $caseType = CaseType::where('is_active', true)->find($refs['case_type_id'] ?? null);
        $color = CaseColor::where('is_active', true)->find($refs['case_color_id'] ?? null);
        $phoneModel = isset($refs['phone_model_id']) ? PhoneModel::active()->find($refs['phone_model_id']) : null;
        $hasPreview = (bool) $phoneModel?->has_preview;
        $option = $hasPreview
            ? CustomCaseOption::where('phone_model_id', $phoneModel->id)->where('case_type_id', $caseType?->id)->where('is_available', true)->first()
            : null;

        if ($caseType === null || $color === null || ($hasPreview && $option === null) || (isset($refs['phone_model_id']) && $phoneModel === null)) {
            throw new BusinessRuleException("{$item['detail']} sudah tidak tersedia. Hapus dari keranjang untuk melanjutkan.");
        }

        $unitPrice = $option?->priceIn($currency) ?? $caseType->defaultPriceIn($currency);
        $modelName = $phoneModel?->name ?? Str::limit(trim((string) ($refs['phone_model_text'] ?? '')), 80, '');

        return [
            'item' => [
                'item_type' => OrderItemType::CustomCase,
                'custom_case_option_id' => $option?->id,
                'item_name' => "Custom Casing {$modelName} · {$caseType->name} · {$color->name}",
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'unit_cost_idr' => $option?->cost_price_idr ?? $caseType->default_cost_price_idr,
                'subtotal' => $unitPrice * $qty,
            ],
            'design' => [
                'phone_model_id' => $phoneModel?->id,
                'phone_model_text' => $modelName,
                'case_type_id' => $caseType->id,
                'case_color_id' => $color->id,
                'has_preview' => $hasPreview,
                'position_note' => $item['position_note'] ?? null,
                'original_path' => $refs['original_path'] ?? null,
                'image_width_px' => $refs['image_width_px'] ?? null,
                'image_height_px' => $refs['image_height_px'] ?? null,
                'pos_x' => (float) ($refs['pos_x'] ?? 0),
                'pos_y' => (float) ($refs['pos_y'] ?? 0),
                'scale' => (float) ($refs['scale'] ?? 1),
                'rotation' => (float) ($refs['rotation'] ?? 0),
                'rights_agreed_at' => now(),
                'review_status' => DesignReviewStatus::Menunggu,
            ],
        ];
    }

    /**
     * Moves an uploaded design from the cart's temporary folder into the order's folder.
     */
    private function moveDesignFile(?string $path, string $orderCode, int $itemNumber): ?string
    {
        $disk = Storage::disk(self::DESIGN_DISK);

        if ($path === null || ! str_starts_with($path, 'design-uploads/') || ! $disk->exists($path)) {
            return null;
        }

        $target = "designs/{$orderCode}/{$itemNumber}.".pathinfo($path, PATHINFO_EXTENSION);
        $disk->move($path, $target);

        return $target;
    }
}
