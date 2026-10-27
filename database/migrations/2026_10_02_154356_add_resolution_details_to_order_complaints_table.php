<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_complaints', function (Blueprint $table): void {
            $table->string('photo_path')->nullable();
            $table->date('received_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_complaints', function (Blueprint $table): void {
            $table->dropColumn(['photo_path', 'received_at', 'resolution_note', 'resolved_at']);
        });
    }
};
