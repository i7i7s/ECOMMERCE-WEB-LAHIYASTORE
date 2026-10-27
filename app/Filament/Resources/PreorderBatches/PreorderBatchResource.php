<?php

namespace App\Filament\Resources\PreorderBatches;

use App\Filament\NavigationGroup;
use App\Filament\Resources\PreorderBatches\Pages\ListPreorderBatches;
use App\Filament\Resources\PreorderBatches\Pages\ViewPreorderBatch;
use App\Filament\Resources\PreorderBatches\RelationManagers\OrdersRelationManager;
use App\Filament\Resources\PreorderBatches\Schemas\PreorderBatchInfolist;
use App\Filament\Resources\PreorderBatches\Tables\PreorderBatchesTable;
use App\Models\PreorderBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PreorderBatchResource extends Resource
{
    protected static ?string $model = PreorderBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Operasional;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Batch preorder';

    protected static ?string $modelLabel = 'batch';

    protected static ?string $pluralModelLabel = 'batch preorder';

    protected static ?string $recordTitleAttribute = 'code';

    public static function infolist(Schema $schema): Schema
    {
        return PreorderBatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PreorderBatchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPreorderBatches::route('/'),
            'view' => ViewPreorderBatch::route('/{record}'),
        ];
    }
}
