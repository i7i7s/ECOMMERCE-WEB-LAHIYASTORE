<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The store only sells softcases. Other case types are deactivated, not deleted,
 * because past orders refer to them.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('case_types')->where('slug', '!=', 'softcase')->update(['is_active' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('case_types')->update(['is_active' => true]);
    }
};
