<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customers buy on credit only after the admin verified their account (asked over WhatsApp).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('credit_status', 12)->default('none')->after('is_active')->index();
            $table->timestamp('credit_requested_at')->nullable()->after('credit_status');
            $table->timestamp('credit_verified_at')->nullable()->after('credit_requested_at');
            $table->foreignId('credit_verified_by')->nullable()->after('credit_verified_at')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('credit_limit')->nullable()->after('credit_verified_by');
            $table->text('credit_note')->nullable()->after('credit_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_verified_by');
            $table->dropColumn(['credit_status', 'credit_requested_at', 'credit_verified_at', 'credit_limit', 'credit_note']);
        });
    }
};
