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
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 50)->index();
            $table->string('timeframe', 20)->index();
            $table->string('input_type', 30)->default('screenshot')->index(); // 'screenshot', 'csv', 'manual_ohlcv', 'live'
            $table->string('data_source', 50)->default('screenshot_upload');
            $table->string('strategy', 50)->default('comprehensive');
            $table->string('status', 30)->default('pending')->index(); // 'pending', 'processing', 'completed', 'failed'
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
};
