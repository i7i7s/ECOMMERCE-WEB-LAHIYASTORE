<?php

namespace App\Filament\Widgets;

use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\PreorderBatch;
use App\Support\Storefront\Storefront;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class BatchOverview extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return OperationsOverview::canView();
    }

    public function boot(): void
    {
        abort_unless(static::canView(), 403);
    }

    public function table(Table $table): Table
    {
        return $table->heading('Batch aktif dan dalam perjalanan')
            ->query(PreorderBatch::query()->whereIn('status', [BatchStatus::Dibuka, BatchStatus::DikirimKeJepang])
                ->withCount(['orders' => fn (Builder $query) => $query->where('status', '!=', OrderStatus::Dibatalkan)])
                ->addSelect(['item_count' => OrderItem::query()
                    ->selectRaw('COALESCE(SUM(order_items.qty), 0)')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->whereColumn('orders.preorder_batch_id', 'preorder_batches.id')
                    ->where('orders.status', '!=', OrderStatus::Dibatalkan)]))
            ->columns([
                TextColumn::make('code')->label('Batch'),
                TextColumn::make('status')->badge(),
                TextColumn::make('orders_count')->label('Order'),
                TextColumn::make('item_count')->label('Item')->numeric(),
                TextColumn::make('cutoff_at')->label('Cutoff')->dateTime('d M H:i')->timezone('Asia/Tokyo'),
                TextColumn::make('remaining')->label('Sisa waktu')->state(fn (PreorderBatch $record): string => $record->status === BatchStatus::Dibuka ? $record->cutoff_at->locale('id')->diffForHumans() : '—'),
                TextColumn::make('eta')->label('ETA Jepang')->state(fn (PreorderBatch $record): string => app(Storefront::class)->etaLabel($record)),
            ])->recordUrl(fn (PreorderBatch $record): string => route('filament.admin.resources.preorder-batches.view', ['record' => $record->id]));
    }
}
