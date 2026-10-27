<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Credit can be taken in yen or rupiah, like every other purchase. The JPY→IDR rate of the day
 * is kept so rupiah credits count against the customer's credit limit (set in yen).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            $table->string('currency', 3)->default('JPY')->after('item_name');
            $table->decimal('exchange_rate', 10, 4)->nullable()->after('currency');
            $table->unsignedBigInteger('cash_price')->change();
            $table->unsignedBigInteger('down_payment')->change();
            $table->unsignedBigInteger('admin_fee')->default(0)->change();
            $table->unsignedBigInteger('monthly_installment')->change();
            $table->unsignedBigInteger('total_payable')->change();
        });

        Schema::table('credit_installments', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_applications', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate']);
        });
    }
};
