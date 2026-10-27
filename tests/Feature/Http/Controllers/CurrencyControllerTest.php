<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\CaseType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CurrencyControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_selected_currency_is_stored_in_session_and_redirects_back(): void
    {
        $response = $this->from(route('catalog'))->post(route('currency.update'), ['currency' => 'IDR']);

        $response->assertRedirect(route('catalog'));
        $response->assertSessionHas('currency', 'IDR');
    }

    public function test_rejects_unknown_currency(): void
    {
        $response = $this->from(route('catalog'))->post(route('currency.update'), ['currency' => 'USD']);

        $response->assertSessionHasErrors('currency');
        $response->assertSessionMissing('currency');
    }

    public function test_store_prices_follow_the_selected_currency(): void
    {
        CaseType::factory()->create(['default_price_jpy' => 2800, 'default_price_idr' => 285000]);

        $this->get(route('home'))->assertSee('Mulai ¥2.800');

        $this->withSession(['currency' => 'IDR'])->get(route('home'))->assertSee('Mulai Rp285.000');
    }
}
