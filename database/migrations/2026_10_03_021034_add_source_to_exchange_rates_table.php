<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->string('source', 10)->default('manual')->after('jpy_to_idr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
