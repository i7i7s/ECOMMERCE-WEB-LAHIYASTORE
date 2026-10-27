<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\Currency;
use App\Enums\OrderSource;
use App\Models\CaseColor;
use App\Models\CaseType;
use App\Models\CashAccount;
use App\Models\PhoneModel;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Support\JapanPrefectures;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Manual order from a WhatsApp or TikTok chat (ORD-04, PRD §7.3).
 */
class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Sumber & pelanggan')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        ToggleButtons::make('source')
                            ->label('Sumber')
                            ->options([OrderSource::Whatsapp->value => 'WhatsApp', OrderSource::Tiktok->value => 'TikTok'])
                            ->default(OrderSource::Whatsapp->value)
                            ->inline()
                            ->required(),
                        ToggleButtons::make('currency')
                            ->label('Mata uang')
                            ->options([Currency::Jpy->value => '¥ JPY', Currency::Idr->value => 'Rp IDR'])
                            ->default(Currency::Jpy->value)
                            ->inline()
                            ->live()
                            ->required(),
                        TextInput::make('customer_name')->label('Nama penerima')->required()->maxLength(100),
                        TextInput::make('customer_phone')->label('No. HP')->tel()->required()->regex('/^[0-9+\-\s]{9,16}$/'),
                        TextInput::make('postal_code')->label('Kode pos')->placeholder('530-0001')->required()->regex('/^\d{3}-?\d{4}$/'),
                        Select::make('prefecture')->label('Prefektur')->options(array_combine(JapanPrefectures::all(), JapanPrefectures::all()))->searchable()->required(),
                        TextInput::make('city')->label('Kota / ward')->required()->maxLength(100),
                        TextInput::make('address_line1')->label('Alamat')->required()->maxLength(150),
                        TextInput::make('address_line2')->label('Gedung & kamar')->maxLength(150),
                    ]),
                Section::make('Pembayaran')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('already_paid')
                            ->label('Pelanggan sudah bayar')
                            ->default(false)
                            ->live(),
                        Select::make('cash_account_id')
                            ->label('Masuk ke akun')
                            ->options(fn (Get $get) => CashAccount::query()->where('currency', $get('currency'))->where('is_active', true)->pluck('name', 'id'))
                            ->visible(fn (Get $get): bool => (bool) $get('already_paid'))
                            ->required(fn (Get $get): bool => (bool) $get('already_paid')),
                        FileUpload::make('payment_proof')
                            ->label('Bukti transfer')
                            ->disk('local')
                            ->directory('payment-proof-uploads')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->maxSize(fn (): int => (int) Setting::valueOf('max_proof_upload_mb', '5') * 1024)
                            ->visible(fn (Get $get): bool => (bool) $get('already_paid'))
                            ->required(fn (Get $get): bool => (bool) $get('already_paid'))
                            ->helperText(fn (): string => auth()->user()?->isAdmin() ? 'Pembayaran langsung diterima.' : 'Admin akan memverifikasi pembayaran ini.'),
                    ]),
                Section::make('Item')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('lines')
                            ->hiddenLabel()
                            ->addActionLabel('Tambah item')
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columns(4)
                            ->schema([
                                ToggleButtons::make('type')
                                    ->label('Jenis')
                                    ->options(['product' => 'Produk', 'custom_case' => 'Custom casing'])
                                    ->default('product')
                                    ->inline()
                                    ->live()
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('qty')->label('Qty')->integer()->minValue(1)->maxValue(9)->default(1)->required(),
                                Select::make('product_variant_id')
                                    ->label('Produk & varian')
                                    ->options(fn () => ProductVariant::query()
                                        ->with('product')
                                        ->where('is_available', true)
                                        ->whereRelation('product', 'is_active', true)
                                        ->get()
                                        ->mapWithKeys(fn (ProductVariant $variant): array => [$variant->id => $variant->product->name.($variant->name === 'Default' ? '' : ' · '.$variant->name)]))
                                    ->searchable()
                                    ->visible(fn (Get $get): bool => $get('type') === 'product')
                                    ->required(fn (Get $get): bool => $get('type') === 'product')
                                    ->columnSpanFull(),
                                Select::make('phone_model_id')
                                    ->label('Tipe HP')
                                    ->options(fn () => PhoneModel::active()->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->live()
                                    ->helperText('Kosongkan bila tipe HP tidak ada di daftar, lalu ketik manual.')
                                    ->visible(fn (Get $get): bool => $get('type') === 'custom_case')
                                    ->columnSpan(2),
                                TextInput::make('phone_model_text')
                                    ->label('Tipe HP (ketik manual)')
                                    ->maxLength(80)
                                    ->visible(fn (Get $get): bool => $get('type') === 'custom_case' && blank($get('phone_model_id')))
                                    ->required(fn (Get $get): bool => $get('type') === 'custom_case' && blank($get('phone_model_id')))
                                    ->columnSpan(2),
                                Select::make('case_type_id')
                                    ->label('Jenis case')
                                    ->options(fn () => CaseType::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                                    ->visible(fn (Get $get): bool => $get('type') === 'custom_case')
                                    ->required(fn (Get $get): bool => $get('type') === 'custom_case'),
                                Select::make('case_color_id')
                                    ->label('Warna')
                                    ->options(fn () => CaseColor::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                                    ->visible(fn (Get $get): bool => $get('type') === 'custom_case')
                                    ->required(fn (Get $get): bool => $get('type') === 'custom_case'),
                                FileUpload::make('design')
                                    ->label('Gambar dari chat')
                                    ->disk('local')
                                    ->directory('design-uploads')
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                    ->maxSize(fn (): int => (int) Setting::valueOf('max_design_upload_mb', '10') * 1024)
                                    ->visible(fn (Get $get): bool => $get('type') === 'custom_case')
                                    ->required(fn (Get $get): bool => $get('type') === 'custom_case')
                                    ->columnSpan(2),
                                Textarea::make('position_note')
                                    ->label('Catatan posisi')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->visible(fn (Get $get): bool => $get('type') === 'custom_case')
                                    ->columnSpan(2),
                            ]),
                    ]),
            ]);
    }
}
