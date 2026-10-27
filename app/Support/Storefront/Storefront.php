<?php

namespace App\Support\Storefront;

use App\Enums\BatchStatus;
use App\Enums\Currency;
use App\Models\CaseType;
use App\Models\CashAccount;
use App\Models\CustomCaseOption;
use App\Models\PreorderBatch;
use App\Models\Setting;
use App\Support\BatchSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\Scoped;

/**
 * Store-wide facts shown to customers: the open batch, arrival estimate,
 * print-quality thresholds (from settings) and checkout bank accounts.
 * Scoped so the batch and settings are loaded once per request.
 */
#[Scoped]
class Storefront
{
    private ?PreorderBatch $openBatch = null;

    /** @var array<string, string|null>|null */
    private ?array $settings = null;

    /**
     * The batch currently accepting orders. Falls back to an unsaved batch for the
     * coming cutoff if none is open yet, so the store never shows an empty estimate.
     */
    public function openBatch(): PreorderBatch
    {
        return $this->openBatch ??= PreorderBatch::open()->oldest('cutoff_at')->first()
            ?? new PreorderBatch([
                'code' => BatchSchedule::codeFor($cutoff = BatchSchedule::nextCutoff()),
                'status' => BatchStatus::Dibuka,
                'cutoff_at' => $cutoff->utc(),
            ]);
    }

    public function cutoffAt(): CarbonImmutable
    {
        return CarbonImmutable::instance($this->openBatch()->cutoff_at)->setTimezone(BatchSchedule::TIMEZONE);
    }

    /**
     * Cutoff, e.g. "Minggu, 4 Okt 23.59 JST".
     */
    public function cutoffLabel(): string
    {
        return $this->cutoffAt()->locale('id')->translatedFormat('l, j M H.i').' JST';
    }

    /**
     * Arrival estimate for a batch (the open one by default), e.g. "18–25 Okt 2026"
     * (14–21 days after cutoff), or the ETA the admin entered once the batch shipped.
     */
    public function etaLabel(?PreorderBatch $batch = null): string
    {
        if ($batch?->eta_date !== null) {
            return 'sekitar '.CarbonImmutable::instance($batch->eta_date)->locale('id')->translatedFormat('j M Y');
        }

        $cutoff = $batch ? CarbonImmutable::instance($batch->cutoff_at)->setTimezone(BatchSchedule::TIMEZONE) : $this->cutoffAt();
        $from = $cutoff->addDays($this->settingInt('eta_min_days', 14))->locale('id');
        $to = $cutoff->addDays($this->settingInt('eta_max_days', 21))->locale('id');

        return $from->isSameMonth($to)
            ? $from->translatedFormat('j').'–'.$to->translatedFormat('j M Y')
            : $from->translatedFormat('j M').' – '.$to->translatedFormat('j M Y');
    }

    /**
     * Lowest custom case price in the currency, for "Mulai ¥2.800" labels.
     */
    public function lowestCustomCasePrice(Currency $currency): ?int
    {
        $column = $currency === Currency::Jpy ? 'price_jpy' : 'price_idr';

        $prices = [
            CustomCaseOption::query()->where('is_available', true)->min($column),
            CaseType::query()->where('is_active', true)->min('default_'.$column),
        ];

        $prices = array_filter($prices, fn ($price): bool => $price !== null);

        return $prices === [] ? null : (int) min($prices);
    }

    /**
     * Bank account shown at checkout for the order's currency.
     */
    public function checkoutAccount(Currency $currency): ?CashAccount
    {
        return CashAccount::query()
            ->where('currency', $currency)
            ->where('show_at_checkout', true)
            ->where('is_active', true)
            ->first();
    }

    private function settingInt(string $key, int $default): int
    {
        $this->settings ??= Setting::query()->pluck('value', 'key')->all();

        return (int) ($this->settings[$key] ?? $default);
    }
}
