<?php

namespace App\Filament\Resources\ExchangeRates\Pages;

use App\Actions\Finance\FetchExchangeRate;
use App\Enums\ExchangeRateSource;
use App\Filament\Resources\ExchangeRates\ExchangeRateResource;
use App\Filament\Support\BusinessAction;
use App\Models\ExchangeRate;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageExchangeRates extends ManageRecords
{
    protected static string $resource = ExchangeRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetch')
                ->label('Ambil kurs hari ini')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Kurs JPY→IDR hari ini diambil dari ExchangeRate-API (diperbarui sekali sehari). Kurs manual hari ini akan diganti.')
                ->action(fn () => BusinessAction::run(
                    fn (): ExchangeRate => app(FetchExchangeRate::class)->handle(auth()->user(), replaceManual: true),
                    'Kurs hari ini diperbarui',
                )),
            CreateAction::make()
                ->label('Tambah kurs')
                ->using(function (array $data): ExchangeRate {
                    $rate = ExchangeRate::query()->whereDate('effective_date', $data['effective_date'])->first() ?? new ExchangeRate;
                    $rate->fill([...$data, 'source' => ExchangeRateSource::Manual, 'set_by' => auth()->id()])->save();

                    return $rate;
                }),
        ];
    }
}
