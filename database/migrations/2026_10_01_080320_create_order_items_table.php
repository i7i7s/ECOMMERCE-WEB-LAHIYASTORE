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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 20);
            $table->foreignId('product_variant_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('custom_case_option_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('item_name');
            $table->unsignedSmallInteger('qty');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('unit_cost_idr');
            $table->unsignedBigInteger('subtotal');
            $table->boolean('is_packed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
