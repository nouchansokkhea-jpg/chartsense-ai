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
        Schema::create('scan_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->string('screenshot_path')->nullable();
            $table->string('file_original_name')->nullable();
            $table->json('raw_data_json')->nullable();
            $table->integer('candles_count')->default(0);
            $table->timestamp('first_candle_timestamp')->nullable();
            $table->timestamp('last_candle_timestamp')->nullable();
            $table->json('file_metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_inputs');
    }
};
