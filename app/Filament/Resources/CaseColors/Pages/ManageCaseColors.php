<?php

namespace App\Filament\Resources\CaseColors\Pages;

use App\Filament\Resources\CaseColors\CaseColorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCaseColors extends ManageRecords
{
    protected static string $resource = CaseColorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
