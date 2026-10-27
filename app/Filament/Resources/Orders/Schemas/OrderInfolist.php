<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Filament\Resources\OrderComplaints\OrderComplaintResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Ringkasan')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('status')->badge(),
                                TextEntry::make('source')->label('Sumber')->badge()->color('gray'),
                                TextEntry::make('total')->label('Total')->state(fn (Order $record): string => $record->formattedTotal())->weight('bold'),
                                TextEntry::make('created_at')->label('Dibuat')->dateTime('j M Y H.i')->timezone('Asia/Tokyo')->suffix(' JST'),
                                TextEntry::make('payment_due_at')->label('Batas bayar')->dateTime('j M Y H.i')->timezone('Asia/Tokyo')->suffix(' JST')->placeholder('-'),
                                TextEntry::make('batch.code')->label('Batch')->placeholder('Belum masuk batch'),
                                TextEntry::make('exchange_rate')->label('Kurs snapshot')->prefix('1 JPY = Rp'),
                                TextEntry::make('parentOrder.code')->label('Penggantian dari order')->url(fn (Order $record): ?string => $record->parent_order_id ? OrderResource::getUrl('view', ['record' => $record->parent_order_id]) : null)->visible(fn (Order $record): bool => $record->parent_order_id !== null),
                                TextEntry::make('complaints_link')->label('Komplain')->state(fn (Order $record): string => $record->complaints()->count().' komplain')->url(fn (Order $record): string => OrderComplaintResource::getUrl('index', ['filters' => ['order_id' => ['value' => $record->id]]])),
                                TextEntry::make('creator.name')->label('Diinput oleh')->placeholder('Pelanggan (website)'),
                                TextEntry::make('cancel_reason')->label('Alasan batal')->visible(fn (Order $record): bool => filled($record->cancel_reason)),
                                TextEntry::make('tracking_number')
                                    ->label('Pengiriman domestik')
                                    ->state(fn (Order $record): ?string => $record->tracking_number ? "{$record->domestic_courier} · {$record->tracking_number}" : null)
                                    ->helperText(fn (Order $record): ?string => $record->packer ? "Dipacking {$record->packer->name}" : null)
                                    ->visible(fn (Order $record): bool => filled($record->tracking_number)),
                                TextEntry::make('packing_photo_path')
                                    ->label('Foto paket')
                                    ->state('Lihat foto')
                                    ->url(fn (Order $record): string => route('admin.files.packing-photo', $record), shouldOpenInNewTab: true)
                                    ->color('primary')
                                    ->visible(fn (Order $record): bool => filled($record->packing_photo_path)),
                            ]),
                        Section::make('Item')
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->hiddenLabel()
                                    ->columns(4)
                                    ->schema([
                                        ImageEntry::make('design_preview')
                                            ->hiddenLabel()
                                            ->state(fn (OrderItem $record): ?string => $record->design?->original_path ? route('admin.files.design', $record->design) : null)
                                            ->url(fn (OrderItem $record): ?string => $record->design?->original_path ? route('admin.files.design', $record->design) : null, shouldOpenInNewTab: true)
                                            ->imageHeight(96)
                                            ->placeholder('Tanpa gambar'),
                                        TextEntry::make('item_name')
                                            ->label('Item')
                                            ->columnSpan(2)
                                            ->helperText(fn (OrderItem $record): ?string => $record->design ? collect([
                                                $record->design->has_preview ? null : 'Tanpa preview',
                                                $record->design->position_note ? 'Posisi: '.$record->design->position_note : null,
                                            ])->filter()->implode(' · ') : null),
                                        TextEntry::make('design.review_status')
                                            ->label('Desain')
                                            ->badge()
                                            ->placeholder('-')
                                            ->helperText(fn (OrderItem $record): ?string => $record->design?->review_note),
                                        TextEntry::make('qty')->label('Qty'),
                                        TextEntry::make('subtotal')
                                            ->label('Subtotal')
                                            ->state(fn (OrderItem $record): string => $record->order->currency->format($record->subtotal)),
                                    ]),
                            ]),
                        Section::make('Pembayaran')
                            ->schema([
                                RepeatableEntry::make('payments')
                                    ->hiddenLabel()
                                    ->columns(4)
                                    ->placeholder('Belum ada pembayaran.')
                                    ->schema([
                                        TextEntry::make('amount')
                                            ->label('Jumlah')
                                            ->state(fn (Payment $record): string => $record->currency->format($record->amount)),
                                        TextEntry::make('cashAccount.name')->label('Ke akun'),
                                        TextEntry::make('status')
                                            ->badge()
                                            ->helperText(fn (Payment $record): ?string => $record->reject_reason),
                                        TextEntry::make('proof_path')
                                            ->label('Bukti')
                                            ->state(fn (Payment $record): ?string => $record->proof_path ? 'Lihat bukti' : null)
                                            ->url(fn (Payment $record): ?string => $record->proof_path ? route('admin.files.payment-proof', $record) : null, shouldOpenInNewTab: true)
                                            ->color('primary')
                                            ->placeholder('-'),
                                    ]),
                            ]),
                    ]),
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Penerima')
                            ->schema([
                                TextEntry::make('customer_name')->label('Nama'),
                                TextEntry::make('customer_phone')->label('No. HP'),
                                TextEntry::make('address')
                                    ->label('Alamat')
                                    ->state(fn (Order $record): string => "〒{$record->postal_code} {$record->prefecture}, {$record->city}, {$record->address_line1}".($record->address_line2 ? ", {$record->address_line2}" : '')),
                            ]),
                        Section::make('Riwayat status')
                            ->schema([
                                RepeatableEntry::make('statusHistories')
                                    ->hiddenLabel()
                                    ->contained(false)
                                    ->schema([
                                        TextEntry::make('to_status')
                                            ->hiddenLabel()
                                            ->badge()
                                            ->helperText(fn ($record): string => collect([
                                                $record->created_at->setTimezone('Asia/Tokyo')->format('j M H.i'),
                                                $record->user?->name ?? 'Sistem',
                                                $record->note,
                                            ])->filter()->implode(' · ')),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
