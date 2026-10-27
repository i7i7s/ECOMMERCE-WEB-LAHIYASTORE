<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CartRequiresLoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_opening_the_cart_is_asked_to_log_in_and_comes_back_afterwards(): void
    {
        $this->get(route('cart'))->assertRedirect(route('login'));

        $this->followingRedirects()
            ->get(route('cart'))
            ->assertSee('Silakan login terlebih dahulu untuk membuka keranjang.');

        $this->assertSame(route('cart'), session('url.intended'));
    }

    public function test_guest_cannot_open_checkout(): void
    {
        $this->get(route('checkout'))->assertRedirect(route('login'));
    }

    public function test_signed_in_customer_opens_the_cart(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('cart'))
            ->assertOk();
    }

    public function test_store_pages_use_the_branded_confirmation_dialog_instead_of_the_browser_one(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('cart'))
            ->assertSee('role="alertdialog"', false)
            ->assertDontSee('wire:confirm', false);
    }
}
