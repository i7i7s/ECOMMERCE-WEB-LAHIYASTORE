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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phone_model_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('name', 100);
            $table->unsignedInteger('price_jpy');
            $table->unsignedBigInteger('price_idr');
            $table->unsignedBigInteger('cost_price_idr')->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
