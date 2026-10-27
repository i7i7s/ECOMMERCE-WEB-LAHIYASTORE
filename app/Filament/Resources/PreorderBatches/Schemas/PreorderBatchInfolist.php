<?php

namespace App\Filament\Resources\PreorderBatches\Schemas;

use App\Actions\Batches\BuildBatchRecap;
use App\Enums\DesignReviewStatus;
use App\Enums\OrderStatus;
use App\Models\OrderItemDesign;
use App\Models\PreorderBatch;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;

class PreorderBatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Status')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('status')->badge(),
                        TextEntry::make('cutoff_at')->label('Cutoff')->dateTime('D, j M Y H.i')->timezone('Asia/Tokyo')->suffix(' JST'),
                        TextEntry::make('unreviewed')
                            ->label('Desain belum disetujui')
                            ->state(fn (PreorderBatch $record): int => OrderItemDesign::query()
                                ->whereIn('review_status', [DesignReviewStatus::Menunggu, DesignReviewStatus::Revisi])
                                ->whereHas('orderItem.order', fn ($query) => $query->whereBelongsTo($record, 'batch')->where('status', OrderStatus::Dibayar))
                                ->count())
                            ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success')
                            ->helperText('Harus 0 sebelum batch diproduksi.'),
                        TextEntry::make('intl_courier')
                            ->label('Pengiriman internasional')
                            ->state(fn (PreorderBatch $record): ?string => $record->intl_courier ? "{$record->intl_courier} · {$record->intl_tracking_number}" : null)
                            ->placeholder('Belum dikirim'),
                        TextEntry::make('eta_date')->label('ETA tiba di Jepang')->date('j M Y')->placeholder('-'),
                        TextEntry::make('arrived_at')->label('Tiba')->dateTime('j M Y')->timezone('Asia/Tokyo')->placeholder('-'),
                    ]),
                Section::make('Rekap produksi')
                    ->description('Jumlah per tipe HP × jenis case × warna, tanpa order yang dibatalkan.')
                    ->columnSpan(2)
                    ->schema([
                        RepeatableEntry::make('recap')
                            ->label('Rekap produksi')
                            ->hiddenLabel()
                            ->state(fn (PreorderBatch $record): array => app(BuildBatchRecap::class)->summary($record)->all())
                            ->table([
                                RepeatableEntry\TableColumn::make('Tipe HP'),
                                RepeatableEntry\TableColumn::make('Jenis case'),
                                RepeatableEntry\TableColumn::make('Warna'),
                                RepeatableEntry\TableColumn::make('Qty')->alignment(Alignment::End),
                            ])
                            ->schema([
                                TextEntry::make('phone_model')->label('Tipe HP')->hiddenLabel(),
                                TextEntry::make('case_type')->label('Jenis case')->hiddenLabel(),
                                TextEntry::make('color')->label('Warna')->hiddenLabel(),
                                TextEntry::make('qty')->label('Qty')->hiddenLabel()->alignEnd(),
                            ])
                            ->placeholder('Belum ada custom casing di batch ini.'),
                    ]),
            ]);
    }
}
