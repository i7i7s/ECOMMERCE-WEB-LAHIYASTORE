<?php

namespace App\Filament\Resources\OrderComplaints;

use App\Actions\Orders\ResolveComplaint;
use App\Enums\ComplaintResolution;
use App\Enums\ComplaintStatus;
use App\Enums\ComplaintType;
use App\Enums\OrderStatus;
use App\Filament\NavigationGroup;
use App\Filament\Resources\OrderComplaints\Pages\ManageOrderComplaints;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\BusinessAction;
use App\Models\OrderComplaint;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrderComplaintResource extends Resource
{
    protected static ?string $model = OrderComplaint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Operasional;

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'komplain';

    protected static ?string $pluralModelLabel = 'Komplain';

    public static function getNavigationBadge(): ?string
    {
        $count = OrderComplaint::where('status', ComplaintStatus::Terbuka)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('order_id')->label('Order')
                ->relationship('order', 'code', fn (Builder $query): Builder => $query->whereIn('status', [OrderStatus::Dikirim, OrderStatus::Selesai]))
                ->searchable(['code', 'customer_name'])->required(),
            Select::make('type')->label('Jenis masalah')->options(ComplaintType::class)->required(),
            DatePicker::make('received_at')->label('Tanggal paket diterima (JST)')->maxDate(now('Asia/Tokyo'))->required(),
            Textarea::make('description')->label('Detail masalah')->required()->maxLength(5000)->columnSpanFull(),
            FileUpload::make('evidence_path')->label('Video unboxing')->disk('local')->visibility('private')
                ->directory('complaints/videos')->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm'])->maxSize(10240)->required(),
            FileUpload::make('photo_path')->label('Foto masalah')->disk('local')->visibility('private')
                ->directory('complaints/photos')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(5120)->required(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('order.code')->label('Order')->url(fn (OrderComplaint $record): string => OrderResource::getUrl('view', ['record' => $record->order_id])),
            TextEntry::make('type')->label('Masalah'),
            TextEntry::make('received_at')->label('Diterima')->date(),
            TextEntry::make('description')->label('Detail')->columnSpanFull(),
            TextEntry::make('video')->state('Buka video unboxing')->url(fn (OrderComplaint $record): string => route('admin.files.complaint', [$record, 'video']), true)->visible(fn (OrderComplaint $record): bool => filled($record->evidence_path)),
            TextEntry::make('photo')->state('Buka foto')->url(fn (OrderComplaint $record): string => route('admin.files.complaint', [$record, 'photo']), true)->visible(fn (OrderComplaint $record): bool => filled($record->photo_path)),
            TextEntry::make('resolution')->label('Keputusan')->placeholder('Belum diputuskan'),
            TextEntry::make('resolution_note')->label('Alasan keputusan')->columnSpanFull(),
            TextEntry::make('replacementOrder.code')->label('Order penggantian')
                ->url(fn (OrderComplaint $record): ?string => $record->replacement_order_id ? OrderResource::getUrl('view', ['record' => $record->replacement_order_id]) : null),
            TextEntry::make('handler.name')->label('Ditangani'),
            TextEntry::make('resolved_at')->label('Diselesaikan')->dateTime()->timezone('Asia/Tokyo'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['order', 'replacementOrder', 'handler']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order.code')->label('Order')->searchable()->description(fn (OrderComplaint $record): string => $record->order->customer_name),
                TextColumn::make('type')->label('Masalah'),
                TextColumn::make('status')->badge(),
                TextColumn::make('resolution')->label('Keputusan')->placeholder('Belum diputuskan'),
                TextColumn::make('replacementOrder.code')->label('Penggantian'),
                TextColumn::make('created_at')->label('Diajukan')->dateTime('d M Y H:i')->timezone('Asia/Tokyo'),
            ])->filters([
                SelectFilter::make('status')->options(ComplaintStatus::class),
                SelectFilter::make('order_id')->label('Order')->relationship('order', 'code')->searchable(),
            ])->recordActions([
                ViewAction::make()->slideOver(),
                Action::make('resolve')->label('Selesaikan')->visible(fn (OrderComplaint $record): bool => $record->status === ComplaintStatus::Terbuka && (bool) auth()->user()?->isAdmin())
                    ->authorize('update')->modalHeading('Selesaikan komplain')
                    ->modalDescription('Penggantian mencakup seluruh item order. Pilih refund hanya setelah dana benar-benar dikembalikan; tindakan ini mencatat pengeluaran kas, tidak melakukan transfer bank.')
                    ->schema([
                        Select::make('resolution')->label('Keputusan')->options(ComplaintResolution::class)->required(),
                        Textarea::make('note')->label('Alasan / referensi transfer refund')->required()->maxLength(5000),
                    ])->action(fn (OrderComplaint $record, array $data) => BusinessAction::run(
                        fn () => app(ResolveComplaint::class)->handle($record, auth()->user(), $data['resolution'] instanceof ComplaintResolution ? $data['resolution'] : ComplaintResolution::from($data['resolution']), $data['note']),
                        'Komplain diselesaikan',
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOrderComplaints::route('/')];
    }
}
