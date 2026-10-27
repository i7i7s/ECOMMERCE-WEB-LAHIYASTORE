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
        Schema::create('preorder_batches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('status', 20)->default('dibuka')->index();
            $table->timestamp('cutoff_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('produced_at')->nullable();
            $table->string('intl_courier', 100)->nullable();
            $table->string('intl_tracking_number', 100)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->date('eta_date')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preorder_batches');
    }
};
