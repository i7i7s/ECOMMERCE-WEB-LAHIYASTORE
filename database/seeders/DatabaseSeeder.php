<?php

namespace Database\Seeders;

use App\Actions\PhoneModels\SyncPhoneModels;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([SystemSeeder::class, PhoneCreditCatalogSeeder::class]);

        if (app()->isLocal()) {
            $this->call([CatalogSeeder::class, DemoUserSeeder::class]);
            app(SyncPhoneModels::class)->handle();
        }
    }
}
