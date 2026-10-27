<?php

namespace App\Actions\Finance;

use App\Enums\ExchangeRateSource;
use App\Exceptions\BusinessRuleException;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Stores today's JPY→IDR rate from ExchangeRate-API (FIN-14). With EXCHANGERATE_API_KEY the
 * keyed endpoint is used, otherwise the free open endpoint. Both refresh once a day.
 *
 * Today's API rate is refreshed in place; a rate the admin typed for today is kept unless
 * the admin asks for the API rate explicitly. Orders and cash entries keep their own snapshot.
 */
class FetchExchangeRate
{
    public function handle(?User $user = null, bool $replaceManual = false): ExchangeRate
    {
        $today = now('Asia/Tokyo')->toDateString();
        $existing = ExchangeRate::query()->whereDate('effective_date', $today)->first();

        if ($existing?->source === ExchangeRateSource::Manual && ! $replaceManual) {
            return $existing;
        }

        $rate = $existing ?? new ExchangeRate(['effective_date' => $today]);
        $rate->fill([
            'jpy_to_idr' => $this->latestRate(),
            'source' => ExchangeRateSource::Api,
            'set_by' => $user?->id,
        ])->save();

        return $rate;
    }

    private function latestRate(): float
    {
        $key = config('services.exchangerate.key');

        try {
            $response = Http::timeout(15)
                ->retry(2, 500, throw: false)
                ->get($key ? "https://v6.exchangerate-api.com/v6/{$key}/pair/JPY/IDR" : 'https://open.er-api.com/v6/latest/JPY');
        } catch (ConnectionException) {
            throw new BusinessRuleException('Server kurs tidak bisa dihubungi. Coba lagi nanti atau isi kurs manual.');
        }

        $rate = $key ? $response->json('conversion_rate') : $response->json('rates.IDR');

        if (! $response->successful() || $response->json('result') !== 'success' || ! is_numeric($rate) || $rate <= 0) {
            throw new BusinessRuleException('Kurs dari API tidak bisa diambil. Coba lagi nanti atau isi kurs manual.');
        }

        return round((float) $rate, 4);
    }
}
