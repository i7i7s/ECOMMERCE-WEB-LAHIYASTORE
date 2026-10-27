<?php

namespace Tests\Feature\Filament\Pages;

use App\Enums\OrderStatus;
use App\Filament\Pages\Packing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PackingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_queue_lists_only_orders_that_arrived_in_japan(): void
    {
        $this->actingAs(User::factory()->staff()->create());
        $arrived = Order::factory()->paid()->status(OrderStatus::TibaDiJepang)->create();
        $inProduction = Order::factory()->paid()->status(OrderStatus::Diproduksi)->create();

        Livewire::test(Packing::class)
            ->assertCanSeeTableRecords([$arrived])
            ->assertCanNotSeeTableRecords([$inProduction]);
    }

    public function test_staff_cannot_ship_until_every_item_is_checked(): void
    {
        $this->actingAs(User::factory()->staff()->create());
        $order = Order::factory()->paid()->status(OrderStatus::TibaDiJepang)->create();
        $items = OrderItem::factory()->count(2)->for($order)->create();

        Livewire::test(Packing::class)
            ->callAction(TestAction::make('pack')->table($order), [
                'packed_items' => [(string) $items[0]->id],
                'courier' => 'Yamato',
                'tracking_number' => '1234',
            ])
            ->assertHasActionErrors(['packed_items' => 'size']);

        $this->assertSame(OrderStatus::TibaDiJepang, $order->refresh()->status);
    }

    public function test_staff_ships_a_fully_checked_order(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff);
        $order = Order::factory()->paid()->status(OrderStatus::TibaDiJepang)->create();
        $items = OrderItem::factory()->count(2)->for($order)->create();

        Livewire::test(Packing::class)
            ->callAction(TestAction::make('pack')->table($order), [
                'packed_items' => $items->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
                'courier' => 'Sagawa',
                'tracking_number' => 'SG-998',
            ])
            ->assertHasNoActionErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Dikirim, $order->status);
        $this->assertSame('SG-998', $order->tracking_number);
        $this->assertSame(2, $order->items()->where('is_packed', true)->count());
    }

    public function test_customers_cannot_open_the_packing_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(Packing::getUrl())
            ->assertForbidden();
    }

    public function test_packing_slip_shows_recipient_and_items(): void
    {
        $order = Order::factory()->paid()->status(OrderStatus::TibaDiJepang)->create(['customer_name' => 'Raka Pratama']);
        OrderItem::factory()->for($order)->create(['item_name' => 'Jaket Windbreaker · M - Hitam']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.files.packing-slip', $order))
            ->assertOk()
            ->assertSee($order->code)
            ->assertSee('Raka Pratama')
            ->assertSee('Jaket Windbreaker · M - Hitam');
    }
}
