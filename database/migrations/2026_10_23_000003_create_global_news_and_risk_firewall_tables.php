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
        // 1. News Providers
        Schema::create('news_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // 'rss', 'rest_api', 'calendar', 'official'
            $table->string('base_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('rate_limit_per_minute')->default(60);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('health_status')->default('healthy'); // 'healthy', 'degraded', 'offline'
            $table->text('health_message')->nullable();
            $table->integer('latency_ms')->nullable();
            $table->timestamps();
        });

        // 2. News Articles with Deduplication and Clustering
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->string('provider_code')->index();
            $table->text('title');
            $table->string('canonical_url')->nullable()->index();
            $table->text('summary')->nullable();
            $table->string('source_name');
            $table->timestamp('published_at')->index(); // Original publication UTC
            $table->timestamp('received_at')->index();  // Ingested UTC
            $table->string('country_code', 10)->nullable()->index();
            $table->jsonb('affected_assets')->nullable(); // ['XAUUSD', 'EURUSD', 'USD']
            $table->string('event_category')->default('market_macro');
            $table->string('sentiment_classification')->default('UNCERTAIN'); // BULLISH, BEARISH, MIXED, UNCERTAIN
            $table->float('sentiment_score')->default(0.0);
            $table->string('importance')->default('MODERATE'); // LOW, MODERATE, HIGH, CRITICAL
            $table->string('cluster_hash', 64)->nullable()->index();
            $table->string('verification_status')->default('reputable_wire'); // verified_official, reputable_wire, unverified
            $table->timestamps();
        });

        // 3. Economic Calendar Events & Numerical Surprises
        Schema::create('economic_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider_code')->index();
            $table->string('event_name')->index();
            $table->string('country_code', 10)->index();
            $table->string('currency', 10)->index(); // USD, EUR, GBP, JPY, etc.
            $table->timestamp('scheduled_at')->index(); // UTC
            $table->string('importance')->default('HIGH'); // LOW, MODERATE, HIGH, CRITICAL
            $table->string('previous_value')->nullable();
            $table->string('forecast_value')->nullable();
            $table->string('actual_value')->nullable();
            $table->string('revised_value')->nullable();
            $table->string('unit', 20)->nullable(); // %, K, M, B, Index
            $table->float('surprise_score')->nullable(); // Numerical surprise percentage
            $table->string('surprise_direction', 32)->nullable(); // BETTER_THAN_EXPECTED, WORSE_THAN_EXPECTED, AS_EXPECTED, MIXED
            $table->string('status', 32)->default('scheduled'); // scheduled, released, delayed, cancelled
            $table->string('official_source')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Event-to-Asset Relevance Mappings
        Schema::create('event_asset_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('event_pattern'); // e.g. 'CPI', 'Nonfarm Payrolls', 'Interest Rate', 'FOMC'
            $table->string('currency', 10);
            $table->jsonb('affected_symbols'); // ['XAUUSD', 'EURUSD', 'USOIL', 'US30']
            $table->integer('pre_event_block_minutes')->default(15);
            $table->integer('post_event_stabilization_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Active Trade Restrictions (News Risk Firewall)
        Schema::create('trade_restrictions', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->index();
            $table->foreignId('economic_event_id')->nullable()->constrained('economic_calendar_events')->nullOnDelete();
            $table->string('status')->default('BLOCKED'); // BLOCKED, STABILIZING, CLEARED
            $table->string('severity')->default('CRITICAL'); // HIGH, CRITICAL
            $table->text('reason');
            $table->timestamp('activated_at')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('cleared_at')->nullable();
            $table->boolean('override_prohibited')->default(true); // AI cannot override
            $table->timestamps();
        });

        // 6. User Alert Preferences (Telegram & Risk)
        Schema::create('user_alert_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('telegram_chat_id')->nullable();
            $table->boolean('notify_approaching_events')->default(true);
            $table->boolean('notify_trade_blocks')->default(true);
            $table->boolean('notify_economic_releases')->default(true);
            $table->boolean('notify_model_disagreements')->default(true);
            $table->boolean('is_telegram_connected')->default(false);
            $table->timestamps();
        });

        // 7. Telegram Delivery Logs
        Schema::create('telegram_alert_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('alert_type'); // event_approaching, trade_blocked, trade_cleared, release_published, model_disagreement
            $table->string('dedup_key')->unique();
            $table->string('symbol')->nullable();
            $table->string('chat_id');
            $table->text('message_text');
            $table->string('delivery_status')->default('sent'); // sent, failed, mock_sent
            $table->string('telegram_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_alert_logs');
        Schema::dropIfExists('user_alert_preferences');
        Schema::dropIfExists('trade_restrictions');
        Schema::dropIfExists('event_asset_mappings');
        Schema::dropIfExists('economic_calendar_events');
        Schema::dropIfExists('news_articles');
        Schema::dropIfExists('news_providers');
    }
};
