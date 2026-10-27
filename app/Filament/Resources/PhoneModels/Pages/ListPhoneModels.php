<?php

namespace App\Filament\Resources\PhoneModels\Pages;

use App\Actions\PhoneModels\SyncPhoneModels;
use App\Filament\Resources\PhoneModels\PhoneModelResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListPhoneModels extends ListRecords
{
    protected static string $resource = PhoneModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sinkronkan daftar HP')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Menambah merek dan tipe HP terbaru dari daftar bawaan. Tidak ada yang dihapus, dan tipe yang kamu buat sendiri tidak diubah.')
                ->action(function (): void {
                    ['created' => $created, 'updated' => $updated] = app(SyncPhoneModels::class)->handle();

                    Notification::make()->title("{$created} tipe HP baru, {$updated} diperbarui")->success()->send();
                }),
            CreateAction::make(),
        ];
    }
}
