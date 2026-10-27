<?php

namespace App\Filament\Resources\PhoneModels\Pages;

use App\Filament\Resources\PhoneModels\PhoneModelResource;
use App\Filament\Support\SafeDeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPhoneModel extends EditRecord
{
    protected static string $resource = PhoneModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SafeDeleteAction::make(),
        ];
    }
}
