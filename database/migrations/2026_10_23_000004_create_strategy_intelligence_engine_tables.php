<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Global Trading Strategy Intelligence Engine.
     */
    public function up(): void
    {
        // 1. Strategy Categories (Taxonomy)
        Schema::create('strategy_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Strategies
        Schema::create('strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('strategy_categories')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('category_name')->nullable();
            $table->text('description')->nullable();
            $table->text('intended_market_conditions')->nullable();
            $table->jsonb('market_regime_suitability')->nullable(); // ['trending_bullish', 'ranging_tight', etc.]
            $table->jsonb('timeframes')->nullable(); // ['M15', 'H1', 'H4', etc.]
            $table->jsonb('supported_instruments')->nullable(); // ['XAUUSD', 'EURUSD', 'ALL']
            $table->jsonb('trading_sessions')->nullable(); // ['london', 'new_york', 'asian']
            $table->jsonb('required_indicators')->nullable(); // ['RSI', 'EMA200', 'ATR']
            $table->jsonb('required_market_data')->nullable(); // ['ohlcv', 'swings', 'volume']
            $table->jsonb('risk_constraints')->nullable(); // {'min_rr': 1.5, 'max_risk_pct': 2.0}
            $table->jsonb('known_weaknesses')->nullable(); // ['Chop during FOMC', etc.]
            $table->string('implementation_status')->default('fully_deterministic'); // fully_deterministic, partially_rule_based, discretionary, cannot_evaluate
            $table->string('backtesting_status')->default('pending'); // pending, validated, insufficient_data, failed_criteria
            $table->integer('evidence_confidence')->default(70); // 0-100
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Strategy Versions
        Schema::create('strategy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained('strategies')->cascadeOnDelete();
            $table->string('version_number', 20)->default('1.0.0');
            $table->text('changelog')->nullable();
            $table->jsonb('entry_rules')->nullable();
            $table->jsonb('stop_loss_rules')->nullable();
            $table->jsonb('take_profit_rules')->nullable();
            $table->jsonb('invalidation_rules')->nullable();
            $table->jsonb('exit_rules')->nullable();
            $table->jsonb('compiled_specification')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();
        });

        // 4. Strategy Source References & Attribution
        Schema::create('strategy_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained('strategies')->cascadeOnDelete();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('publication_year', 20)->nullable();
            $table->string('source_url')->nullable();
            $table->string('license_type')->default('educational_fair_use');
            $table->text('attribution_text')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Strategy Evaluations & Backtesting Evidence
        Schema::create('strategy_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained('strategies')->cascadeOnDelete();
            $table->string('symbol', 20)->index();
            $table->string('timeframe', 10)->index();
            $table->string('market_regime', 50)->index(); // trending_bullish, ranging, etc.
            $table->integer('sample_size_trades')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->float('win_rate_pct')->default(0.0);
            $table->float('profit_factor')->default(0.0);
            $table->float('expectancy_r')->default(0.0);
            $table->float('max_drawdown_r')->default(0.0);
            $table->float('net_profit_r')->default(0.0);
            $table->float('avg_rr_ratio')->default(0.0);
            $table->float('slippage_sensitivity_score')->default(0.0);
            $table->boolean('out_of_sample_tested')->default(false);
            $table->string('ranking_tier')->default('insufficient_data'); // tier_1_elite, tier_2_viable, tier_3_conditional, insufficient_data, failing
            $table->text('evaluation_notes')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
        });

        // 6. Strategy Research Documents (Research Lab)
        Schema::create('strategy_research_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('source_url')->nullable();
            $table->text('raw_text');
            $table->text('extracted_summary')->nullable();
            $table->jsonb('extracted_rules')->nullable();
            $table->foreignId('suggested_strategy_id')->nullable()->constrained('strategies')->nullOnDelete();
            $table->string('review_status')->default('pending_review'); // pending_review, approved, rejected
            $table->text('reviewer_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strategy_research_documents');
        Schema::dropIfExists('strategy_evaluations');
        Schema::dropIfExists('strategy_sources');
        Schema::dropIfExists('strategy_versions');
        Schema::dropIfExists('strategies');
        Schema::dropIfExists('strategy_categories');
    }
};
