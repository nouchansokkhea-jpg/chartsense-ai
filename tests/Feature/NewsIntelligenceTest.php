<?php

namespace Tests\Feature;

use App\Models\EconomicCalendarEvent;
use App\Models\NewsArticle;
use App\Models\NewsProvider;
use App\Models\TradeRestriction;
use App\Models\User;
use App\Services\Risk\NewsRiskFirewallService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_global_news_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('news.index'));

        $response->assertOk();
        $response->assertSee('GLOBAL MARKET NEWS INTELLIGENCE');
        $response->assertSee('Current Market Risk');
        $response->assertSee('Upcoming High-Impact Economic Releases');
        $response->assertSee('Live Financial News Stream');
        $response->assertSee('News Ingestion Provider Health');
    }

    public function test_news_firewall_blocks_trade_during_pre_event_window(): void
    {
        // Create an upcoming CRITICAL event 20 minutes from now
        $event = EconomicCalendarEvent::create([
            'provider_code' => 'calendar_feed',
            'event_name' => 'US Federal Reserve FOMC Rate Decision',
            'country_code' => 'US',
            'currency' => 'USD',
            'scheduled_at' => now()->utc()->addMinutes(20),
            'importance' => 'CRITICAL',
            'previous_value' => '5.25%',
            'forecast_value' => '5.25%',
            'status' => 'scheduled',
            'official_source' => 'Federal Reserve',
        ]);

        $firewall = app(NewsRiskFirewallService::class);
        $candidatePlan = [
            'direction' => 'BUY',
            'entry_target' => 2650.0,
            'stop_loss' => 2635.0,
            'take_profit_1' => 2680.0,
            'confidence' => 'high',
        ];

        $evaluation = $firewall->evaluateTradeSetup('XAUUSD', $candidatePlan);

        $this->assertTrue($evaluation['is_blocked']);
        $this->assertEquals('NEWS RISK — TRADE BLOCKED', $evaluation['firewall_status']);
        $this->assertEquals('WAIT', $evaluation['trading_plan']['direction']);
        $this->assertEquals('BLOCKED_BY_NEWS_FIREWALL', $evaluation['trading_plan']['trade_eligibility']);
        $this->assertStringContainsString('FOMC', $evaluation['reason']);

        // Clean up
        $event->delete();
    }

    public function test_news_firewall_stabilizing_window_after_event_release(): void
    {
        // Event occurred 5 minutes ago (within post-release 15-minute stabilization window)
        $event = EconomicCalendarEvent::create([
            'provider_code' => 'calendar_feed',
            'event_name' => 'US Consumer Price Index (CPI) YoY',
            'country_code' => 'US',
            'currency' => 'USD',
            'scheduled_at' => now()->utc()->subMinutes(5),
            'importance' => 'CRITICAL',
            'previous_value' => '2.5%',
            'forecast_value' => '2.3%',
            'actual_value' => '2.6%',
            'status' => 'published',
            'official_source' => 'Bureau of Labor Statistics',
        ]);

        $firewall = app(NewsRiskFirewallService::class);
        $candidatePlan = [
            'direction' => 'BUY',
            'entry_target' => 2650.0,
            'stop_loss' => 2635.0,
            'take_profit_1' => 2680.0,
            'confidence' => 'high',
        ];

        $evaluation = $firewall->evaluateTradeSetup('XAUUSD', $candidatePlan);

        $this->assertTrue($evaluation['is_stabilizing']);
        $this->assertEquals('NEWS RISK — STABILIZING', $evaluation['firewall_status']);
        $this->assertEquals('WAIT', $evaluation['trading_plan']['direction']);
        $this->assertStringContainsString('stabilizing', strtolower($evaluation['reason']));

        $event->delete();
    }

    public function test_news_firewall_allows_trade_when_disabled_in_system_settings(): void
    {
        \App\Models\SystemSetting::set('firewall_enabled', 'false');

        // Impending event 10 minutes out
        $event = EconomicCalendarEvent::create([
            'provider_code' => 'calendar_feed',
            'event_name' => 'US Non-Farm Payrolls (NFP)',
            'country_code' => 'US',
            'currency' => 'USD',
            'scheduled_at' => now()->utc()->addMinutes(10),
            'importance' => 'CRITICAL',
            'status' => 'scheduled',
            'official_source' => 'BLS',
        ]);

        $firewall = app(NewsRiskFirewallService::class);
        $candidatePlan = [
            'direction' => 'SELL',
            'entry_target' => 2660.0,
            'stop_loss' => 2675.0,
            'take_profit_1' => 2630.0,
            'confidence' => 'high',
        ];

        $evaluation = $firewall->evaluateTradeSetup('XAUUSD', $candidatePlan);

        $this->assertFalse($evaluation['is_blocked']);
        $this->assertEquals('CLEARED', $evaluation['firewall_status']);
        $this->assertEquals('SELL', $evaluation['trading_plan']['direction']);

        // Restore setting and cleanup
        \App\Models\SystemSetting::set('firewall_enabled', 'true');
        $event->delete();
    }

    public function test_economic_surprise_calculator_computes_deviations_and_transmission(): void
    {
        $calculator = app(\App\Services\News\EconomicSurpriseCalculator::class);

        // 1. CPI higher than forecast -> Hawkish for USD (Bullish), Bearish for Gold
        $cpiResult = $calculator->calculateSurprise('US Consumer Price Index (CPI) YoY', '2.8%', '2.5%');
        $this->assertEquals('BETTER_THAN_EXPECTED', $cpiResult['surprise_direction']);
        $this->assertEquals('BULLISH', $cpiResult['currency_bias']);
        $this->assertEquals('BEARISH', $cpiResult['gold_bias']);
        $this->assertGreaterThan(0, $cpiResult['surprise_score']);

        // 2. Unemployment higher than forecast -> Bearish for USD, Bullish for Gold
        $unempResult = $calculator->calculateSurprise('US Unemployment Rate', '4.3%', '4.0%');
        $this->assertEquals('WORSE_THAN_EXPECTED', $unempResult['surprise_direction']);
        $this->assertEquals('BEARISH', $unempResult['currency_bias']);
        $this->assertEquals('BULLISH', $unempResult['gold_bias']);

        // 3. Exactly in line
        $inlineResult = $calculator->calculateSurprise('US Core PPI', '2.0%', '2.0%');
        $this->assertEquals('AS_EXPECTED', $inlineResult['surprise_direction']);
        $this->assertEquals('NEUTRAL', $inlineResult['currency_bias']);
    }

    public function test_market_data_fusion_flags_price_discrepancy(): void
    {
        $fusion = app(\App\Services\Vision\MarketDataFusionService::class);

        $candles = [
            ['open' => 2650.0, 'high' => 2655.0, 'low' => 2648.0, 'close' => 2652.0, 'volume' => 1000],
        ];

        // Vision reports a price of 2750.0 (> 3.5% discrepancy)
        $visionPlan = [
            'detected_symbol' => 'XAUUSD',
            'detected_timeframe' => 'M15',
            'current_market_price' => 2750.0,
            'screenshot_quality' => [
                'quality_score' => 85,
                'is_blurry' => false,
                'price_axis_readable' => true,
            ],
        ];

        $report = $fusion->fuse($visionPlan, $candles, 'XAUUSD', 'M15');

        $this->assertTrue($report['discrepancy_flagged']);
        $this->assertEquals('FUSED_WITH_DISCREPANCY', $report['fusion_status']);
        $this->assertGreaterThan(1.5, $report['price_difference_pct']);
        // Numerical price is anchored to verified candle close (2652.0)
        $this->assertEquals(2652.0, $report['effective_price']);
    }

    public function test_telegram_service_deduplicates_repeated_alerts_within_hour(): void
    {
        $tg = app(\App\Services\Notifications\TelegramService::class);

        // First alert
        $res1 = $tg->sendAlert(
            'trade_blocked',
            'XAUUSD',
            'CPI Freeze Window',
            'Orders blocked 30m before US CPI release.',
            'CRITICAL'
        );

        $this->assertContains($res1['status'], ['sent', 'mock_sent']);
        $this->assertNotEmpty($res1['dedup_key']);

        // Second alert within same hour with identical parameters
        $res2 = $tg->sendAlert(
            'trade_blocked',
            'XAUUSD',
            'CPI Freeze Window',
            'Orders blocked 30m before US CPI release.',
            'CRITICAL'
        );

        $this->assertEquals('duplicate_suppressed', $res2['status']);
        $this->assertEquals($res1['dedup_key'], $res2['dedup_key']);

        // Verify logged in database
        $this->assertDatabaseHas('telegram_alert_logs', [
            'dedup_key' => $res1['dedup_key'],
            'symbol' => 'XAUUSD',
        ]);
    }

    public function test_admin_can_update_news_risk_firewall_settings(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.settings.update'), [
            'exness_referral_url' => 'https://one.exnessonelink.com/a/l4l2nsdkfb?source=app',
            'ai_provider' => 'gemini',
            'ai_model' => 'gemini-2.5-flash',
            'referral_verification_mode' => 'manual_review',
            'firewall_enabled' => 'true',
            'firewall_critical_block_minutes' => 45,
            'firewall_high_block_minutes' => 20,
            'firewall_post_stabilization_minutes' => 25,
            'telegram_alerts_enabled' => 'true',
        ]);

        $response->assertRedirect(route('admin.settings'));
        $response->assertSessionHas('success');

        $this->assertEquals('45', \App\Models\SystemSetting::get('firewall_critical_block_minutes'));
        $this->assertEquals('20', \App\Models\SystemSetting::get('firewall_high_block_minutes'));
        $this->assertEquals('25', \App\Models\SystemSetting::get('firewall_post_stabilization_minutes'));
    }

    public function test_live_stream_api_returns_json_with_articles_and_risk_gauge(): void
    {
        $user = User::factory()->create(['account_status' => 'active']);

        NewsArticle::create([
            'provider_code' => 'investing_live',
            'title' => 'UK October Flash Services PMI beats expectations at 53.1',
            'canonical_url' => 'https://investinglive.com/news/uk-october-flash-services-pmi',
            'summary' => 'UK Flash Services PMI rebounded sharply, demonstrating ongoing resilience.',
            'source_name' => 'InvestingLive',
            'published_at' => now(),
            'received_at' => now(),
            'country_code' => 'GB',
            'affected_assets' => ['GBPUSD', 'EURGBP'],
            'event_category' => 'market_macro',
            'sentiment_classification' => 'BULLISH',
            'sentiment_score' => 60.0,
            'importance' => 'HIGH',
            'cluster_hash' => 'test_hash_gbp_pmi',
        ]);

        $response = $this->actingAs($user)->getJson(route('news.live', ['symbol' => 'GBPUSD']));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'latest_id',
            'new_count',
            'articles',
            'market_risk' => ['risk_score', 'sentiment_bias', 'macro_narrative'],
            'upcoming_events',
            'active_restrictions_count',
            'server_time',
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('articles'));
    }

    public function test_sync_now_api_triggers_global_wire_sync(): void
    {
        $user = User::factory()->create(['account_status' => 'active']);

        $response = $this->actingAs($user)->postJson(route('news.sync'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'details' => ['total_fetched', 'new_articles_inserted', 'providers'],
        ]);
        $this->assertTrue($response->json('success'));
    }

    public function test_forex_factory_news_provider_fetches_and_ingests_real_time_news(): void
    {
        $provider = app(\App\Services\News\Providers\ForexFactoryNewsProvider::class);

        $this->assertEquals('forexfactory', $provider->getProviderCode());

        $articles = $provider->fetchArticles('forex');
        $this->assertIsArray($articles);
        $this->assertNotEmpty($articles);

        $first = $articles[0];
        $this->assertEquals('Forex Factory', $first['source_name']);
        $this->assertNotEmpty($first['title']);
        $this->assertNotEmpty($first['canonical_url']);
        $this->assertStringContainsString('forexfactory.com', $first['canonical_url']);
        $this->assertArrayHasKey('importance', $first);
        $this->assertArrayHasKey('sentiment', $first);

        // Test synchronization via GlobalNewsAggregatorService
        $aggregator = app(\App\Services\News\GlobalNewsAggregatorService::class);
        $result = $aggregator->syncAll(force: true);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('news_providers', [
            'code' => 'forexfactory',
            'name' => 'Forex Factory Live Real-Time News & Release Wire',
        ]);
        $this->assertDatabaseHas('news_articles', [
            'source_name' => 'Forex Factory',
        ]);
    }
}
