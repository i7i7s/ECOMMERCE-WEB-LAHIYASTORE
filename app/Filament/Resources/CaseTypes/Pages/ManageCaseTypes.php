<?php

namespace App\Filament\Resources\CaseTypes\Pages;

use App\Filament\Resources\CaseTypes\CaseTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCaseTypes extends ManageRecords
{
    protected static string $resource = CaseTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
