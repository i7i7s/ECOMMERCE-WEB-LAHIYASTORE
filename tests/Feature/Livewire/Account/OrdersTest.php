<?php

namespace Tests\Feature\Livewire\Account;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lists_only_the_customers_own_orders(): void
    {
        $customer = User::factory()->create();
        Order::factory()->for($customer)->create(['code' => 'LS-20261001-001']);
        Order::factory()->create(['code' => 'LS-20261001-002']);

        $this->actingAs($customer)
            ->get(route('account.orders'))
            ->assertOk()
            ->assertSee('LS-20261001-001')
            ->assertDontSee('LS-20261001-002');
    }

    public function test_owner_opens_order_details_without_phone_digits(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->for($customer)->create();

        $this->actingAs($customer)
            ->get(route('track', ['kode' => $order->code]))
            ->assertSee('Riwayat status', false);
    }

    public function test_other_customer_cannot_open_the_order(): void
    {
        $order = Order::factory()->for(User::factory())->create(['customer_name' => 'Raka Rahasia']);

        $this->actingAs(User::factory()->create())
            ->get(route('track', ['kode' => $order->code]))
            ->assertDontSee('Raka Rahasia');
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('account.orders'))->assertRedirect(route('login'));
    }
}
