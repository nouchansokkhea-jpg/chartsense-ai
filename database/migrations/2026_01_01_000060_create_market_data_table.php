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
        Schema::create('market_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('symbol', 50)->index();
            $table->string('timeframe', 20)->index();
            $table->timestamp('candle_timestamp')->index();
            $table->decimal('open', 18, 5);
            $table->decimal('high', 18, 5);
            $table->decimal('low', 18, 5);
            $table->decimal('close', 18, 5);
            $table->decimal('volume', 18, 5)->nullable();
            $table->string('source', 50)->default('user_csv');
            $table->timestamps();

            $table->index(['symbol', 'timeframe', 'candle_timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_data');
    }
};
