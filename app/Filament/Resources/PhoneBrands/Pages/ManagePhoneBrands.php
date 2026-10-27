<?php

namespace App\Filament\Resources\PhoneBrands\Pages;

use App\Filament\Resources\PhoneBrands\PhoneBrandResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePhoneBrands extends ManageRecords
{
    protected static string $resource = PhoneBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
