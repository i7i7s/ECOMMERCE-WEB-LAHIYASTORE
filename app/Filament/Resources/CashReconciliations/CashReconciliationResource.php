<?php

namespace App\Filament\Resources\CashReconciliations;

use App\Filament\NavigationGroup;
use App\Filament\Resources\CashReconciliations\Pages\ManageCashReconciliations;
use App\Models\CashReconciliation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * History of reconciliations (FIN-09). New ones are made from the cash account list.
 */
class CashReconciliationResource extends Resource
{
    protected static ?string $model = CashReconciliation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Keuangan;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Rekonsiliasi';

    protected static ?string $modelLabel = 'rekonsiliasi';

    protected static ?string $pluralModelLabel = 'rekonsiliasi';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $format = fn (string $column) => fn (CashReconciliation $record): string => $record->cashAccount->currency->format($record->{$column});

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['cashAccount', 'user']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('reconciled_on')->label('Tanggal')->date('j M Y'),
                TextColumn::make('cashAccount.name')->label('Akun'),
                TextColumn::make('system_balance')->label('Saldo sistem')->state($format('system_balance'))->alignEnd(),
                TextColumn::make('actual_balance')->label('Saldo aktual')->state($format('actual_balance'))->alignEnd(),
                TextColumn::make('difference')
                    ->label('Selisih')
                    ->state(fn (CashReconciliation $record): string => ($record->difference > 0 ? '+' : ($record->difference < 0 ? '−' : '')).$record->cashAccount->currency->format(abs($record->difference)))
                    ->color(fn (CashReconciliation $record): string => $record->difference === 0 ? 'success' : 'danger')
                    ->weight('bold')
                    ->alignEnd(),
                TextColumn::make('note')->label('Catatan')->wrap()->placeholder('-'),
                TextColumn::make('user.name')->label('Oleh'),
            ])
            ->filters([
                SelectFilter::make('cash_account_id')->label('Akun')->relationship('cashAccount', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCashReconciliations::route('/'),
        ];
    }
}
