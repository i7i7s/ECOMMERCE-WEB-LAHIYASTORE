<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A credit purchase of one phone. Prices and terms are snapshots of the moment it was submitted.
 * Amounts are yen (credit is only offered in JPY).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credit_applications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->string('item_name');
            $table->string('customer_name');
            $table->string('customer_phone', 20)->nullable();
            $table->string('customer_email')->nullable();
            $table->unsignedInteger('cash_price');
            $table->unsignedInteger('down_payment');
            $table->unsignedTinyInteger('tenor_months');
            $table->decimal('margin_percent', 5, 2);
            $table->unsignedInteger('admin_fee')->default(0);
            $table->unsignedInteger('monthly_installment');
            $table->unsignedInteger('total_payable');
            $table->string('status', 12)->default('diajukan')->index();
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_applications');
    }
};
