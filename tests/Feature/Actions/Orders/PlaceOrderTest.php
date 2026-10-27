<?php

namespace Tests\Feature\Actions\Orders;

use App\Actions\Orders\PlaceOrder;
use App\Enums\Currency;
use App\Enums\DesignReviewStatus;
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
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @var array<string, string>
     */
    private array $customer = [
        'name' => 'Raka',
        'phone' => '080-1234-5678',
        'postal_code' => '530-0001',
        'prefecture' => 'Osaka',
        'city' => 'Osaka-shi',
        'address_line1' => 'Umeda 1-2-3',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-01 05:00', 'UTC'));
        ExchangeRate::factory()->create(['jpy_to_idr' => 105]);
    }

    /**
     * @return array<string, mixed>
     */
    private function productItem(ProductVariant $variant, int $qty = 1): array
    {
        return ['type' => 'product', 'name' => 'Produk', 'qty' => $qty, 'price_jpy' => 1, 'refs' => ['product_variant_id' => $variant->id]];
    }

    public function test_prices_items_from_the_database_and_snapshots_cost_and_rate(): void
    {
        $variant = ProductVariant::factory()->create(['price_jpy' => 4800, 'price_idr' => 490000, 'cost_price_idr' => 250000]);

        $order = app(PlaceOrder::class)->handle($this->customer, [$this->productItem($variant, 2)], Currency::Jpy, OrderSource::Website);

        $this->assertSame(9600, $order->total);
        $this->assertSame('105.0000', $order->exchange_rate);
        $item = $order->items()->sole();
        $this->assertSame(4800, $item->unit_price);
        $this->assertSame(250000, $item->unit_cost_idr);
        $this->assertSame(9600, $item->subtotal);
    }

    public function test_uses_rupiah_prices_for_idr_orders(): void
    {
        $variant = ProductVariant::factory()->create(['price_jpy' => 4800, 'price_idr' => 490000]);

        $order = app(PlaceOrder::class)->handle($this->customer, [$this->productItem($variant)], Currency::Idr, OrderSource::Website);

        $this->assertSame(490000, $order->total);
    }

    public function test_order_awaits_payment_for_24_hours_and_records_history(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01 05:00', 'UTC'));
        $staff = User::factory()->staff()->create();

        $order = app(PlaceOrder::class)->handle($this->customer, [$this->productItem(ProductVariant::factory()->create())], Currency::Jpy, OrderSource::Whatsapp, $staff);

        $this->assertSame('LS-20261001-001', $order->code);
        $this->assertSame(OrderStatus::MenungguPembayaran, $order->status);
        $this->assertSame('2026-10-02 05:00', $order->payment_due_at->format('Y-m-d H:i'));
        $this->assertTrue($order->creator->is($staff));
        $history = $order->statusHistories()->sole();
        $this->assertNull($history->from_status);
        $this->assertSame(OrderStatus::MenungguPembayaran, $history->to_status);
    }

    public function test_order_codes_count_up_within_a_tokyo_day(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01 05:00', 'UTC'));
        Order::factory()->create(['code' => 'LS-20261001-007']);

        $order = app(PlaceOrder::class)->handle($this->customer, [$this->productItem(ProductVariant::factory()->create())], Currency::Jpy, OrderSource::Website);

        $this->assertSame('LS-20261001-008', $order->code);
    }

    public function test_custom_case_with_preview_uses_model_price_and_moves_design_into_order_folder(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('design-uploads/abc.png', 'png');
        $option = CustomCaseOption::factory()->create(['price_jpy' => 3200, 'cost_price_idr' => 95000]);
        $color = CaseColor::factory()->create();

        $order = app(PlaceOrder::class)->handle($this->customer, [[
            'type' => 'custom_case',
            'detail' => 'Custom',
            'qty' => 1,
            'refs' => [
                'phone_model_id' => $option->phone_model_id,
                'case_type_id' => $option->case_type_id,
                'case_color_id' => $color->id,
                'original_path' => 'design-uploads/abc.png',
                'scale' => 1.4,
            ],
        ]], Currency::Jpy, OrderSource::Website);

        $item = $order->items()->with('design')->sole();
        $this->assertSame(3200, $item->unit_price);
        $this->assertSame(95000, $item->unit_cost_idr);
        $this->assertTrue($item->customCaseOption->is($option));
        $this->assertTrue($item->design->has_preview);
        $this->assertSame(DesignReviewStatus::Menunggu, $item->design->review_status);
        $this->assertSame("designs/{$order->code}/1.png", $item->design->original_path);
        Storage::disk('local')->assertExists("designs/{$order->code}/1.png");
        Storage::disk('local')->assertMissing('design-uploads/abc.png');
        $this->assertSame(OrderStatus::MenungguPembayaran, $order->status);
    }

    public function test_model_without_mockup_waits_for_confirmation_with_case_type_default_price(): void
    {
        $caseType = CaseType::factory()->create(['default_price_jpy' => 2900]);
        $phoneModel = PhoneModel::factory()->create();

        $order = app(PlaceOrder::class)->handle($this->customer, [[
            'type' => 'custom_case',
            'detail' => 'Custom',
            'qty' => 1,
            'position_note' => 'Tengah',
            'refs' => ['phone_model_id' => $phoneModel->id, 'case_type_id' => $caseType->id, 'case_color_id' => CaseColor::factory()->create()->id],
        ]], Currency::Jpy, OrderSource::Website);

        $this->assertSame(OrderStatus::MenungguKonfirmasi, $order->status);
        $this->assertNull($order->payment_due_at);
        $this->assertSame(2900, $order->total);
        $this->assertFalse($order->items()->sole()->design->has_preview);
    }

    public function test_rejects_an_item_that_is_no_longer_available(): void
    {
        $variant = ProductVariant::factory()->create(['is_available' => false]);

        $this->expectException(BusinessRuleException::class);

        try {
            app(PlaceOrder::class)->handle($this->customer, [$this->productItem($variant)], Currency::Jpy, OrderSource::Website);
        } finally {
            $this->assertSame(0, Order::count());
        }
    }

    public function test_rejects_custom_case_type_without_price_for_a_previewed_model(): void
    {
        $phoneModel = PhoneModel::factory()->withPreview()->create();

        $this->expectException(BusinessRuleException::class);

        app(PlaceOrder::class)->handle($this->customer, [[
            'type' => 'custom_case',
            'detail' => 'Custom',
            'qty' => 1,
            'refs' => ['phone_model_id' => $phoneModel->id, 'case_type_id' => CaseType::factory()->create()->id, 'case_color_id' => CaseColor::factory()->create()->id],
        ]], Currency::Jpy, OrderSource::Website);
    }

    public function test_refuses_orders_until_an_exchange_rate_is_set(): void
    {
        ExchangeRate::query()->delete();

        $this->expectExceptionMessage('kurs JPY→IDR belum diatur admin');

        app(PlaceOrder::class)->handle($this->customer, [$this->productItem(ProductVariant::factory()->create())], Currency::Jpy, OrderSource::Website);
    }
}
