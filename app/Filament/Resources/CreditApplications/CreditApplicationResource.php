<?php

namespace App\Filament\Resources\CreditApplications;

use App\Enums\CreditApplicationStatus;
use App\Enums\Currency;
use App\Filament\NavigationGroup;
use App\Filament\Resources\CreditApplications\Pages\ListCreditApplications;
use App\Filament\Resources\CreditApplications\Pages\ViewCreditApplication;
use App\Filament\Resources\CreditApplications\RelationManagers\InstallmentsRelationManager;
use App\Models\CreditApplication;
use App\Models\CreditInstallment;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Phones bought on credit (Kredit HP): review applications, approve them (the payment schedule
 * is generated) and record each down payment and installment into the cash book.
 */
class CreditApplicationResource extends Resource
{
    protected static ?string $model = CreditApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Kredit;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Pengajuan & cicilan';

    protected static ?string $modelLabel = 'kredit';

    protected static ?string $pluralModelLabel = 'kredit HP';

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationBadge(): ?string
    {
        $count = CreditApplication::where('status', CreditApplicationStatus::Diajukan)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pengajuan baru';
    }

    public static function yen(?int $amount): string
    {
        return Currency::Jpy->format((int) $amount);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Kredit')
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        TextEntry::make('item_name')->label('HP')->columnSpanFull()->weight('bold'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('cash_price')->label('Harga')->formatStateUsing(fn (int $state, CreditApplication $record): string => $record->money($state)),
                        TextEntry::make('down_payment')->label('DP')->formatStateUsing(fn (int $state, CreditApplication $record): string => $record->money($state)),
                        TextEntry::make('financed')->label('Dicicil')->state(fn (CreditApplication $record): string => $record->money($record->financedAmount())),
                        TextEntry::make('monthly_installment')->label('Cicilan')
                            ->formatStateUsing(fn (int $state, CreditApplication $record): string => $record->money($state)." × {$record->tenor_months} bulan"),
                        TextEntry::make('margin_percent')->label('Margin')
                            ->formatStateUsing(fn (string $state, CreditApplication $record): string => rtrim(rtrim($state, '0'), '.').'% / bulan'.($record->admin_fee ? ' + admin '.$record->money($record->admin_fee) : '')),
                        TextEntry::make('total_payable')->label('Total bayar')->formatStateUsing(fn (int $state, CreditApplication $record): string => $record->money($state)),
                        TextEntry::make('currency')->label('Mata uang')->badge(),
                        TextEntry::make('paid')->label('Sudah dibayar')->state(fn (CreditApplication $record): string => $record->money($record->paidAmount())),
                        TextEntry::make('customer_note')->label('Catatan pelanggan')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('admin_note')->label('Catatan admin')->placeholder('-')->columnSpanFull(),
                    ]),
                Section::make('Pelanggan')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('customer_name')->label('Nama'),
                        TextEntry::make('customer_phone')->label('WhatsApp')->placeholder('-')
                            ->url(fn (CreditApplication $record): ?string => $record->customer_phone ? 'https://wa.me/'.preg_replace('/\D/', '', $record->customer_phone) : null, shouldOpenInNewTab: true),
                        TextEntry::make('customer_email')->label('Email')->placeholder('-'),
                        TextEntry::make('user.credit_status')->label('Status verifikasi')->badge(),
                        TextEntry::make('user.credit_limit')->label('Batas kredit')
                            ->formatStateUsing(fn (?int $state): string => $state === null ? 'Tanpa batas' : self::yen($state))
                            ->placeholder('Tanpa batas'),
                        TextEntry::make('created_at')->label('Diajukan')->dateTime('d M Y H:i')->timezone('Asia/Tokyo'),
                        TextEntry::make('decider.name')->label('Diputuskan oleh')->placeholder('-')
                            ->helperText(fn (CreditApplication $record): ?string => $record->decided_at?->setTimezone('Asia/Tokyo')->format('d M Y H:i')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['installments' => fn ($query) => $query->whereNull('paid_at')]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->description(fn (CreditApplication $record): string => $record->created_at->setTimezone('Asia/Tokyo')->format('d M Y')),
                TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->description(fn (CreditApplication $record): ?string => $record->customer_phone),
                TextColumn::make('item_name')
                    ->label('HP')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('monthly_installment')
                    ->label('Cicilan')
                    ->formatStateUsing(fn (int $state, CreditApplication $record): string => $record->money($state)." × {$record->tenor_months}")
                    ->description(fn (CreditApplication $record): string => 'DP '.$record->money($record->down_payment)),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('next_due')
                    ->label('Tagihan berikutnya')
                    ->state(function (CreditApplication $record): ?string {
                        /** @var CreditInstallment|null $next */
                        $next = $record->installments->sortBy('sequence')->first();

                        return $next ? $next->label().' · '.$next->due_date->format('d M Y') : null;
                    })
                    ->color(fn (CreditApplication $record): ?string => $record->installments->sortBy('sequence')->first()?->isOverdue() ? 'danger' : null)
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(CreditApplicationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InstallmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreditApplications::route('/'),
            'view' => ViewCreditApplication::route('/{record}'),
        ];
    }
}
