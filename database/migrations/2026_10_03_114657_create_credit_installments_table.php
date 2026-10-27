<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payment schedule of a credit purchase: sequence 0 is the down payment, then one row per month.
 * A paid row links to the income it booked in the cash book.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credit_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_application_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->date('due_date');
            $table->unsignedInteger('amount');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('cash_transaction_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['credit_application_id', 'sequence']);
            $table->index(['paid_at', 'due_date']);
        });

        // Income category for down payments and installments, protected like sales income.
        DB::table('transaction_categories')->updateOrInsert(
            ['slug' => 'kredit_hp'],
            ['name' => 'Kredit HP', 'type' => 'masuk', 'scope' => 'penjualan', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_installments');
        DB::table('transaction_categories')->where('slug', 'kredit_hp')->delete();
    }
};
