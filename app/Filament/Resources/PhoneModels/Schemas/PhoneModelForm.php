<?php

namespace App\Filament\Resources\PhoneModels\Schemas;

use App\Enums\PhoneModelSource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PhoneModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Tipe HP')
                    ->columns(2)
                    ->schema([
                        Select::make('phone_brand_id')
                            ->label('Merek')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->label('Nama merek')->required()->maxLength(100),
                            ])
                            ->required(),
                        TextInput::make('name')
                            ->label('Nama tipe')
                            ->placeholder('iPhone 17 Pro Max')
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
                        Select::make('source')
                            ->label('Sumber')
                            ->options(PhoneModelSource::class)
                            ->default(PhoneModelSource::Manual)
                            ->disabled()
                            ->dehydrated(),
                        Toggle::make('is_active')
                            ->label('Bisa dipesan')
                            ->default(true),
                        Toggle::make('is_featured')
                            ->label('Tampil paling atas'),
                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->integer()
                            ->default(0),
                    ]),
                Section::make('Preview 2D')
                    ->description('Isi mockup dan ukuran area cetak agar tipe ini punya editor preview. Ukuran diukur dari blank case vendor.')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('mockup_path')
                            ->label('Mockup PNG transparan')
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->disk('public')
                            ->directory('mockups')
                            ->maxSize(5120),
                        FileUpload::make('camera_mask_path')
                            ->label('Masker kamera (PNG hitam-putih)')
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->disk('public')
                            ->directory('camera-masks')
                            ->maxSize(5120),
                        TextInput::make('print_width_mm')
                            ->label('Lebar area cetak')
                            ->suffix('mm')
                            ->numeric()
                            ->minValue(30)
                            ->maxValue(150)
                            ->requiredWith('mockup_path'),
                        TextInput::make('print_height_mm')
                            ->label('Tinggi area cetak')
                            ->suffix('mm')
                            ->numeric()
                            ->minValue(60)
                            ->maxValue(250)
                            ->requiredWith('mockup_path'),
                    ]),
            ]);
    }
}
