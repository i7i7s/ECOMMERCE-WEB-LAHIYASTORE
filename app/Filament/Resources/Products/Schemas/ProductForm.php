<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductSaleMode;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Detail produk')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                if (blank($get('slug'))) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(),
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('sale_mode')
                            ->label('Cara jual')
                            ->options(ProductSaleMode::class)
                            ->default(ProductSaleMode::Preorder)
                            ->helperText('Kredit: hanya bisa diajukan pelanggan terverifikasi, tidak masuk keranjang. Harga kredit memakai harga JPY varian.')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Tampil di toko')
                            ->default(true)
                            ->inline(false),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
                Section::make('Foto')
                    ->columnSpan(1)
                    ->schema([
                        Repeater::make('images')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->addActionLabel('Tambah foto')
                            ->defaultItems(0)
                            ->schema([
                                FileUpload::make('path')
                                    ->hiddenLabel()
                                    ->image()
                                    ->disk('public')
                                    ->directory('products')
                                    ->maxSize(5120)
                                    ->required(),
                                TextInput::make('color_name')
                                    ->label('Warna (opsional)')
                                    ->helperText('Sama persis dengan warna varian; foto tampil saat warna itu dipilih.')
                                    ->maxLength(50),
                            ]),
                    ]),
            ]);
    }
}
