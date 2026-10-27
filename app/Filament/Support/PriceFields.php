<?php

namespace App\Filament\Support;

use App\Enums\Currency;
use App\Models\ExchangeRate;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;

/**
 * Shared JPY/IDR selling price and IDR cost fields (PRD §6.4): prices are typed by hand,
 * with a helper that fills the Rupiah price from the current rate, and cost is admin-only.
 */
class PriceFields
{
    /**
     * @return list<TextInput>
     */
    public static function make(string $prefix = '', bool $withCost = true): array
    {
        $fields = [
            TextInput::make("{$prefix}price_jpy")
                ->label('Harga jual JPY')
                ->prefix('¥')
                ->required()
                ->integer()
                ->minValue(0),
            TextInput::make("{$prefix}price_idr")
                ->label('Harga jual IDR')
                ->prefix('Rp')
                ->required()
                ->integer()
                ->minValue(0)
                ->hintAction(
                    Action::make("{$prefix}fill_idr_from_rate")
                        ->label('Hitung dari kurs')
                        ->action(function (Get $get, Set $set) use ($prefix): void {
                            $rate = ExchangeRate::effectiveOn();

                            if ($rate === null) {
                                Notification::make()->title('Kurs belum diisi')->warning()->send();

                                return;
                            }

                            $set("{$prefix}price_idr", self::idrFromJpy((int) $get("{$prefix}price_jpy"), (float) $rate->jpy_to_idr));
                        }),
                ),
        ];

        if ($withCost) {
            $fields[] = TextInput::make("{$prefix}cost_price_idr")
                ->label('Harga modal IDR')
                ->prefix('Rp')
                ->integer()
                ->minValue(0)
                ->default(0)
                ->visible(fn (): bool => (bool) auth()->user()?->isAdmin());
        }

        return $fields;
    }

    /**
     * Suggested Rupiah price for a Yen price, rounded up to the nearest Rp1.000 so prices stay round.
     */
    public static function idrFromJpy(int $priceJpy, float $jpyToIdr): int
    {
        return (int) (ceil($priceJpy * $jpyToIdr / 1000) * 1000);
    }

    public static function column(string $name, Currency $currency, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->formatStateUsing(fn (?int $state): string => $currency->format((int) $state))
            ->sortable();
    }
}
