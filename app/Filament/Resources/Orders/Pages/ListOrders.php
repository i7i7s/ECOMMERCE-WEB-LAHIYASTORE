<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Actions\Orders\ExportMonthlyPreorders;
use App\Filament\Resources\Orders\OrderResource;
use App\Support\BatchSchedule;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportMonth')
                ->label('Export Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->modalHeading('Export preorder per bulan')
                ->modalDescription('File Excel berisi ringkasan, daftar order, dan detail item (tipe HP, jenis & warna case) dari order yang dibuat pada bulan itu (JST).')
                ->modalSubmitActionLabel('Unduh Excel')
                ->schema([
                    Select::make('month')
                        ->label('Bulan')
                        ->options(fn (): array => self::monthOptions())
                        ->default(now(BatchSchedule::TIMEZONE)->format('Y-m'))
                        ->required(),
                ])
                ->action(function (array $data): BinaryFileResponse {
                    $export = app(ExportMonthlyPreorders::class)->handle(CarbonImmutable::createFromFormat('!Y-m', $data['month'], BatchSchedule::TIMEZONE));

                    return response()->download($export['path'], $export['filename'])->deleteFileAfterSend();
                }),
            CreateAction::make()->label('Order manual WA/TikTok'),
        ];
    }

    /**
     * This month and the eleven before it, newest first.
     *
     * @return array<string, string>
     */
    private static function monthOptions(): array
    {
        $month = CarbonImmutable::now(BatchSchedule::TIMEZONE)->startOfMonth();

        return collect(range(0, 11))
            ->mapWithKeys(fn (int $back): array => [$month->subMonths($back)->format('Y-m') => $month->subMonths($back)->locale('id')->translatedFormat('F Y')])
            ->all();
    }
}
