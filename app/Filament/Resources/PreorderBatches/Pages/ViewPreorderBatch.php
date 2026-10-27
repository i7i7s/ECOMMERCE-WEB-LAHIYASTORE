<?php

namespace App\Filament\Resources\PreorderBatches\Pages;

use App\Actions\Batches\AdvanceBatch;
use App\Actions\Batches\CloseBatch;
use App\Enums\BatchStatus;
use App\Filament\Resources\PreorderBatches\PreorderBatchResource;
use App\Filament\Support\BusinessAction;
use App\Models\PreorderBatch;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property PreorderBatch $record
 */
class ViewPreorderBatch extends ViewRecord
{
    protected static string $resource = PreorderBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('downloadZip')
                    ->label('ZIP file cetak + rekap')
                    ->url(fn (): string => route('admin.files.batch-recap', ['batch' => $this->record, 'format' => 'zip'])),
                Action::make('downloadCsv')
                    ->label('rekap.csv')
                    ->url(fn (): string => route('admin.files.batch-recap', ['batch' => $this->record, 'format' => 'csv'])),
                Action::make('downloadPdf')
                    ->label('PDF rekap untuk vendor')
                    ->url(fn (): string => route('admin.files.batch-recap', ['batch' => $this->record, 'format' => 'pdf'])),
            ])
                ->label('Unduh rekap')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->button()
                ->color('gray'),
            Action::make('close')
                ->label('Tutup batch sekarang')
                ->requiresConfirmation()
                ->modalDescription('Batch ditutup lebih awal dari cutoff dan batch baru langsung dibuka.')
                ->visible(fn (): bool => $this->isAdmin() && $this->record->status === BatchStatus::Dibuka)
                ->after(fn () => $this->record->refresh())
                ->action(fn () => BusinessAction::run(
                    fn () => app(CloseBatch::class)->handle($this->record),
                    'Batch ditutup, batch baru dibuka',
                )),
            Action::make('advance')
                ->label(fn (): string => 'Ubah ke '.$this->record->status->next()?->getLabel())
                ->visible(fn (): bool => $this->isAdmin() && ! in_array($this->record->status, [BatchStatus::Dibuka, BatchStatus::Selesai], true))
                ->requiresConfirmation()
                ->modalDescription(fn (): string => 'Status semua order di batch ini ikut berubah dan tercatat di riwayat masing-masing.')
                ->schema(fn (): array => $this->record->status->next() === BatchStatus::DikirimKeJepang ? [
                    TextInput::make('intl_courier')->label('Kurir internasional')->required()->maxLength(100),
                    TextInput::make('intl_tracking_number')->label('No. resi')->required()->maxLength(100),
                    DatePicker::make('eta_date')->label('ETA tiba di Jepang')->required()->minDate(now('Asia/Tokyo')->startOfDay()),
                ] : [])
                ->after(fn () => $this->record->refresh())
                ->action(fn (array $data) => BusinessAction::run(
                    fn () => app(AdvanceBatch::class)->handle($this->record, auth()->user(), $data),
                    'Status batch diperbarui',
                )),
        ];
    }

    private function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
