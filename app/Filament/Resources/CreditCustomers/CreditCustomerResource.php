<?php

namespace App\Filament\Resources\CreditCustomers;

use App\Actions\Credit\DecideCreditVerification;
use App\Enums\CreditVerification;
use App\Enums\UserRole;
use App\Filament\NavigationGroup;
use App\Filament\Resources\CreditCustomers\Pages\ManageCreditCustomers;
use App\Filament\Support\BusinessAction;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Customers who may buy on credit. They ask over WhatsApp; the admin verifies them here,
 * optionally with a credit limit in yen (financed amount across open credits).
 */
class CreditCustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'credit-customers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Kredit;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Verifikasi pelanggan';

    protected static ?string $modelLabel = 'pelanggan';

    protected static ?string $pluralModelLabel = 'verifikasi pelanggan';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::Customer);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = User::where('role', UserRole::Customer)->where('credit_status', CreditVerification::Diajukan)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Menunggu verifikasi';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount(['creditApplications', 'orders']))
            ->defaultSort(fn (Builder $query): Builder => $query->orderByRaw("credit_status = 'requested' desc")->latest('credit_requested_at')->latest('id'))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->description(fn (User $record): ?string => $record->email),
                TextColumn::make('phone')
                    ->label('No. HP')
                    ->placeholder('Belum diisi')
                    ->searchable()
                    ->url(fn (User $record): ?string => $record->phone ? 'https://wa.me/'.preg_replace('/\D/', '', $record->phone) : null, shouldOpenInNewTab: true),
                TextColumn::make('credit_status')
                    ->label('Status kredit')
                    ->badge(),
                TextColumn::make('credit_limit')
                    ->label('Batas kredit')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? 'Tanpa batas' : '¥'.number_format($state, 0, ',', '.'))
                    ->placeholder('-'),
                TextColumn::make('orders_count')
                    ->label('Order'),
                TextColumn::make('credit_applications_count')
                    ->label('Kredit'),
                TextColumn::make('credit_requested_at')
                    ->label('Minta verifikasi')
                    ->since()
                    ->timezone('Asia/Tokyo')
                    ->placeholder('-'),
                TextColumn::make('credit_note')
                    ->label('Catatan')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('credit_status')
                    ->label('Status kredit')
                    ->options(CreditVerification::class),
            ])
            ->recordActions([
                Action::make('verify')
                    ->label(fn (User $record): string => $record->credit_status === CreditVerification::Terverifikasi ? 'Ubah batas' : 'Verifikasi')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->authorize('update')
                    ->modalHeading(fn (User $record): string => "Verifikasi kredit {$record->name}")
                    ->modalDescription('Pastikan data pelanggan sudah dicek lewat WhatsApp (identitas, alamat di Jepang, pekerjaan).')
                    ->fillForm(fn (User $record): array => ['limit' => $record->credit_limit, 'note' => $record->credit_note])
                    ->schema([
                        TextInput::make('limit')
                            ->label('Batas kredit (¥)')
                            ->helperText('Total yang boleh dicicil (harga − DP) dari semua kredit yang masih berjalan. Kosongkan untuk tanpa batas.')
                            ->integer()
                            ->minValue(1)
                            ->prefix('¥'),
                        Textarea::make('note')
                            ->label('Catatan internal')
                            ->maxLength(1000),
                    ])
                    ->action(fn (User $record, array $data) => BusinessAction::run(
                        fn () => app(DecideCreditVerification::class)->verify($record, auth()->user(), filled($data['limit']) ? (int) $data['limit'] : null, $data['note'] ?: null),
                        'Pelanggan terverifikasi untuk kredit',
                    )),
                Action::make('reject')
                    ->label(fn (User $record): string => $record->credit_status === CreditVerification::Terverifikasi ? 'Cabut' : 'Tolak')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->authorize('update')
                    ->visible(fn (User $record): bool => $record->credit_status !== CreditVerification::Ditolak)
                    ->modalDescription('Pelanggan tidak bisa mengajukan kredit baru. Kredit yang sudah berjalan tetap berjalan.')
                    ->schema([
                        Textarea::make('note')
                            ->label('Alasan (terlihat oleh pelanggan)')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(fn (User $record, array $data) => BusinessAction::run(
                        fn () => app(DecideCreditVerification::class)->reject($record, auth()->user(), $data['note']),
                        'Verifikasi kredit ditolak',
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCreditCustomers::route('/'),
        ];
    }
}
