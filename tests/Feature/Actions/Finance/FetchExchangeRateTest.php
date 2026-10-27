<?php

namespace Tests\Feature\Actions\Finance;

use App\Actions\Finance\FetchExchangeRate;
use App\Enums\ExchangeRateSource;
use App\Exceptions\BusinessRuleException;
use App\Filament\Resources\ExchangeRates\Pages\ManageExchangeRates;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class FetchExchangeRateTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function fakeKeyedApi(float $rate): void
    {
        config(['services.exchangerate.key' => 'test-key']);
        Http::fake(['v6.exchangerate-api.com/*' => Http::response(['result' => 'success', 'conversion_rate' => $rate])]);
    }

    public function test_todays_rate_is_stored_from_the_keyed_api(): void
    {
        $this->fakeKeyedApi(113.25641);

        $rate = app(FetchExchangeRate::class)->handle();

        $this->assertSame('113.2564', $rate->jpy_to_idr);
        $this->assertSame(ExchangeRateSource::Api, $rate->source);
        $this->assertSame(now('Asia/Tokyo')->toDateString(), $rate->effective_date->toDateString());
        $this->assertTrue($rate->is(ExchangeRate::effectiveOn()));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://v6.exchangerate-api.com/v6/test-key/pair/JPY/IDR');
    }

    public function test_free_endpoint_is_used_without_a_key(): void
    {
        config(['services.exchangerate.key' => null]);
        Http::fake(['open.er-api.com/*' => Http::response(['result' => 'success', 'rates' => ['IDR' => 112.9]])]);

        $this->assertSame('112.9000', app(FetchExchangeRate::class)->handle()->jpy_to_idr);
    }

    public function test_fetching_again_refreshes_todays_api_rate_instead_of_adding_one(): void
    {
        config(['services.exchangerate.key' => 'test-key']);
        Http::fake(['v6.exchangerate-api.com/*' => Http::sequence()
            ->push(['result' => 'success', 'conversion_rate' => 110])
            ->push(['result' => 'success', 'conversion_rate' => 111])]);

        app(FetchExchangeRate::class)->handle();
        app(FetchExchangeRate::class)->handle();

        $this->assertSame('111.0000', ExchangeRate::sole()->jpy_to_idr);
    }

    public function test_manual_rate_for_today_is_kept_by_the_scheduled_fetch(): void
    {
        $manual = ExchangeRate::factory()->create(['jpy_to_idr' => 105]);
        $this->fakeKeyedApi(113);

        $this->artisan('exchange-rates:fetch')->assertSuccessful();

        $this->assertSame('105.0000', $manual->refresh()->jpy_to_idr);
        $this->assertSame(ExchangeRateSource::Manual, $manual->source);
    }

    public function test_failed_api_call_is_a_readable_error(): void
    {
        config(['services.exchangerate.key' => 'bad-key']);
        Http::fake(['*' => Http::response(['result' => 'error', 'error-type' => 'invalid-key'], 403)]);

        $this->expectException(BusinessRuleException::class);

        app(FetchExchangeRate::class)->handle();
    }

    public function test_admin_replaces_todays_manual_rate_with_the_api_rate(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        ExchangeRate::factory()->create(['jpy_to_idr' => 105]);
        $this->fakeKeyedApi(113.5);

        Livewire::test(ManageExchangeRates::class)
            ->callAction('fetch')
            ->assertNotified('Kurs hari ini diperbarui');

        $rate = ExchangeRate::sole();
        $this->assertSame('113.5000', $rate->jpy_to_idr);
        $this->assertSame(ExchangeRateSource::Api, $rate->source);
        $this->assertTrue($rate->setter->is($admin));
    }

    public function test_manual_rate_replaces_todays_api_rate(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->fakeKeyedApi(113);
        app(FetchExchangeRate::class)->handle();

        Livewire::test(ManageExchangeRates::class)
            ->callAction('create', ['effective_date' => now('Asia/Tokyo')->toDateString(), 'jpy_to_idr' => 108])
            ->assertHasNoFormErrors();

        $rate = ExchangeRate::sole();
        $this->assertSame('108.0000', $rate->jpy_to_idr);
        $this->assertSame(ExchangeRateSource::Manual, $rate->source);
    }
}
