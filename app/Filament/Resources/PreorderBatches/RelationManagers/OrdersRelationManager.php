<?php

namespace App\Filament\Resources\PreorderBatches\RelationManagers;

use App\Actions\Batches\MoveOrderToOpenBatch;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\BusinessAction;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Order di batch ini';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('items'))
            ->defaultSort('code')
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable(),
                TextColumn::make('customer_name')->label('Pelanggan')->searchable(),
                TextColumn::make('items_count')->label('Item'),
                TextColumn::make('total')->label('Total')->state(fn (Order $record): string => $record->formattedTotal()),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(OrderStatus::class),
            ])
            ->recordActions([
                Action::make('moveBatch')
                    ->label('Pindah ke batch berikutnya')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => auth()->user()?->isAdmin()
                        && $record->status === OrderStatus::Dibayar
                        && $this->getOwnerRecord()->status === BatchStatus::Ditutup)
                    ->action(fn (Order $record) => BusinessAction::run(
                        fn () => app(MoveOrderToOpenBatch::class)->handle($record, auth()->user()),
                        "Order {$record->code} dipindah",
                    )),
            ]);
    }
}
