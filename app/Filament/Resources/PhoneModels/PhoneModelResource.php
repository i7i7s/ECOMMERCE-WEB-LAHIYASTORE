<?php

namespace App\Filament\Resources\PhoneModels;

use App\Filament\NavigationGroup;
use App\Filament\Resources\PhoneModels\Pages\CreatePhoneModel;
use App\Filament\Resources\PhoneModels\Pages\EditPhoneModel;
use App\Filament\Resources\PhoneModels\Pages\ListPhoneModels;
use App\Filament\Resources\PhoneModels\RelationManagers\CustomCaseOptionsRelationManager;
use App\Filament\Resources\PhoneModels\Schemas\PhoneModelForm;
use App\Filament\Resources\PhoneModels\Tables\PhoneModelsTable;
use App\Models\PhoneModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PhoneModelResource extends Resource
{
    protected static ?string $model = PhoneModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::CustomCasing;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'tipe HP';

    protected static ?string $pluralModelLabel = 'tipe HP';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PhoneModelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PhoneModelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CustomCaseOptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPhoneModels::route('/'),
            'create' => CreatePhoneModel::route('/create'),
            'edit' => EditPhoneModel::route('/{record}/edit'),
        ];
    }
}
