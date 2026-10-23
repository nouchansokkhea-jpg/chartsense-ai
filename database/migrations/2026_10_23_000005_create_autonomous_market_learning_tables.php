<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Autonomous Market Learning & Historical Engine.
     */
    public function up(): void
    {
        // 1. Historical Market Candles (Rolling 3-Year Dataset)
        Schema::create('historical_market_candles', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 30)->index();
            $table->string('timeframe', 10)->index();
            $table->timestamp('candle_timestamp')->index();
            $table->decimal('open', 18, 5);
            $table->decimal('high', 18, 5);
            $table->decimal('low', 18, 5);
            $table->decimal('close', 18, 5);
            $table->decimal('volume', 18, 5)->nullable();
            $table->string('volume_type', 20)->default('tick'); // tick, real, estimated
            $table->decimal('spread', 10, 5)->nullable();
            $table->string('trading_session', 30)->default('unknown'); // asian, london, new_york, overlap
            $table->string('data_source', 50)->default('yahoo_finance');
            $table->integer('quality_score')->default(100); // 0-100
            $table->boolean('is_gap')->default(false);
            $table->text('quality_notes')->nullable();
            $table->timestamps();

            $table->unique(['symbol', 'timeframe', 'candle_timestamp'], 'hist_candles_sym_tf_ts_unique');
            $table->index(['symbol', 'timeframe', 'quality_score']);
        });

        // 2. Market Data Sync Jobs (Resumable batch tracking)
        Schema::create('market_data_sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 30)->index();
            $table->string('timeframe', 10)->index();
            $table->string('data_source', 50)->default('yahoo_finance');
            $table->timestamp('window_start');
            $table->timestamp('window_end');
            $table->timestamp('last_synced_candle_at')->nullable();
            $table->integer('candles_synced')->default(0);
            $table->integer('gaps_detected')->default(0);
            $table->string('status', 30)->default('pending'); // pending, syncing, completed, failed
            $table->text('error_message')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        // 3. Versioned Market Features (Zero Lookahead Bias)
        Schema::create('market_features', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 30)->index();
            $table->string('timeframe', 10)->index();
            $table->timestamp('candle_timestamp')->index();
            $table->string('feature_version', 20)->default('v1.0.0');
            $table->float('log_return')->nullable();
            $table->float('body_wick_ratio')->nullable();
            $table->float('atr_14')->nullable();
            $table->float('trend_strength_adx')->nullable();
            $table->float('rsi_14')->nullable();
            $table->float('macd_line')->nullable();
            $table->float('macd_signal')->nullable();
            $table->float('ema_20')->nullable();
            $table->float('ema_50')->nullable();
            $table->float('ema_200')->nullable();
            $table->float('swing_high')->nullable();
            $table->float('swing_low')->nullable();
            $table->boolean('order_block_candidate')->default(false);
            $table->boolean('fvg_candidate')->default(false);
            $table->boolean('liquidity_sweep_candidate')->default(false);
            $table->string('market_regime', 50)->nullable();
            $table->jsonb('features_json')->nullable();
            $table->timestamps();

            $table->unique(['symbol', 'timeframe', 'candle_timestamp', 'feature_version'], 'market_features_unique');
        });

        // 4. Screenshot Ground-Truth Intelligence & Evaluations
        Schema::create('screenshot_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->nullable()->constrained('scans')->nullOnDelete();
            $table->string('screenshot_path');
            $table->string('detected_symbol', 30)->nullable();
            $table->string('detected_timeframe', 10)->nullable();
            $table->jsonb('detected_structures')->nullable();
            $table->string('verified_symbol', 30)->nullable();
            $table->string('verified_timeframe', 10)->nullable();
            $table->boolean('ground_truth_matched')->default(false);
            $table->float('price_extraction_error_pct')->nullable();
            $table->float('structure_recall_score')->nullable();
            $table->float('confidence_score')->nullable();
            $table->float('calibration_error')->nullable();
            $table->jsonb('reviewer_corrections')->nullable();
            $table->text('evaluation_notes')->nullable();
            $table->timestamps();
        });

        // 5. Approved Research Sources & Discovery Feeds
        Schema::create('research_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('category', 50)->default('quantitative_research'); // quantitative_research, institutional_whitepaper, strategy_documentation
            $table->string('base_url');
            $table->string('feed_url')->nullable();
            $table->string('licensing_terms')->default('educational_fair_use');
            $table->integer('credibility_score')->default(85); // 0-100
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamps();
        });

        // 6. Capability-Aware AI Model Registry
        Schema::create('ai_model_registries', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->index(); // gemini, claude, openai, deepseek, deterministic
            $table->string('model_id', 100)->index(); // gemini-2.5-flash, claude-3-7-sonnet, gpt-4o, etc.
            $table->string('display_name');
            $table->jsonb('assigned_roles')->nullable();
            $table->boolean('supports_vision')->default(false);
            $table->boolean('supports_structured_output')->default(true);
            $table->boolean('supports_tool_calling')->default(true);
            $table->boolean('supports_fine_tuning')->default(false);
            $table->integer('context_window_tokens')->default(128000);
            $table->decimal('cost_per_1k_input_tokens', 8, 5)->default(0.00000);
            $table->decimal('cost_per_1k_output_tokens', 8, 5)->default(0.00000);
            $table->integer('avg_latency_ms')->default(0);
            $table->string('evaluation_status', 30)->default('active'); // active, experimental, deprecated
            $table->string('configuration_version', 20)->default('v1.0.0');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'model_id'], 'model_registry_provider_model_unique');
        });

        // 7. Daily Autonomous Learning Runs
        Schema::create('learning_runs', function (Blueprint $table) {
            $table->id();
            $table->date('run_date')->index();
            $table->jsonb('stages_executed');
            $table->integer('candles_ingested')->default(0);
            $table->integer('features_calculated')->default(0);
            $table->integer('discoveries_made')->default(0);
            $table->integer('strategies_evaluated')->default(0);
            $table->integer('candidate_proposals_generated')->default(0);
            $table->integer('proposals_promoted')->default(0);
            $table->integer('proposals_rejected')->default(0);
            $table->jsonb('data_quality_issues')->nullable();
            $table->string('status', 30)->default('running'); // running, completed, failed, partial
            $table->integer('execution_seconds')->default(0);
            $table->text('summary_report')->nullable();
            $table->timestamps();
        });

        // 8. Validated Real & Simulated Trade Outcomes (Feedback Dataset)
        Schema::create('trade_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->nullable()->constrained('scans')->nullOnDelete();
            $table->foreignId('strategy_id')->nullable()->constrained('strategies')->nullOnDelete();
            $table->string('symbol', 30)->index();
            $table->string('timeframe', 10)->index();
            $table->string('market_regime', 50)->nullable();
            $table->string('trade_type', 30)->default('SIMULATED_BACKTEST'); // THEORETICAL_SIGNAL, SIMULATED_BACKTEST, PAPER_TRADE, LIVE_EXECUTED
            $table->string('direction', 10); // BUY, SELL
            $table->timestamp('predicted_at')->index();
            $table->decimal('entry_price', 18, 5);
            $table->decimal('stop_loss', 18, 5);
            $table->decimal('take_profit', 18, 5);
            $table->decimal('exit_price', 18, 5)->nullable();
            $table->timestamp('exit_timestamp')->nullable();
            $table->string('outcome', 20)->default('OPEN'); // WIN, LOSS, BREAKEVEN, EXPIRED, INVALIDATED, OPEN
            $table->float('realized_r_multiple')->nullable();
            $table->float('max_favorable_excursion_r')->nullable();
            $table->float('max_adverse_excursion_r')->nullable();
            $table->float('slippage_deduction_r')->default(0.0);
            $table->integer('evaluation_horizon_candles')->default(24);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['symbol', 'timeframe', 'trade_type', 'outcome']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_outcomes');
        Schema::dropIfExists('learning_runs');
        Schema::dropIfExists('ai_model_registries');
        Schema::dropIfExists('research_sources');
        Schema::dropIfExists('screenshot_evaluations');
        Schema::dropIfExists('market_features');
        Schema::dropIfExists('market_data_sync_jobs');
        Schema::dropIfExists('historical_market_candles');
    }
};
