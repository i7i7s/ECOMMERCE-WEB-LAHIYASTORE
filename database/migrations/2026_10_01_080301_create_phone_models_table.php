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
        Schema::create('phone_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phone_brand_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('source', 10)->default('manual');
            $table->string('external_id')->nullable()->unique();
            $table->string('mockup_path')->nullable();
            $table->string('camera_mask_path')->nullable();
            $table->decimal('print_width_mm', 6, 2)->nullable();
            $table->decimal('print_height_mm', 6, 2)->nullable();
            $table->string('model_3d_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_models');
    }
};
