<?php

namespace App\Filament\Resources\CreditApplications\Pages;

use App\Actions\Credit\ApproveCreditApplication;
use App\Actions\Credit\CloseCreditApplication;
use App\Enums\CreditApplicationStatus;
use App\Filament\Resources\CreditApplications\CreditApplicationResource;
use App\Filament\Support\BusinessAction;
use App\Models\CreditApplication;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCreditApplication extends ViewRecord
{
    protected static string $resource = CreditApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Setujui')
                ->icon(Heroicon::OutlinedCheck)
                ->color('success')
                ->authorize('update')
                ->visible(fn (CreditApplication $record): bool => $record->status === CreditApplicationStatus::Diajukan)
                ->modalDescription('Jadwal dibuat otomatis: DP jatuh tempo pada tanggal mulai, lalu cicilan setiap bulan di tanggal yang sama.')
                ->schema([
                    DatePicker::make('start')
                        ->label('Tanggal mulai (JST)')
                        ->default(now('Asia/Tokyo')->toDateString())
                        ->required(),
                    Textarea::make('note')
                        ->label('Catatan')
                        ->maxLength(1000),
                ])
                ->action(fn (CreditApplication $record, array $data) => BusinessAction::run(
                    fn () => app(ApproveCreditApplication::class)->handle($record, auth()->user(), CarbonImmutable::parse($data['start'], 'Asia/Tokyo'), $data['note'] ?: null),
                    'Kredit disetujui, jadwal pembayaran dibuat',
                )),
            Action::make('reject')
                ->label('Tolak')
                ->icon(Heroicon::OutlinedXMark)
                ->color('danger')
                ->authorize('update')
                ->visible(fn (CreditApplication $record): bool => $record->status === CreditApplicationStatus::Diajukan)
                ->schema([
                    Textarea::make('reason')
                        ->label('Alasan (terlihat oleh pelanggan)')
                        ->required()
                        ->maxLength(1000),
                ])
                ->action(fn (CreditApplication $record, array $data) => BusinessAction::run(
                    fn () => app(CloseCreditApplication::class)->reject($record, auth()->user(), $data['reason']),
                    'Pengajuan ditolak',
                )),
            Action::make('cancel')
                ->label('Batalkan')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('gray')
                ->authorize('update')
                ->visible(fn (CreditApplication $record): bool => $record->status === CreditApplicationStatus::Disetujui)
                ->modalDescription('Hanya bisa selama DP belum dibayar. Jadwal pembayaran dihapus.')
                ->schema([
                    Textarea::make('reason')
                        ->label('Alasan')
                        ->required()
                        ->maxLength(1000),
                ])
                ->action(fn (CreditApplication $record, array $data) => BusinessAction::run(
                    fn () => app(CloseCreditApplication::class)->cancel($record, auth()->user(), $data['reason']),
                    'Kredit dibatalkan',
                )),
        ];
    }
}
