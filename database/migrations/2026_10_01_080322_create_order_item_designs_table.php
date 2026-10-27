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
        Schema::create('order_item_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('phone_model_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone_model_text');
            $table->foreignId('case_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('case_color_id')->constrained()->restrictOnDelete();
            $table->boolean('has_preview');
            $table->text('position_note')->nullable();
            $table->string('original_path')->nullable();
            $table->string('preview_path')->nullable();
            $table->string('print_path')->nullable();
            $table->unsignedInteger('image_width_px')->nullable();
            $table->unsignedInteger('image_height_px')->nullable();
            $table->float('pos_x')->default(0);
            $table->float('pos_y')->default(0);
            $table->float('scale')->default(1);
            $table->float('rotation')->default(0);
            $table->unsignedInteger('effective_dpi')->nullable();
            $table->timestamp('rights_agreed_at')->nullable();
            $table->string('review_status', 20)->default('menunggu')->index();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_designs');
    }
};
