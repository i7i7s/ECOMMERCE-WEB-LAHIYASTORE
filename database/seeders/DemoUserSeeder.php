<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local-only admin and staff accounts. Password for both: "password".
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['email' => 'admin@lahiya.test'], [
            'name' => 'Admin Lahiya',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);

        User::firstOrCreate(['email' => 'staff@lahiya.test'], [
            'name' => 'Staff Lahiya',
            'password' => 'password',
            'role' => UserRole::Staff,
        ]);

        User::firstOrCreate(['email' => 'pelanggan@lahiya.test'], [
            'name' => 'Raka Pelanggan',
            'phone' => '08012345678',
            'password' => 'password',
            'role' => UserRole::Customer,
        ]);
    }
}
