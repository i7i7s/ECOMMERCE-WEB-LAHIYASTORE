<?php

namespace App\Filament\Resources\CashTransactions;

use App\Actions\Finance\VoidCashTransaction;
use App\Enums\CashFlow;
use App\Filament\NavigationGroup;
use App\Filament\Resources\CashTransactions\Pages\ManageCashTransactions;
use App\Filament\Support\BusinessAction;
use App\Models\CashTransaction;
use App\Support\BaseCurrency;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Cash book (FIN-08): every movement of money, never edited or deleted, only voided.
 */
class CashTransactionResource extends Resource
{
    protected static ?string $model = CashTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Keuangan;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Buku kas';

    protected static ?string $modelLabel = 'transaksi';

    protected static ?string $pluralModelLabel = 'buku kas';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['cashAccount', 'category', 'batch', 'supplier', 'order']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('transaction_date')->label('Tanggal')->date('j M Y')->sortable(),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->wrap()
                    ->description(fn (CashTransaction $record): ?string => collect([
                        $record->batch?->code,
                        $record->supplier?->name,
                        $record->voided_at ? 'VOID: '.$record->void_reason : null,
                    ])->filter()->implode(' · ') ?: null),
                TextColumn::make('category.name')->label('Kategori')->badge()->color('gray'),
                TextColumn::make('cashAccount.name')->label('Akun'),
                TextColumn::make('masuk')
                    ->label('Masuk')
                    ->state(fn (CashTransaction $record): ?string => $record->type === CashFlow::Masuk ? $record->currency->format($record->amount) : null)
                    ->color(fn (CashTransaction $record): string => $record->voided_at ? 'gray' : 'success')
                    ->alignEnd(),
                TextColumn::make('keluar')
                    ->label('Keluar')
                    ->state(fn (CashTransaction $record): ?string => $record->type === CashFlow::Keluar ? $record->currency->format($record->amount) : null)
                    ->color(fn (CashTransaction $record): string => $record->voided_at ? 'gray' : 'danger')
                    ->alignEnd(),
                TextColumn::make('amount_base')
                    ->label(fn (): string => 'Nilai '.BaseCurrency::currency()->value)
                    ->state(fn (CashTransaction $record): string => BaseCurrency::currency()->format($record->amount_base))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('cash_account_id')->label('Akun')->relationship('cashAccount', 'name'),
                SelectFilter::make('transaction_category_id')->label('Kategori')->relationship('category', 'name'),
                SelectFilter::make('preorder_batch_id')->label('Batch')->relationship('batch', 'code'),
                Filter::make('period')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('transaction_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('transaction_date', '<=', $date))),
                TernaryFilter::make('voided')
                    ->label('Void')
                    ->placeholder('Sembunyikan yang di-void')
                    ->trueLabel('Hanya yang di-void')
                    ->falseLabel('Semua')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('voided_at'),
                        false: fn (Builder $query) => $query,
                        blank: fn (Builder $query) => $query->whereNull('voided_at'),
                    ),
            ])
            ->recordActions([
                Action::make('attachment')
                    ->label('Nota')
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->url(fn (CashTransaction $record): string => route('admin.files.cash-attachment', $record), shouldOpenInNewTab: true)
                    ->visible(fn (CashTransaction $record): bool => $record->attachment_path !== null),
                Action::make('void')
                    ->label('Void')
                    ->color('danger')
                    ->visible(fn (CashTransaction $record): bool => $record->voided_at === null && $record->payment_id === null && ! in_array($record->category->slug, ['penjualan', 'refund'], true))
                    ->modalDescription(fn (CashTransaction $record): string => $record->transfer_group ? 'Kedua sisi transfer ikut di-void.' : 'Transaksi tetap tercatat, tapi tidak dihitung di saldo.')
                    ->schema([
                        Textarea::make('reason')->label('Alasan')->required()->maxLength(500),
                    ])
                    ->action(fn (CashTransaction $record, array $data) => BusinessAction::run(
                        fn () => app(VoidCashTransaction::class)->handle($record, auth()->user(), $data['reason']),
                        'Transaksi di-void',
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCashTransactions::route('/'),
        ];
    }
}
