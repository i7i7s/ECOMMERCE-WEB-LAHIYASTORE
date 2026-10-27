<?php

namespace App\Filament\Resources\PhoneBrands;

use App\Filament\NavigationGroup;
use App\Filament\Resources\PhoneBrands\Pages\ManagePhoneBrands;
use App\Filament\Support\SafeDeleteAction;
use App\Models\PhoneBrand;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PhoneBrandResource extends Resource
{
    protected static ?string $model = PhoneBrand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::CustomCasing;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'merek HP';

    protected static ?string $pluralModelLabel = 'merek HP';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama merek')
                    ->required()
                    ->maxLength(100),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->integer()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Merek')
                    ->searchable(),
                TextColumn::make('phone_models_count')
                    ->label('Tipe HP')
                    ->counts('phoneModels'),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePhoneBrands::route('/'),
        ];
    }
}
