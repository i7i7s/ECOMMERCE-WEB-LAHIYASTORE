<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\NavigationGroup;
use App\Filament\Resources\Suppliers\Pages\ManageSuppliers;
use App\Filament\Support\SafeDeleteAction;
use App\Models\Supplier;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Vendors in Indonesia that print the cases (linked to vendor expenses).
 */
class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Keuangan;

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Vendor';

    protected static ?string $modelLabel = 'vendor';

    protected static ?string $pluralModelLabel = 'vendor';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama vendor')->required()->maxLength(255),
                TextInput::make('contact')->label('Kontak (WA / email)')->maxLength(255),
                Textarea::make('note')->label('Catatan')->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Vendor')->searchable(),
                TextColumn::make('contact')->label('Kontak')->placeholder('-'),
                TextColumn::make('cash_transactions_count')->label('Transaksi')->counts('cashTransactions'),
            ])
            ->recordActions([
                EditAction::make(),
                SafeDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSuppliers::route('/'),
        ];
    }
}
