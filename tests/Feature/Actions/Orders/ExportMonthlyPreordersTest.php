<?php

namespace Tests\Feature\Actions\Orders;

use App\Actions\Orders\ExportMonthlyPreorders;
use App\Enums\Currency;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemDesign;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class ExportMonthlyPreordersTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, list<list<mixed>>> sheet name => rows
     */
    private function read(string $path): array
    {
        $reader = new Reader;
        $reader->open($path);
        $sheets = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $sheets[$sheet->getName()][] = $row->toArray();
            }
        }

        $reader->close();

        return $sheets;
    }

    public function test_workbook_has_the_orders_and_items_of_the_month_in_tokyo_time(): void
    {
        // 30 Sep 16:00 UTC is already 1 Oct in Tokyo.
        $october = Order::factory()->paid()->create(['code' => 'LS-20261001-001', 'customer_name' => 'Raka', 'total' => 3200, 'created_at' => CarbonImmutable::parse('2026-09-30 16:00', 'UTC')]);
        Order::factory()->create(['code' => 'LS-20260930-009', 'created_at' => CarbonImmutable::parse('2026-09-30 14:00', 'UTC')]);
        Order::factory()->status(OrderStatus::Dibatalkan)->create(['code' => 'LS-20261015-002', 'created_at' => CarbonImmutable::parse('2026-10-15 03:00', 'UTC')]);
        $item = OrderItem::factory()->customCase()->for($october)->create(['item_name' => 'Custom Casing', 'qty' => 2]);
        OrderItemDesign::factory()->for($item)->create(['phone_model_text' => 'iPhone 18 Pro']);

        $export = app(ExportMonthlyPreorders::class)->handle(CarbonImmutable::parse('2026-10-01', 'Asia/Tokyo'));
        $sheets = $this->read($export['path']);
        unlink($export['path']);

        $this->assertSame('preorder-2026-10.xlsx', $export['filename']);
        $this->assertSame(['Ringkasan', 'Order', 'Item'], array_keys($sheets));
        $this->assertSame(['LS-20261001-001', 'LS-20261015-002'], array_column(array_slice($sheets['Order'], 1), 0));
        $this->assertSame('Raka', $sheets['Order'][1][2]);
        $this->assertSame(['LS-20261001-001', 'iPhone 18 Pro', 2], [$sheets['Item'][1][0], $sheets['Item'][1][3], $sheets['Item'][1][8]]);
        $this->assertContains(['Total order', 2], $sheets['Ringkasan']);
        $this->assertContains(['Dibayar dalam '.Currency::Jpy->value, 1, 3200], $sheets['Ringkasan']);
    }

    public function test_admin_downloads_the_month_from_the_orders_page(): void
    {
        $this->actingAs(User::factory()->staff()->create());
        Order::factory()->create();

        Livewire::test(ListOrders::class)
            ->callAction('exportMonth', ['month' => now('Asia/Tokyo')->format('Y-m')])
            ->assertFileDownloaded('preorder-'.now('Asia/Tokyo')->format('Y-m').'.xlsx');
    }
}
