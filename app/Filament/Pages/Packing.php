<?php

namespace App\Filament\Pages;

use App\Actions\Orders\ShipOrder;
use App\Enums\Currency;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Filament\NavigationGroup;
use App\Filament\Support\BusinessAction;
use App\Models\CashAccount;
use App\Models\Order;
use App\Models\OrderItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Packing queue in Japan (ORD-06, PRD §6.10): staff open each arrived order, match every
 * case with its design thumbnail, then enter the domestic courier and tracking number.
 */
class Packing extends Page implements HasTable
{
    use InteractsWithTable;

    public const COURIERS = ['Yamato' => 'Yamato', 'Sagawa' => 'Sagawa', 'Japan Post' => 'Japan Post', 'Lainnya' => 'Lainnya'];

    protected string $view = 'filament.pages.packing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Operasional;

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Packing';

    protected static ?string $title = 'Antrean packing';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->is_active && in_array($user->role, [UserRole::Admin, UserRole::Staff], true);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Order::query()->where('status', OrderStatus::TibaDiJepang)->count();

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->where('status', OrderStatus::TibaDiJepang)->with('batch')->withCount('items'))
            ->defaultSort('code')
            ->emptyStateHeading('Tidak ada order yang menunggu packing')
            ->emptyStateDescription('Order muncul di sini setelah batchnya berstatus Tiba di Jepang.')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->weight('medium'),
                TextColumn::make('customer_name')
                    ->label('Penerima')
                    ->searchable()
                    ->description(fn (Order $record): string => "{$record->prefecture}, {$record->city}"),
                TextColumn::make('items_count')->label('Item'),
                TextColumn::make('batch.code')->label('Batch'),
            ])
            ->filters([
                SelectFilter::make('batch')->label('Batch')->relationship('batch', 'code'),
            ])
            ->recordActions([
                Action::make('slip')
                    ->label('Packing slip')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('gray')
                    ->url(fn (Order $record): string => route('admin.files.packing-slip', $record), shouldOpenInNewTab: true),
                Action::make('pack')
                    ->label('Packing')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->button()
                    ->slideOver()
                    ->modalHeading(fn (Order $record): string => "Packing {$record->code}")
                    ->modalSubmitActionLabel('Tandai dikirim')
                    ->fillForm(fn (Order $record): array => [
                        'packed_items' => $record->items()->where('is_packed', true)->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
                    ])
                    ->schema(fn (Order $record): array => $this->packingForm($record))
                    ->action(function (Order $record, array $data): void {
                        $record->items()->whereIn('id', $data['packed_items'])->update(['is_packed' => true]);

                        BusinessAction::run(fn () => app(ShipOrder::class)->handle($record, auth()->user(), [
                            'courier' => $data['courier'],
                            'tracking_number' => $data['tracking_number'],
                            'packing_photo_path' => $data['packing_photo'] ?? null,
                            'shipping_cost' => $data['shipping_cost'] ?? null,
                            'cash_account_id' => $data['cash_account_id'] ?? null,
                        ]), "{$record->code} dikirim");
                    }),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private function packingForm(Order $order): array
    {
        $items = $order->items()->with(['design.caseType', 'design.caseColor'])->get();

        return [
            Section::make('Penerima')
                ->description(new HtmlString(e("{$order->customer_name} · {$order->customer_phone}").'<br>'.e("〒{$order->postal_code} {$order->prefecture}, {$order->city}, {$order->address_line1}".($order->address_line2 ? ", {$order->address_line2}" : '')))),
            CheckboxList::make('packed_items')
                ->label('Cocokkan setiap casing dengan foto desainnya')
                ->options($items->mapWithKeys(fn (OrderItem $item): array => [(string) $item->id => "{$item->item_name} ×{$item->qty}"])->all())
                ->descriptions($items->mapWithKeys(fn (OrderItem $item): array => [(string) $item->id => $this->itemDescription($item)])->all())
                ->required()
                ->rules(['size:'.$items->count()])
                ->validationMessages(['size' => 'Semua item harus dicocokkan sebelum dikirim.']),
            Select::make('courier')->label('Kurir')->options(self::COURIERS)->required(),
            TextInput::make('tracking_number')->label('No. resi')->required()->maxLength(100),
            FileUpload::make('packing_photo')
                ->label('Foto paket sebelum ditutup (opsional)')
                ->image()
                ->disk('local')
                ->directory('packing-photos')
                ->maxSize(8192),
            TextInput::make('shipping_cost')
                ->label('Ongkir dibayar toko (opsional)')
                ->prefix('¥')
                ->integer()
                ->minValue(0)
                ->live(onBlur: true),
            Select::make('cash_account_id')
                ->label('Dibayar dari akun')
                ->options(fn () => CashAccount::query()->where('currency', Currency::Jpy)->where('is_active', true)->pluck('name', 'id'))
                ->visible(fn (Get $get): bool => (int) $get('shipping_cost') > 0)
                ->required(fn (Get $get): bool => (int) $get('shipping_cost') > 0),
        ];
    }

    private function itemDescription(OrderItem $item): HtmlString
    {
        $design = $item->design;

        if ($design === null) {
            return new HtmlString('Produk');
        }

        $thumbnail = $design->original_path
            ? '<img src="'.e(route('admin.files.design', $design)).'" alt="" style="height:96px;border-radius:10px;margin:6px 0">'
            : '';

        return new HtmlString($thumbnail.'<div>'.e("{$design->phone_model_text} · {$design->caseType->name} · {$design->caseColor->name}").'</div>');
    }
}
