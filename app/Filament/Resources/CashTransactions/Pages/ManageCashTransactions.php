<?php

namespace App\Filament\Resources\CashTransactions\Pages;

use App\Actions\Finance\RecordExpense;
use App\Actions\Finance\TransferBetweenAccounts;
use App\Enums\CashFlow;
use App\Enums\CategoryScope;
use App\Filament\Resources\CashTransactions\CashTransactionResource;
use App\Filament\Support\BusinessAction;
use App\Models\CashAccount;
use App\Models\PreorderBatch;
use App\Models\Supplier;
use App\Models\TransactionCategory;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ManageCashTransactions extends ManageRecords
{
    protected static string $resource = CashTransactionResource::class;

    protected function getHeaderActions(): array
    {
        $accounts = fn () => CashAccount::query()->where('is_active', true)->get()->mapWithKeys(fn (CashAccount $account): array => [$account->id => "{$account->name} ({$account->currency->value})"]);

        return [
            Action::make('transfer')
                ->label('Transfer antar akun')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('gray')
                ->modalDescription('Misalnya tukar Yen ke Rupiah untuk bayar vendor. Isi jumlah yang benar-benar keluar dan masuk.')
                ->schema([
                    Select::make('from_id')->label('Dari akun')->options($accounts)->required()->live(),
                    Select::make('to_id')->label('Ke akun')->options($accounts)->required()->different('from_id')->live(),
                    TextInput::make('amount_out')->label('Jumlah keluar')->integer()->minValue(1)->required()
                        ->suffix(fn (Get $get): ?string => CashAccount::find($get('from_id'))?->currency->value),
                    TextInput::make('amount_in')->label('Jumlah masuk')->integer()->minValue(1)->required()
                        ->suffix(fn (Get $get): ?string => CashAccount::find($get('to_id'))?->currency->value),
                    DatePicker::make('date')->label('Tanggal')->default(now('Asia/Tokyo'))->required(),
                    TextInput::make('description')->label('Catatan')->maxLength(255),
                ])
                ->action(fn (array $data) => BusinessAction::run(fn () => app(TransferBetweenAccounts::class)->handle(
                    CashAccount::findOrFail($data['from_id']),
                    CashAccount::findOrFail($data['to_id']),
                    (int) $data['amount_out'],
                    (int) $data['amount_in'],
                    CarbonImmutable::parse($data['date'], 'Asia/Tokyo'),
                    auth()->user(),
                    $data['description'] ?? null,
                ), 'Transfer dicatat')),
            Action::make('expense')
                ->label('Catat pengeluaran')
                ->icon(Heroicon::OutlinedMinusCircle)
                ->schema([
                    Select::make('transaction_category_id')
                        ->label('Kategori')
                        ->options(fn () => TransactionCategory::query()
                            ->where('type', CashFlow::Keluar)
                            ->whereIn('scope', [CategoryScope::Batch, CategoryScope::Operasional])
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->required(),
                    Select::make('cash_account_id')->label('Dari akun')->options($accounts)->required()->live(),
                    TextInput::make('amount')->label('Jumlah')->integer()->minValue(1)->required()
                        ->suffix(fn (Get $get): ?string => CashAccount::find($get('cash_account_id'))?->currency->value),
                    DatePicker::make('date')->label('Tanggal')->default(now('Asia/Tokyo'))->required(),
                    Select::make('preorder_batch_id')
                        ->label('Batch (opsional)')
                        ->options(fn () => PreorderBatch::query()->latest('cutoff_at')->limit(20)->pluck('code', 'id'))
                        ->helperText('Isi untuk belanja vendor, ongkir internasional, dan bea masuk.'),
                    Select::make('supplier_id')->label('Vendor (opsional)')->options(fn () => Supplier::query()->orderBy('name')->pluck('name', 'id'))->searchable(),
                    TextInput::make('description')->label('Keterangan')->maxLength(255),
                    FileUpload::make('attachment_path')
                        ->label('Foto nota (opsional)')
                        ->disk('local')
                        ->directory('cash-attachments')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                        ->maxSize(5120),
                ])
                ->action(fn (array $data) => BusinessAction::run(fn () => app(RecordExpense::class)->handle(
                    CashAccount::findOrFail($data['cash_account_id']),
                    TransactionCategory::findOrFail($data['transaction_category_id']),
                    (int) $data['amount'],
                    CarbonImmutable::parse($data['date'], 'Asia/Tokyo'),
                    auth()->user(),
                    [
                        'preorder_batch_id' => $data['preorder_batch_id'] ?? null,
                        'supplier_id' => $data['supplier_id'] ?? null,
                        'description' => $data['description'] ?? null,
                        'attachment_path' => $data['attachment_path'] ?? null,
                    ],
                ), 'Pengeluaran dicatat')),
        ];
    }
}
