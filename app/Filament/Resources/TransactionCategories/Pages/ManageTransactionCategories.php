<?php

namespace App\Filament\Resources\TransactionCategories\Pages;

use App\Enums\CashFlow;
use App\Filament\Resources\TransactionCategories\TransactionCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTransactionCategories extends ManageRecords
{
    protected static string $resource = TransactionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah kategori pengeluaran')
                ->mutateDataUsing(fn (array $data): array => [...$data, 'type' => CashFlow::Keluar, 'is_system' => false]),
        ];
    }
}
