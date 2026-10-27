<?php

namespace App\Filament\Resources\OrderItemDesigns;

use App\Actions\Orders\ReviewDesign;
use App\Enums\DesignReviewStatus;
use App\Enums\OrderStatus;
use App\Filament\NavigationGroup;
use App\Filament\Resources\OrderItemDesigns\Pages\ManageOrderItemDesigns;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\BusinessAction;
use App\Models\OrderItemDesign;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Design review before production (CUST-11). Only designs of paid orders are reviewed.
 */
class OrderItemDesignResource extends Resource
{
    protected static ?string $model = OrderItemDesign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Operasional;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Review desain';

    protected static ?string $modelLabel = 'desain';

    protected static ?string $pluralModelLabel = 'review desain';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('orderItem.order', fn (Builder $query) => $query->where('status', OrderStatus::Dibayar))
            ->with(['orderItem.order.batch', 'caseType', 'caseColor', 'reviewer']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('review_status', DesignReviewStatus::Menunggu)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                ImageColumn::make('preview')
                    ->label('')
                    ->state(fn (OrderItemDesign $record): ?string => $record->original_path ? route('admin.files.design', $record) : null)
                    ->url(fn (OrderItemDesign $record): ?string => $record->original_path ? route('admin.files.design', $record) : null, shouldOpenInNewTab: true)
                    ->imageHeight(72),
                TextColumn::make('orderItem.order.code')
                    ->label('Order')
                    ->url(fn (OrderItemDesign $record): string => OrderResource::getUrl('view', ['record' => $record->orderItem->order]))
                    ->searchable(),
                TextColumn::make('phone_model_text')
                    ->label('Tipe HP')
                    ->description(fn (OrderItemDesign $record): string => "{$record->caseType->name} · {$record->caseColor->name}")
                    ->searchable(),
                TextColumn::make('has_preview')
                    ->label('Preview')
                    ->state(fn (OrderItemDesign $record): string => $record->has_preview ? 'Dengan preview' : 'Tanpa preview')
                    ->badge()
                    ->color(fn (OrderItemDesign $record): string => $record->has_preview ? 'success' : 'gray')
                    ->description(fn (OrderItemDesign $record): ?string => $record->position_note),
                TextColumn::make('orderItem.order.batch.code')
                    ->label('Batch'),
                TextColumn::make('review_status')
                    ->label('Status')
                    ->badge()
                    ->description(fn (OrderItemDesign $record): ?string => $record->review_note),
            ])
            ->filters([
                SelectFilter::make('review_status')
                    ->label('Status review')
                    ->options(DesignReviewStatus::class)
                    ->default(DesignReviewStatus::Menunggu->value),
                SelectFilter::make('batch')
                    ->label('Batch')
                    ->relationship('orderItem.order.batch', 'code'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->visible(fn (OrderItemDesign $record): bool => $record->review_status !== DesignReviewStatus::Disetujui)
                    ->action(fn (OrderItemDesign $record) => BusinessAction::run(
                        fn () => app(ReviewDesign::class)->handle($record, auth()->user(), DesignReviewStatus::Disetujui),
                        'Desain disetujui',
                    )),
                Action::make('revise')
                    ->label('Minta revisi')
                    ->color('danger')
                    ->schema([
                        Textarea::make('note')->label('Catatan untuk pelanggan')->required()->maxLength(500),
                    ])
                    ->action(fn (OrderItemDesign $record, array $data) => BusinessAction::run(
                        fn () => app(ReviewDesign::class)->handle($record, auth()->user(), DesignReviewStatus::Revisi, $data['note']),
                        'Revisi dicatat. Hubungi pelanggan lewat chat.',
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrderItemDesigns::route('/'),
        ];
    }
}
