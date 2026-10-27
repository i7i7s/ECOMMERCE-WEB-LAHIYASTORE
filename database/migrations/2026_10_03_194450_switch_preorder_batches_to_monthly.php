<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lahiya Store runs one preorder batch per month (cutoff on the last day of the month).
 * The batch that is open now keeps its cutoff; the next batch follows the monthly schedule.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(['key' => 'batch_period'], ['value' => 'monthly']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->where('key', 'batch_period')->delete();
    }
};
