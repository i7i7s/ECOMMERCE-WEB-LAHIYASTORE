<?php

namespace App\Filament\Resources\CreditApplications\RelationManagers;

use App\Actions\Credit\RecordCreditPayment;
use App\Actions\Credit\UndoCreditPayment;
use App\Enums\CreditApplicationStatus;
use App\Filament\Support\BusinessAction;
use App\Models\CashAccount;
use App\Models\CreditInstallment;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Payment schedule of a credit purchase. Recording a payment books it in the cash book;
 * undoing one voids that entry (only the latest payment, to keep the schedule in order).
 */
class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    protected static ?string $title = 'Jadwal pembayaran';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sequence')
            ->paginated(false)
            ->columns([
                TextColumn::make('sequence')
                    ->label('Pembayaran')
                    ->formatStateUsing(fn (int $state, CreditInstallment $record): string => $record->label()),
                TextColumn::make('due_date')
                    ->label('Jatuh tempo')
                    ->date('d M Y'),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->formatStateUsing(fn (int $state): string => $this->getOwnerRecord()->money($state)),
                TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->state(fn (CreditInstallment $record): string => match (true) {
                        $record->isPaid() => 'Lunas',
                        $record->isOverdue() => 'Terlambat',
                        default => 'Belum',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Lunas' => 'success',
                        'Terlambat' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('paid_at')
                    ->label('Dibayar')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->description(fn (CreditInstallment $record): ?string => $record->cashTransaction?->cashAccount?->name),
                TextColumn::make('recorder.name')
                    ->label('Dicatat')
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('pay')
                    ->label('Catat bayar')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color('success')
                    ->authorize('update')
                    ->visible(fn (CreditInstallment $record): bool => ! $record->isPaid() && in_array($this->getOwnerRecord()->status, [CreditApplicationStatus::Disetujui, CreditApplicationStatus::Berjalan], true))
                    ->modalHeading(fn (CreditInstallment $record): string => "Catat {$record->label()} ".$this->getOwnerRecord()->money($record->amount))
                    ->modalDescription(fn (CreditInstallment $record): string => $record->isDownPayment() ? 'Setelah DP dicatat, status menjadi Cicilan berjalan: serahkan HP ke pelanggan.' : 'Pemasukan dicatat di buku kas, kategori Kredit HP.')
                    ->schema([
                        Select::make('cash_account_id')
                            ->label('Masuk ke akun')
                            ->options(fn () => CashAccount::query()->where('currency', $this->getOwnerRecord()->currency)->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => CashAccount::query()->where('currency', $this->getOwnerRecord()->currency)->where('is_active', true)->orderByDesc('show_at_checkout')->value('id'))
                            ->required(),
                        DatePicker::make('paid_on')
                            ->label('Tanggal diterima (JST)')
                            ->default(now('Asia/Tokyo')->toDateString())
                            ->maxDate(now('Asia/Tokyo')->toDateString())
                            ->required(),
                    ])
                    ->action(fn (CreditInstallment $record, array $data) => BusinessAction::run(
                        fn () => app(RecordCreditPayment::class)->handle($record, CashAccount::findOrFail($data['cash_account_id']), CarbonImmutable::parse($data['paid_on'], 'Asia/Tokyo'), auth()->user()),
                        'Pembayaran dicatat',
                    )),
                Action::make('undo')
                    ->label('Batalkan bayar')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->authorize('update')
                    ->visible(fn (CreditInstallment $record): bool => $record->isPaid())
                    ->modalDescription('Pemasukan di buku kas di-void (tidak dihapus) dan pembayaran ini kembali belum lunas.')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Alasan')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(fn (CreditInstallment $record, array $data) => BusinessAction::run(
                        fn () => app(UndoCreditPayment::class)->handle($record, auth()->user(), $data['reason']),
                        'Pembayaran dibatalkan',
                    )),
            ]);
    }
}
