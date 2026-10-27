<?php

namespace Tests\Feature\Livewire\Store;

use App\Livewire\Store\TrackForm;
use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class TrackFormTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_matching_code_and_phone_redirect_to_order_without_phone_in_url(): void
    {
        $order = Order::factory()->create(['code' => 'LS-20261001-001', 'customer_phone' => '080-0000-1234']);

        Livewire::test(TrackForm::class)
            ->set('code', 'ls-20261001-001')
            ->set('phoneSuffix', '1234')
            ->call('track')
            ->assertHasNoErrors()
            ->assertRedirect(route('track', ['kode' => $order->code]));

        $this->assertContains($order->code, session('tracked_orders'));
    }

    public function test_wrong_phone_digits_show_not_found_message(): void
    {
        Order::factory()->create(['code' => 'LS-20261001-001', 'customer_phone' => '080-0000-1234']);

        Livewire::test(TrackForm::class)
            ->set('code', 'LS-20261001-001')
            ->set('phoneSuffix', '9999')
            ->call('track')
            ->assertHasErrors('code')
            ->assertSee('Order tidak ditemukan. Cek lagi kode order dan nomor HP.')
            ->assertNoRedirect();
    }

    public function test_blocks_guessing_after_ten_wrong_attempts(): void
    {
        RateLimiter::clear('track-order:127.0.0.1');
        Order::factory()->create(['code' => 'LS-20261001-001', 'customer_phone' => '080-0000-1234']);
        $component = Livewire::test(TrackForm::class)->set('code', 'LS-20261001-001');

        foreach (range(1, 10) as $guess) {
            $component->set('phoneSuffix', str_pad((string) $guess, 4, '0', STR_PAD_LEFT))->call('track');
        }

        $component->set('phoneSuffix', '1234')
            ->call('track')
            ->assertNoRedirect()
            ->assertSee('Terlalu banyak percobaan.');
    }

    public function test_phone_suffix_must_be_four_digits(): void
    {
        Livewire::test(TrackForm::class)
            ->set('code', 'LS-20261001-001')
            ->set('phoneSuffix', '12a')
            ->call('track')
            ->assertHasErrors(['phoneSuffix' => 'digits']);
    }
}
