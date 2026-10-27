<?php

namespace Tests\Feature\Support\Storefront;

use App\Enums\BatchStatus;
use App\Enums\Currency;
use App\Models\CaseType;
use App\Models\CashAccount;
use App\Models\CustomCaseOption;
use App\Models\PreorderBatch;
use App\Models\Setting;
use App\Support\Storefront\Storefront;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_uses_the_open_batch_from_the_database(): void
    {
        PreorderBatch::factory()->status(BatchStatus::Ditutup)->create(['code' => 'PO-2026-W39']);
        PreorderBatch::factory()->create(['code' => 'PO-2026-W40', 'cutoff_at' => CarbonImmutable::parse('2026-10-04 23:59', 'Asia/Tokyo')->utc()]);

        $storefront = new Storefront;

        $this->assertSame('PO-2026-W40', $storefront->openBatch()->code);
        $this->assertSame('Minggu, 4 Okt 23.59 JST', $storefront->cutoffLabel());
    }

    public function test_falls_back_to_the_coming_cutoff_when_no_batch_is_open(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01 05:00', 'UTC'));

        $batch = (new Storefront)->openBatch();

        $this->assertSame('PO-2026-10', $batch->code);
        $this->assertFalse($batch->exists);
    }

    public function test_arrival_estimate_spans_14_to_21_days_after_cutoff_by_default(): void
    {
        PreorderBatch::factory()->create(['cutoff_at' => CarbonImmutable::parse('2026-10-04 23:59', 'Asia/Tokyo')->utc()]);

        $this->assertSame('18–25 Okt 2026', (new Storefront)->etaLabel());
    }

    public function test_arrival_estimate_follows_settings_and_names_both_months(): void
    {
        PreorderBatch::factory()->create(['cutoff_at' => CarbonImmutable::parse('2026-10-11 23:59', 'Asia/Tokyo')->utc()]);
        Setting::create(['key' => 'eta_min_days', 'value' => '10']);
        Setting::create(['key' => 'eta_max_days', 'value' => '25']);

        $this->assertSame('21 Okt – 5 Nov 2026', (new Storefront)->etaLabel());
    }

    public function test_lowest_custom_price_ignores_unavailable_options_and_inactive_case_types(): void
    {
        $softcase = CaseType::factory()->create(['default_price_jpy' => 2900]);
        CaseType::factory()->create(['default_price_jpy' => 1000, 'is_active' => false]);
        CustomCaseOption::factory()->for($softcase)->create(['price_jpy' => 2600, 'is_available' => false]);
        CustomCaseOption::factory()->for($softcase)->create(['price_jpy' => 3100]);

        $this->assertSame(2900, (new Storefront)->lowestCustomCasePrice(Currency::Jpy));
    }

    public function test_checkout_account_matches_the_order_currency(): void
    {
        CashAccount::factory()->create(['name' => 'Bank Jepang']);
        $bca = CashAccount::factory()->idr()->create();
        CashAccount::factory()->idr()->create(['name' => 'Kas IDR', 'show_at_checkout' => false]);

        $this->assertTrue((new Storefront)->checkoutAccount(Currency::Idr)->is($bca));
    }
}
