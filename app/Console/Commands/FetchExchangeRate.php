<?php

namespace App\Console\Commands;

use App\Actions\Finance\FetchExchangeRate as FetchExchangeRateAction;
use App\Exceptions\BusinessRuleException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('exchange-rates:fetch')]
#[Description("Store today's JPY to IDR rate from ExchangeRate-API (FIN-14); a manual rate for today is kept")]
class FetchExchangeRate extends Command
{
    public function handle(FetchExchangeRateAction $fetchExchangeRate): int
    {
        try {
            $rate = $fetchExchangeRate->handle();
        } catch (BusinessRuleException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("1 JPY = Rp{$rate->jpy_to_idr} ({$rate->source->getLabel()}, {$rate->effective_date->toDateString()})");

        return self::SUCCESS;
    }
}
