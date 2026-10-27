<?php

namespace App\Filament\Resources\PreorderBatches\Tables;

use App\Enums\OrderStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PreorderBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['orders' => fn (Builder $query) => $query->where('status', '!=', OrderStatus::Dibatalkan)]))
            ->defaultSort('cutoff_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Batch')
                    ->weight('medium')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('cutoff_at')
                    ->label('Cutoff')
                    ->dateTime('D, j M H.i')
                    ->timezone('Asia/Tokyo')
                    ->suffix(' JST')
                    ->sortable(),
                TextColumn::make('orders_count')
                    ->label('Order'),
                TextColumn::make('eta_date')
                    ->label('ETA Jepang')
                    ->date('j M Y')
                    ->placeholder('-'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
