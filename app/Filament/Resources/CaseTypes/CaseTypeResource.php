<?php

namespace App\Filament\Resources\CaseTypes;

use App\Enums\Currency;
use App\Filament\NavigationGroup;
use App\Filament\Resources\CaseTypes\Pages\ManageCaseTypes;
use App\Filament\Support\PriceFields;
use App\Filament\Support\SafeDeleteAction;
use App\Models\CaseType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class CaseTypeResource extends Resource
{
    protected static ?string $model = CaseType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::CustomCasing;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'jenis case';

    protected static ?string $pluralModelLabel = 'jenis case';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                        if (blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(120)
                    ->alphaDash()
                    ->unique(),
                ...PriceFields::make('default_'),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->integer()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->description('Harga default dipakai untuk tipe HP tanpa mockup (PRD §6.8).')
            ->columns([
                TextColumn::make('name')
                    ->label('Jenis case')
                    ->searchable(),
                PriceFields::column('default_price_jpy', Currency::Jpy, 'Default JPY'),
                PriceFields::column('default_price_idr', Currency::Idr, 'Default IDR'),
                PriceFields::column('default_cost_price_idr', Currency::Idr, 'Modal default'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCaseTypes::route('/'),
        ];
    }
}
