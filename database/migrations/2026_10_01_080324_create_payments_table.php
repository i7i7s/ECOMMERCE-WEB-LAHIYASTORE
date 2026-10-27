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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_account_id')->constrained()->restrictOnDelete();
            $table->string('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->string('method', 20);
            $table->string('gateway', 20)->nullable();
            $table->string('gateway_ref')->nullable()->unique();
            $table->string('proof_path')->nullable();
            $table->string('status', 20)->default('menunggu')->index();
            $table->text('reject_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
