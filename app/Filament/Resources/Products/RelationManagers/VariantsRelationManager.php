<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\Currency;
use App\Filament\Support\PriceFields;
use App\Filament\Support\SafeDeleteAction;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Varian & harga';

    protected static ?string $modelLabel = 'varian';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama varian')
                    ->placeholder('Default, M - Hitam, 42, ...')
                    ->required()
                    ->maxLength(100),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(64)
                    ->unique(),
                ...PriceFields::make(),
                TextInput::make('color_name')
                    ->label('Warna')
                    ->helperText('Untuk HP kredit: pemilih warna di halaman produk.')
                    ->maxLength(50),
                ColorPicker::make('color_hex')
                    ->label('Kode warna'),
                Select::make('storage_gb')
                    ->label('Kapasitas')
                    ->options([64 => '64 GB', 128 => '128 GB', 256 => '256 GB', 512 => '512 GB', 1024 => '1 TB', 2048 => '2 TB'])
                    ->helperText('Untuk HP kredit; kosongkan untuk produk lain.'),
                Select::make('phone_model_id')
                    ->label('Kompatibel dengan tipe HP')
                    ->helperText('Hanya untuk casing ready; kosongkan untuk produk lain.')
                    ->relationship('phoneModel', 'name')
                    ->searchable(),
                Toggle::make('is_available')
                    ->label('Tersedia')
                    ->default(true)
                    ->inline(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Varian')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->color('gray')
                    ->searchable(),
                PriceFields::column('price_jpy', Currency::Jpy, 'Harga JPY'),
                PriceFields::column('price_idr', Currency::Idr, 'Harga IDR'),
                PriceFields::column('cost_price_idr', Currency::Idr, 'Modal')
                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin()),
                IconColumn::make('is_available')
                    ->label('Tersedia')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }
}
