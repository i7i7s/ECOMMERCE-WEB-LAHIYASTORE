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
        Schema::create('order_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('description');
            $table->string('evidence_path')->nullable();
            $table->string('resolution', 20)->nullable();
            $table->foreignId('replacement_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('status', 20)->default('terbuka')->index();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_complaints');
    }
};
