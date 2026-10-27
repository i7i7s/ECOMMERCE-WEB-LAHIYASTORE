<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Batches\MoveOrderToOpenBatch;
use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\CompleteOrder;
use App\Actions\Orders\ConfirmOrderAvailability;
use App\Actions\Payments\AcceptPayment;
use App\Actions\Payments\RecordManualPayment;
use App\Actions\Payments\RejectPayment;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\BusinessAction;
use App\Models\CashAccount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

/**
 * @property Order $record
 */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('acceptPayment')
                ->label('Terima pembayaran')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription(fn (): string => 'Pemasukan '.$this->record->formattedTotal().' dicatat ke kas dan order masuk batch yang sedang dibuka.')
                ->visible(fn (): bool => $this->isAdmin() && $this->pendingPayment() !== null)
                ->after(fn () => $this->record->refresh())
                ->action(fn () => BusinessAction::run(
                    fn () => app(AcceptPayment::class)->handle($this->pendingPayment(), auth()->user()),
                    'Pembayaran diterima',
                )),
            Action::make('rejectPayment')
                ->label('Tolak bukti')
                ->color('danger')
                ->visible(fn (): bool => $this->isAdmin() && $this->pendingPayment() !== null)
                ->schema([
                    Textarea::make('reason')->label('Alasan (dikirim ke pelanggan)')->required()->maxLength(500),
                ])
                ->after(fn () => $this->record->refresh())
                ->action(fn (array $data) => BusinessAction::run(
                    fn () => app(RejectPayment::class)->handle($this->pendingPayment(), auth()->user(), $data['reason']),
                    'Bukti ditolak, pelanggan bisa upload ulang',
                )),
            Action::make('confirmAvailability')
                ->label('Blank case tersedia')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Vendor sudah konfirmasi blank case tersedia. Pelanggan diberi waktu bayar.')
                ->visible(fn (): bool => $this->record->status === OrderStatus::MenungguKonfirmasi)
                ->after(fn () => $this->record->refresh())
                ->action(fn () => BusinessAction::run(
                    fn () => app(ConfirmOrderAvailability::class)->handle($this->record, auth()->user()),
                    'Order siap dibayar',
                )),
            Action::make('recordPayment')
                ->label('Catat pembayaran')
                ->color('primary')
                ->visible(fn (): bool => $this->record->status === OrderStatus::MenungguPembayaran)
                ->schema([
                    Select::make('cash_account_id')
                        ->label('Masuk ke akun')
                        ->options(fn () => CashAccount::query()->where('currency', $this->record->currency)->where('is_active', true)->pluck('name', 'id'))
                        ->required(),
                    FileUpload::make('proof')
                        ->label('Bukti transfer')
                        ->disk('local')
                        ->directory('payment-proof-uploads')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                        ->maxSize(fn (): int => (int) Setting::valueOf('max_proof_upload_mb', '5') * 1024)
                        ->required(),
                ])
                ->after(fn () => $this->record->refresh())
                ->action(fn (array $data) => BusinessAction::run(
                    fn () => app(RecordManualPayment::class)->handle($this->record, CashAccount::findOrFail($data['cash_account_id']), $data['proof'], auth()->user()),
                    $this->isAdmin() ? 'Pembayaran dicatat dan diterima' : 'Pembayaran dicatat, menunggu verifikasi admin',
                )),
            Action::make('complete')
                ->label('Konfirmasi diterima')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Pelanggan sudah menerima paket. Tanpa konfirmasi, order selesai otomatis 7 hari setelah dikirim.')
                ->visible(fn (): bool => $this->isAdmin() && $this->record->status === OrderStatus::Dikirim)
                ->after(fn () => $this->record->refresh())
                ->action(fn () => BusinessAction::run(
                    fn () => app(CompleteOrder::class)->handle($this->record, auth()->user()),
                    'Order selesai',
                )),
            ActionGroup::make([
                Action::make('moveBatch')
                    ->label('Pindah ke batch berikutnya')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => $this->isAdmin() && $this->record->status === OrderStatus::Dibayar && $this->record->batch?->status === BatchStatus::Ditutup)
                    ->after(fn () => $this->record->refresh())
                    ->action(fn () => BusinessAction::run(
                        fn () => app(MoveOrderToOpenBatch::class)->handle($this->record, auth()->user()),
                        'Order dipindah ke batch yang sedang dibuka',
                    )),
                Action::make('cancel')
                    ->label(fn (): string => $this->record->status === OrderStatus::Dibayar ? 'Batalkan & refund' : 'Batalkan order')
                    ->color('danger')
                    ->visible(fn (): bool => $this->record->status->canTransitionTo(OrderStatus::Dibatalkan)
                    && ($this->record->status !== OrderStatus::Dibayar || $this->isAdmin()))
                    ->modalDescription(fn (): ?string => $this->record->status === OrderStatus::Dibayar
                    ? 'Refund '.$this->record->formattedTotal().' dicatat sebagai kas keluar dari akun yang sama dengan pembayaran.'
                    : null)
                    ->schema([
                        Textarea::make('reason')->label('Alasan')->required()->maxLength(500),
                    ])
                    ->after(fn () => $this->record->refresh())
                    ->action(fn (array $data) => BusinessAction::run(
                        fn () => app(CancelOrder::class)->handle($this->record, auth()->user(), $data['reason']),
                        'Order dibatalkan',
                    )),
            ]),
        ];
    }

    private function pendingPayment(): ?Payment
    {
        return $this->record->payments()->where('status', PaymentStatus::Menunggu)->latest('id')->first();
    }

    private function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
