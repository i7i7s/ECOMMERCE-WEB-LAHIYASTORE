<?php

namespace App\Filament\Resources\CaseColors;

use App\Filament\NavigationGroup;
use App\Filament\Resources\CaseColors\Pages\ManageCaseColors;
use App\Filament\Support\SafeDeleteAction;
use App\Models\CaseColor;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class CaseColorResource extends Resource
{
    protected static ?string $model = CaseColor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::CustomCasing;

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'warna case';

    protected static ?string $pluralModelLabel = 'warna case';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(50)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                        if (blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(60)
                    ->alphaDash()
                    ->unique(),
                ColorPicker::make('hex_code')
                    ->label('Warna')
                    ->required()
                    ->regex('/^#[0-9a-fA-F]{6}$/'),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->integer()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ColorColumn::make('hex_code')
                    ->label(''),
                TextColumn::make('name')
                    ->label('Warna')
                    ->searchable(),
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
            'index' => ManageCaseColors::route('/'),
        ];
    }
}
