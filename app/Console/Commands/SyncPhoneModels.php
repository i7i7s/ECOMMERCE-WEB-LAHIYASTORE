<?php

namespace App\Console\Commands;

use App\Actions\PhoneModels\SyncPhoneModels as SyncPhoneModelsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('phones:sync')]
#[Description('Add and update phone brands and models from the bundled catalog (CUST-12); nothing is deleted')]
class SyncPhoneModels extends Command
{
    public function handle(SyncPhoneModelsAction $syncPhoneModels): int
    {
        ['created' => $created, 'updated' => $updated] = $syncPhoneModels->handle();

        $this->info("{$created} tipe HP baru, {$updated} diperbarui.");

        return self::SUCCESS;
    }
}
