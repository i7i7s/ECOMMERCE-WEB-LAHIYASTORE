<?php

namespace App\Filament\Resources\CashAccounts;

use App\Actions\Finance\ReconcileCashAccount;
use App\Enums\CashAccountType;
use App\Enums\CashFlow;
use App\Enums\Currency;
use App\Filament\NavigationGroup;
use App\Filament\Resources\CashAccounts\Pages\ManageCashAccounts;
use App\Filament\Support\BusinessAction;
use App\Models\CashAccount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Where money sits, one currency per account (FIN-01). Balances come from the cash book.
 */
class CashAccountResource extends Resource
{
    protected static ?string $model = CashAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Keuangan;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Akun kas';

    protected static ?string $modelLabel = 'akun kas';

    protected static ?string $pluralModelLabel = 'akun kas';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nama akun')->required()->maxLength(100),
                Select::make('type')->label('Jenis')->options(CashAccountType::class)->required(),
                Select::make('currency')
                    ->label('Mata uang')
                    ->options([Currency::Jpy->value => 'JPY', Currency::Idr->value => 'IDR'])
                    ->required()
                    ->disabledOn('edit')
                    ->helperText('Tidak bisa diubah setelah dibuat.'),
                TextInput::make('bank_name')->label('Nama bank')->maxLength(100),
                TextInput::make('account_number')->label('No. rekening')->maxLength(50),
                TextInput::make('account_holder')->label('Atas nama')->maxLength(100),
                Toggle::make('show_at_checkout')->label('Tampil di checkout')->helperText('Rekening tujuan transfer pelanggan untuk mata uang ini.'),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        $notVoided = fn (CashFlow $type) => fn (Builder $query) => $query->whereNull('voided_at')->where('type', $type);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withSum(['cashTransactions as total_in' => $notVoided(CashFlow::Masuk)], 'amount')
                ->withSum(['cashTransactions as total_out' => $notVoided(CashFlow::Keluar)], 'amount'))
            ->columns([
                TextColumn::make('name')->label('Akun')->weight('medium')->description(fn (CashAccount $record): ?string => $record->account_number ? "{$record->bank_name} · {$record->account_number}" : null),
                TextColumn::make('type')->label('Jenis')->badge()->color('gray'),
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->state(fn (CashAccount $record): string => $record->currency->format((int) $record->total_in - (int) $record->total_out))
                    ->weight('bold')
                    ->alignEnd(),
                IconColumn::make('show_at_checkout')->label('Checkout')->boolean(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                Action::make('reconcile')
                    ->label('Rekonsiliasi')
                    ->icon(Heroicon::OutlinedScale)
                    ->modalDescription(fn (CashAccount $record): string => 'Saldo di sistem: '.$record->currency->format($record->balance()).'. Masukkan saldo sebenarnya dari m-banking atau hitungan kas.')
                    ->schema([
                        TextInput::make('actual_balance')->label('Saldo aktual')->integer()->required(),
                        Textarea::make('note')->label('Catatan')->maxLength(500),
                    ])
                    ->action(function (CashAccount $record, array $data): void {
                        $reconciliation = BusinessAction::run(
                            fn () => app(ReconcileCashAccount::class)->handle($record, (int) $data['actual_balance'], auth()->user(), $data['note'] ?? null),
                            'Rekonsiliasi disimpan',
                        );

                        if ($reconciliation->difference !== 0) {
                            Notification::make()
                                ->title('Selisih '.$record->currency->format(abs($reconciliation->difference)).($reconciliation->difference < 0 ? ' (kurang)' : ' (lebih)'))
                                ->body('Telusuri transaksi yang belum dicatat di buku kas.')
                                ->warning()
                                ->send();
                        }
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCashAccounts::route('/'),
        ];
    }
}
