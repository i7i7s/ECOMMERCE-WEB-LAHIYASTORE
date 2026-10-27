<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Printing is done by an outside vendor, so the store no longer measures print resolution.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_item_designs', function (Blueprint $table) {
            $table->dropColumn('effective_dpi');
        });

        DB::table('settings')
            ->whereIn('key', ['dpi_good', 'dpi_minimum', 'min_image_width_px', 'min_image_height_px', 'print_dpi', 'print_bleed_mm'])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_item_designs', function (Blueprint $table) {
            $table->unsignedInteger('effective_dpi')->nullable()->after('rotation');
        });
    }
};
