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
        Schema::create('scan_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 30)->index(); // 'BUY', 'SELL', 'WAIT', 'NO VALID SETUP'
            $table->string('trend', 100)->nullable(); // 'Bullish', 'Bearish', 'Sideways', etc.
            $table->string('confidence', 20)->default('moderate'); // 'low', 'moderate', 'high'
            $table->string('entry_type', 50)->nullable(); // 'market_entry', 'limit_zone', 'breakout_confirmation', 'retest_confirmation'
            $table->decimal('entry_min', 18, 5)->nullable();
            $table->decimal('entry_max', 18, 5)->nullable();
            $table->decimal('entry_target', 18, 5)->nullable();
            $table->decimal('stop_loss', 18, 5)->nullable();
            $table->decimal('take_profit_1', 18, 5)->nullable();
            $table->decimal('take_profit_2', 18, 5)->nullable();
            $table->decimal('take_profit_3', 18, 5)->nullable();
            $table->decimal('risk_reward_ratio', 8, 2)->nullable();
            $table->decimal('risk_distance', 18, 5)->nullable();
            $table->decimal('reward_distance_tp1', 18, 5)->nullable();
            $table->text('technical_explanation')->nullable();
            $table->json('reasoning')->nullable();
            $table->json('confirmation_conditions')->nullable();
            $table->json('invalidation_conditions')->nullable();
            $table->json('warnings')->nullable();
            $table->json('support_levels')->nullable();
            $table->json('resistance_levels')->nullable();
            $table->json('calculated_indicators')->nullable();
            $table->json('market_structure')->nullable();
            $table->json('full_ai_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_results');
    }
};
