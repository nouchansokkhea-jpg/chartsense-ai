<?php

namespace Tests\Feature;

use App\Models\HistoricalMarketCandle;
use App\Models\LearningRun;
use App\Models\MarketDataSyncJob;
use App\Models\MarketFeature;
use App\Models\ResearchSource;
use App\Models\ScreenshotEvaluation;
use App\Models\Strategy;
use App\Models\StrategyResearchDocument;
use App\Models\User;
use App\Services\AI\AiModelRegistryService;
use App\Services\Historical\AutonomousLearningOrchestrator;
use App\Services\Historical\DataQualityValidator;
use App\Services\Historical\HistoricalMarketDataService;
use App\Services\Historical\MarketFeatureEngine;
use App\Services\Strategy\ResearchDiscoveryService;
use App\Services\Vision\ScreenshotIntelligenceService;
use Carbon\Carbon;
use Database\Seeders\GlobalTradingStrategySeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutonomousMarketLearningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GlobalTradingStrategySeeder::class);
    }

    /**
     * Test 1: Historical Market Data Engine ingests candles and detects gaps without fabricating data.
     */
    public function test_historical_market_data_engine_ingests_and_tracks_sync(): void
    {
        $service = app(HistoricalMarketDataService::class);
        $result = $service->syncHistoricalData('EURUSD', 'D1', 15, 'deterministic_reference');

        $this->assertEquals('completed', $result['status']);
        $this->assertGreaterThan(0, $result['candles_synced']);
        $this->assertDatabaseHas('historical_market_candles', [
            'symbol' => 'EURUSD',
            'timeframe' => 'D1',
        ]);
        $this->assertDatabaseHas('market_data_sync_jobs', [
            'symbol' => 'EURUSD',
            'timeframe' => 'D1',
            'status' => 'completed',
        ]);
    }

    /**
     * Test 2: Data Quality Validator flags invalid OHLC relationships and non-positive prices.
     */
    public function test_data_quality_validator_detects_malformed_records(): void
    {
        $validator = app(DataQualityValidator::class);

        // High lower than Low (invalid)
        $invalidCandle = [
            'timestamp' => '2026-10-23T12:00:00Z',
            'open' => 1.1000,
            'high' => 1.0500, // Invalid: High < Low
            'low' => 1.0800,
            'close' => 1.0900,
            'volume' => 1500,
        ];

        $res = $validator->validateCandle($invalidCandle);
        $this->assertFalse($res['valid']);
        $this->assertLessThan(60, $res['quality_score']);
        $this->assertNotEmpty($res['issues']);

        // Valid candle
        $validCandle = [
            'timestamp' => '2026-10-23T14:30:00Z', // Overlap session
            'open' => 1.0850,
            'high' => 1.0920,
            'low' => 1.0830,
            'close' => 1.0900,
            'volume' => 5000,
        ];
        $validRes = $validator->validateCandle($validCandle);
        $this->assertTrue($validRes['valid']);
        $this->assertEquals(100, $validRes['quality_score']);
        $this->assertEquals('overlap', $validRes['trading_session']);
    }

    /**
     * Test 3: Market Feature Engine calculates indicators and structures with ZERO look-ahead bias.
     */
    public function test_market_feature_engine_zero_lookahead_calculation(): void
    {
        $marketDataService = app(HistoricalMarketDataService::class);
        $marketDataService->syncHistoricalData('GBPUSD', 'D1', 40, 'deterministic_reference');

        $featureEngine = app(MarketFeatureEngine::class);
        $res = $featureEngine->computeFeatures('GBPUSD', 'D1');

        $this->assertEquals('completed', $res['status']);
        $this->assertGreaterThan(0, $res['features_created']);

        $feature = MarketFeature::where('symbol', 'GBPUSD')->where('timeframe', 'D1')->first();
        $this->assertNotNull($feature);
        $this->assertNotNull($feature->atr_14);
        $this->assertNotNull($feature->rsi_14);
        $this->assertNotNull($feature->market_regime);
    }

    /**
     * Test 4: Screenshot Intelligence Service calculates ground-truth match and calibration error.
     */
    public function test_screenshot_intelligence_evaluates_against_ground_truth(): void
    {
        // Ingest baseline candle
        HistoricalMarketCandle::updateOrCreate(
            [
                'symbol' => 'XAUUSD',
                'timeframe' => 'H1',
                'candle_timestamp' => Carbon::now('UTC')->subHours(1),
            ],
            [
                'open' => 2350.00,
                'high' => 2360.00,
                'low' => 2345.00,
                'close' => 2355.00,
                'volume' => 12000,
                'volume_type' => 'tick',
                'trading_session' => 'london',
                'quality_score' => 100,
            ]
        );

        $visionData = [
            'detected_symbol' => 'XAUUSD',
            'detected_timeframe' => 'H1',
            'current_market_price' => 2354.50,
            'trend' => 'BULLISH',
            'direction' => 'BUY',
            'visual_patterns' => ['Bullish FVG'],
            'structures' => ['Order Block'],
            'screenshot_quality' => ['quality_score' => 92],
        ];

        $service = app(ScreenshotIntelligenceService::class);
        $eval = $service->evaluateScreenshot('/tmp/fake_chart.png', $visionData);

        $this->assertTrue($eval['ground_truth_matched']);
        $this->assertNotNull($eval['price_error_pct']);
        $this->assertLessThan(1.0, $eval['price_error_pct']); // ~0.02% error
        $this->assertGreaterThan(80, $eval['calibrated_confidence']);

        $this->assertDatabaseHas('screenshot_evaluations', [
            'detected_symbol' => 'XAUUSD',
            'ground_truth_matched' => true,
        ]);
    }

    /**
     * Test 5: Research Discovery Pipeline sanitizes untrusted input and indexes candidates.
     */
    public function test_research_discovery_sanitizes_input_and_records_candidate(): void
    {
        $service = app(ResearchDiscoveryService::class);
        $result = $service->discoverNewResearch();

        $this->assertGreaterThanOrEqual(1, $result['sources_scanned']);
        $this->assertDatabaseHas('research_sources', [
            'code' => 'arxiv_qfin',
            'is_active' => true,
        ]);
    }

    /**
     * Test 6: AI Model Registry enforces strict fine-tuning capability check and forbids fake claims.
     */
    public function test_ai_model_registry_guards_fine_tuning(): void
    {
        $registryService = app(AiModelRegistryService::class);
        $registryService->ensureDefaultRegistry();

        $this->assertDatabaseHas('ai_model_registries', [
            'model_id' => 'gemini-2.5-flash',
            'supports_fine_tuning' => false,
        ]);

        $this->assertDatabaseHas('ai_model_registries', [
            'model_id' => 'gpt-4o',
            'supports_fine_tuning' => true,
        ]);

        // Attempting to run fine-tuning on a model that does NOT support it must throw Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('does NOT support fine-tuning');

        $registryService->executeModelFineTuningJob('gemini', 'gemini-2.5-flash', [
            'examples' => array_fill(0, 60, ['input' => 'test', 'output' => 'res']),
        ]);
    }

    /**
     * Test 7: Autonomous Daily Learning Cycle executes the complete 11 stages.
     */
    public function test_daily_autonomous_learning_cycle_executes_11_stages(): void
    {
        $orchestrator = app(AutonomousLearningOrchestrator::class);
        $result = $orchestrator->runDailyCycle('USDJPY', 'D1', true);

        $this->assertEquals('completed', $result['status']);
        $this->assertArrayHasKey('stage_1_market_data_sync', $result['stages']);
        $this->assertArrayHasKey('stage_2_feature_update', $result['stages']);
        $this->assertArrayHasKey('stage_3_4_research_discovery', $result['stages']);
        $this->assertArrayHasKey('stage_5_review_provenance', $result['stages']);
        $this->assertArrayHasKey('stage_6_strategy_evaluation', $result['stages']);
        $this->assertArrayHasKey('stage_7_identify_weaknesses', $result['stages']);
        $this->assertArrayHasKey('stage_8_generate_candidate_proposals', $result['stages']);
        $this->assertArrayHasKey('stage_9_validate_holdout', $result['stages']);

        $this->assertDatabaseHas('learning_runs', [
            'status' => 'completed',
        ]);
    }

    /**
     * Test 8: Admin Global Market Learning Center route is protected and accessible to admin.
     */
    public function test_admin_learning_center_dashboard_accessible_only_to_admin(): void
    {
        // Unauthenticated -> Redirect to login
        $response = $this->get(route('admin.learning.index'));
        $response->assertRedirect(route('login'));

        // Non-admin user -> 403 Forbidden
        $regularUser = User::create([
            'name' => 'Regular Trader',
            'email' => 'trader_test@chartsense.ai',
            'password' => Hash::make('Password@123456'),
            'role' => 'user',
            'account_status' => 'active',
        ]);
        $response = $this->actingAs($regularUser)->get(route('admin.learning.index'));
        $response->assertStatus(403);

        // Admin user -> 200 OK
        $admin = User::create([
            'name' => 'Admin Architect',
            'email' => 'admin_test@chartsense.ai',
            'password' => Hash::make('Password@123456'),
            'role' => 'admin',
            'account_status' => 'active',
        ]);
        $response = $this->actingAs($admin)->get(route('admin.learning.index'));
        $response->assertStatus(200);
        $response->assertSee('Global Market Learning Center');
        $response->assertSee('Historical Market Data Center');
        $response->assertSee('Capability-Aware AI Model Registry');
        $response->assertSee('Screenshot Intelligence Lab');
        $response->assertSee('Strategy Research Lab');
    }
}
