<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phones sold on credit: a product sale mode, colour and storage on variants,
 * and an optional colour on photos so the product page shows the chosen colour.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sale_mode', 10)->default('preorder')->after('description');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('color_name', 50)->nullable()->after('name');
            $table->string('color_hex', 7)->nullable()->after('color_name');
            $table->unsignedSmallInteger('storage_gb')->nullable()->after('color_hex');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->string('color_name', 50)->nullable()->after('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn('color_name');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['color_name', 'color_hex', 'storage_gb']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sale_mode');
        });
    }
};
