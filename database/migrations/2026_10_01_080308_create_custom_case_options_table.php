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
        Schema::create('custom_case_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phone_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_type_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('price_jpy');
            $table->unsignedBigInteger('price_idr');
            $table->unsignedBigInteger('cost_price_idr');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['phone_model_id', 'case_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_case_options');
    }
};
