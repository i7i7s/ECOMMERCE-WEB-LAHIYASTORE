<?php

namespace App\Filament\Resources\TransactionCategories;

use App\Enums\CategoryScope;
use App\Filament\NavigationGroup;
use App\Filament\Resources\TransactionCategories\Pages\ManageTransactionCategories;
use App\Filament\Support\SafeDeleteAction;
use App\Models\TransactionCategory;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

/**
 * Expense categories. Built-in ones (sales, refund, vendor, shipping, ...) are fixed;
 * the owner adds operational ones such as packaging or ads (PRD §11.3).
 */
class TransactionCategoryResource extends Resource
{
    protected static ?string $model = TransactionCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Keuangan;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Kategori kas';

    protected static ?string $modelLabel = 'kategori';

    protected static ?string $pluralModelLabel = 'kategori kas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                        if (blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state, '_'));
                        }
                    }),
                TextInput::make('slug')->required()->maxLength(120)->unique(),
                Select::make('scope')
                    ->label('Jenis biaya')
                    ->options([CategoryScope::Operasional->value => 'Operasional (kemasan, iklan, langganan, ...)', CategoryScope::Batch->value => 'Biaya batch'])
                    ->default(CategoryScope::Operasional->value)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Kategori')->searchable(),
                TextColumn::make('type')->label('Arah')->badge(),
                TextColumn::make('scope')->label('Jenis')->badge()->color('gray'),
                IconColumn::make('is_system')->label('Bawaan')->boolean(),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (TransactionCategory $record): bool => ! $record->is_system),
                SafeDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTransactionCategories::route('/'),
        ];
    }
}
