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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('preorder_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('parent_order_id')->nullable()->constrained('orders')->restrictOnDelete();
            $table->string('source', 20);
            $table->string('currency', 3);
            $table->decimal('exchange_rate', 10, 4);
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->string('postal_code', 8);
            $table->string('prefecture', 30);
            $table->string('city', 100);
            $table->string('address_line1', 150);
            $table->string('address_line2', 150)->nullable();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('total');
            $table->string('status', 30)->index();
            $table->string('domestic_courier', 50)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->string('packing_photo_path')->nullable();
            $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('payment_due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['preorder_batch_id', 'status']);
            $table->index(['status', 'payment_due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
