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
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('transaction_category_id')->constrained()->restrictOnDelete();
            $table->string('type', 10);
            $table->string('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->decimal('exchange_rate', 10, 4);
            $table->unsignedBigInteger('amount_base');
            $table->date('transaction_date');
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('preorder_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->uuid('transfer_group')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['cash_account_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
