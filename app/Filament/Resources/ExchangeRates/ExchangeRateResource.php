<?php

namespace App\Filament\Resources\ExchangeRates;

use App\Enums\ExchangeRateSource;
use App\Filament\NavigationGroup;
use App\Filament\Resources\ExchangeRates\Pages\ManageExchangeRates;
use App\Models\ExchangeRate;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

/**
 * JPY→IDR rate per day (FIN-02, FIN-14). Today's rate is fetched from the API every hour unless
 * the admin typed one. Orders and cash entries snapshot the rate in effect, so past rates are never edited.
 */
class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Keuangan;

    protected static ?string $navigationLabel = 'Kurs';

    protected static ?string $modelLabel = 'kurs';

    protected static ?string $pluralModelLabel = 'kurs';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('effective_date')
                    ->label('Berlaku mulai')
                    ->default(now('Asia/Tokyo'))
                    ->required()
                    ->unique(modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('source', ExchangeRateSource::Manual->value))
                    ->validationMessages(['unique' => 'Kurs manual untuk tanggal ini sudah ada.'])
                    ->helperText('Kurs dari API pada tanggal yang sama akan diganti dengan kurs manual ini.'),
                TextInput::make('jpy_to_idr')
                    ->label('1 JPY =')
                    ->prefix('Rp')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(10000)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('effective_date', 'desc')
            ->columns([
                TextColumn::make('effective_date')
                    ->label('Berlaku mulai')
                    ->date('j M Y'),
                TextColumn::make('jpy_to_idr')
                    ->label('1 JPY')
                    ->prefix('Rp'),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->timezone('Asia/Tokyo'),
                TextColumn::make('setter.name')
                    ->label('Diisi oleh')
                    ->placeholder('-'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageExchangeRates::route('/'),
        ];
    }
}
