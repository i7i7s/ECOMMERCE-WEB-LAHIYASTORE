<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\Currency;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\OperationalAlerts;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('batch'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('j M H.i')
                    ->timezone('Asia/Tokyo')
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable(['customer_name', 'customer_phone']),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('total')
                    ->label('Total')
                    ->state(fn (Order $record): string => $record->formattedTotal())
                    ->alignEnd(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('batch.code')
                    ->label('Batch')
                    ->placeholder('-'),
            ])
            ->filters([
                Filter::make('stalled')->label('Order macet')->query(fn (Builder $query): Builder => $query->whereIn('id', OperationalAlerts::stalled()->select('id'))),
                SelectFilter::make('status')
                    ->options(OrderStatus::class)
                    ->multiple(),
                SelectFilter::make('source')
                    ->label('Sumber')
                    ->options(OrderSource::class),
                SelectFilter::make('currency')
                    ->label('Mata uang')
                    ->options(collect(Currency::cases())->mapWithKeys(fn (Currency $currency): array => [$currency->value => $currency->value])),
                SelectFilter::make('batch')
                    ->label('Batch')
                    ->relationship('batch', 'code'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
