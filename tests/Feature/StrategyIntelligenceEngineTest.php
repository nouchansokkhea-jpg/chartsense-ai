<?php

namespace Tests\Feature;

use App\Models\Strategy;
use App\Models\StrategyCategory;
use App\Models\StrategyEvaluation;
use App\Models\StrategyVersion;
use App\Models\User;
use App\Services\AI\MultiAiAnalysisService;
use App\Services\Strategy\StrategyCompilerService;
use App\Services\Strategy\StrategyEvaluationService;
use App\Services\Strategy\StrategyRetrievalService;
use App\Services\TechnicalAnalysis\MarketRegimeService;
use Database\Seeders\GlobalTradingStrategySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StrategyIntelligenceEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $trader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GlobalTradingStrategySeeder::class);

        $this->admin = User::create([
            'name' => 'Admin Architect',
            'email' => 'admin@chartsense.ai',
            'password' => Hash::make('Password@123456'),
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $this->trader = User::create([
            'name' => 'Standard Trader',
            'email' => 'trader@chartsense.ai',
            'password' => Hash::make('Password@123456'),
            'role' => 'user',
            'account_status' => 'active',
        ]);
    }

    public function test_strategy_compiler_classifies_feasibility_accurately(): void
    {
        $compiler = app(StrategyCompilerService::class);

        // 1. Fully deterministic (formulaic indicator crossover)
        $spec1 = $compiler->compileSpecification([
            'name' => 'EMA Crossover Test',
            'category_slug' => 'indicator_based',
            'entry_rules' => ['EMA 9 crosses above EMA 21 with RSI > 50'],
            'stop_loss_rules' => ['Fixed stop loss 20 pips below entry'],
            'take_profit_rules' => ['Fixed take profit 40 pips'],
            'invalidation_rules' => ['EMA crosses back below'],
        ]);
        $this->assertEquals('fully_deterministic', $spec1['implementation_status']);

        // 2. Partially rule-based (SMC FVG & Liquidity Sweeps)
        $spec2 = $compiler->compileSpecification([
            'name' => 'ICT FVG Retest',
            'category_slug' => 'ict_smc',
            'entry_rules' => ['Liquidity pool sweep followed by displacement Fair Value Gap formation and retest'],
            'stop_loss_rules' => ['Stop loss placed behind swing anchor'],
            'take_profit_rules' => ['Opposite side liquidity target'],
            'invalidation_rules' => ['Close beyond displacement candle'],
        ]);
        $this->assertEquals('partially_rule_based', $spec2['implementation_status']);

        // 3. Discretionary (feel of the tape / intuition)
        $spec3 = $compiler->compileSpecification([
            'name' => 'Intuition Scalp',
            'category_slug' => 'price_action',
            'entry_rules' => ['Wait for the feel of the tape and institutional intuition to emerge'],
            'stop_loss_rules' => ['Discretionary stop based on mood'],
            'take_profit_rules' => ['Take profit when feeling satisfied'],
            'invalidation_rules' => ['Momentum stalls'],
        ]);
        $this->assertEquals('discretionary', $spec3['implementation_status']);
    }

    public function test_market_regime_service_classifies_trending_and_ranging_deterministically(): void
    {
        $service = app(MarketRegimeService::class);

        // Synthetic strong bullish trend candles (higher highs, higher lows, strong close)
        $bullishCandles = [];
        $base = 2600.0;
        for ($i = 0; $i < 60; $i++) {
            $open = $base + ($i * 3.0);
            $high = $open + 5.0;
            $low = $open - 1.0;
            $close = $open + 4.5;
            $bullishCandles[] = [
                'open' => $open,
                'high' => $high,
                'low' => $low,
                'close' => $close,
                'volume' => 1000 + ($i * 50),
                'timestamp' => now()->subMinutes((60 - $i) * 15)->toIso8601String(),
            ];
        }

        $regime = $service->classify($bullishCandles);
        $this->assertEquals(MarketRegimeService::REGIME_TRENDING_BULLISH, $regime['regime']);
        $this->assertGreaterThanOrEqual(60.0, $regime['confidence']);
        $this->assertContains('ict_smc', $regime['suitable_categories']);
        $this->assertContains('range_trading', $regime['unsuitable_categories']);
    }

    public function test_strategy_retrieval_service_scores_and_disclaims_model_weights(): void
    {
        $retrievalService = app(StrategyRetrievalService::class);

        $result = $retrievalService->retrieveRelevantStrategies(
            symbol: 'EURUSD',
            timeframe: '15m',
            marketRegime: 'TRENDING_BULLISH',
            technicalContext: [],
            limit: 3
        );

        $this->assertNotEmpty($result['candidates']);
        $this->assertCount(3, $result['candidates']);

        // Verify strict architectural disclaimer about dynamic in-context retrieval rather than weights
        $promptContext = $result['prompt_context'];
        $this->assertStringContainsString('RETRIEVED STRATEGY SPECIFICATIONS', $promptContext);
        $this->assertStringContainsString('DYNAMIC IN-CONTEXT REFERENCE ONLY', $promptContext);
        $this->assertStringContainsString('DO NOT represent model fine-tuning, training weights', $promptContext);
    }

    public function test_strategy_evaluation_enforces_sample_size_guardrails(): void
    {
        $evalService = app(StrategyEvaluationService::class);

        // Scenario 1: Only 5 trades (< 20 minimum sample size)
        $smallTrades = [
            ['outcome' => 'win', 'r_multiple' => 2.0],
            ['outcome' => 'win', 'r_multiple' => 2.5],
            ['outcome' => 'loss', 'r_multiple' => -1.0],
            ['outcome' => 'win', 'r_multiple' => 1.5],
            ['outcome' => 'loss', 'r_multiple' => -1.0],
        ];

        $smallResult = $evalService->evaluateTrades($smallTrades);
        $this->assertFalse($smallResult['is_rankable']);
        $this->assertEquals('insufficient_data', $smallResult['ranking_tier']);
        $this->assertStringContainsString('NOT ENOUGH VALIDATED DATA', $smallResult['summary_label']);

        // Scenario 2: 50 validated trades with positive expectancy (N >= 50, PF >= 1.4)
        $largeTrades = [];
        for ($i = 0; $i < 35; $i++) {
            $largeTrades[] = ['outcome' => 'win', 'r_multiple' => 2.2];
        }
        for ($i = 0; $i < 15; $i++) {
            $largeTrades[] = ['outcome' => 'loss', 'r_multiple' => -1.0];
        }

        $largeResult = $evalService->evaluateTrades($largeTrades);
        $this->assertTrue($largeResult['is_rankable']);
        $this->assertEquals('tier_a', $largeResult['ranking_tier']);
        $this->assertGreaterThan(1.5, $largeResult['profit_factor']);
        $this->assertGreaterThan(0.5, $largeResult['expectancy_r']);
    }

    public function test_all_11_ai_analysts_participate_in_multi_ai_collaboration_session(): void
    {
        $multiAi = app(MultiAiAnalysisService::class);

        // Generate synthetic candles for deterministic evaluation
        $candles = [];
        $base = 2650.0;
        for ($i = 0; $i < 60; $i++) {
            $open = $base + ($i * 0.5);
            $high = $open + 2.0;
            $low = $open - 1.5;
            $close = $open + 0.3;
            $candles[] = [
                'open' => $open,
                'high' => $high,
                'low' => $low,
                'close' => $close,
                'volume' => 1500,
                'timestamp' => now()->subMinutes((60 - $i) * 15)->toIso8601String(),
            ];
        }

        $plan = $multiAi->analyze(
            imagePath: null,
            candles: $candles,
            metadata: ['symbol' => 'XAUUSD', 'timeframe' => 'M15', 'strategy' => 'comprehensive'],
            scanId: null,
            userId: $this->trader->id
        );

        $this->assertIsArray($plan);
        $this->assertArrayHasKey('direction', $plan);
        $this->assertArrayHasKey('market_regime', $plan);
        $this->assertArrayHasKey('retrieved_strategies', $plan);
        $this->assertArrayHasKey('selected_strategy', $plan);

        // Verify collaboration session registered claims across roles
        $session = $plan['collaboration_session'];
        $claims = $session['claims'];
        $rolesWithClaims = array_column($claims, 'role');

        // Check key domain roles
        $this->assertContains('price_action', $rolesWithClaims);
        $this->assertContains('market_structure', $rolesWithClaims);
        $this->assertContains('smc', $rolesWithClaims);
        $this->assertContains('fibonacci_patterns', $rolesWithClaims);
        $this->assertContains('quantitative_analyst', $rolesWithClaims);
        $this->assertContains('market_regime', $rolesWithClaims);
        $this->assertContains('strategy_selector', $rolesWithClaims);
        $this->assertContains('independent_reviewer', $rolesWithClaims);
        $this->assertContains('trading_risk', $rolesWithClaims);

        // Verify selected strategy is structured
        $selectedStrat = $plan['selected_strategy'];
        $this->assertNotEmpty($selectedStrat['name']);
        $this->assertArrayHasKey('school', $selectedStrat);
    }

    public function test_admin_strategy_studio_and_research_lab_management(): void
    {
        // 1. Trader cannot access admin Strategy Studio
        $this->actingAs($this->trader);
        $response = $this->get(route('admin.strategies.index'));
        $response->assertStatus(403);

        // 2. Admin can access index
        $this->actingAs($this->admin);
        $indexResponse = $this->get(route('admin.strategies.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Global Trading Strategy Studio');

        $filteredResponse = $this->get(route('admin.strategies.index', ['category' => 'ict_smc']));
        $filteredResponse->assertStatus(200);
        $filteredResponse->assertSee('SMC Order Block &amp; Fair Value Gap Mitigation', false);

        // 3. Admin can access research lab
        $researchResponse = $this->get(route('admin.strategies.research'));
        $researchResponse->assertStatus(200);
        $researchResponse->assertSee('Strategy Research Lab &amp; Compiler', false);

        // 4. Admin can compile and store a new strategy
        $category = StrategyCategory::firstOrFail();
        $storeResponse = $this->post(route('admin.strategies.store'), [
            'category_id' => $category->id,
            'name' => 'Wyckoff Phase C Spring Accumulation Model',
            'description' => 'Targeting Phase C test of terminal shakeout in trading range with high volume absorption.',
            'market_regimes' => ['RANGING_TIGHT', 'RANGING_EXPANDING'],
            'timeframes' => ['1h', '4h', '1d'],
            'entry_rules_raw' => "Price breaks below Support Trading Range in Phase C\nImmediate reversal back inside trading range with high volume\nSuccessful test of spring on lower volume",
            'stop_loss_rules_raw' => "Stop loss placed 5 pips below lowest low of Spring\nMax risk 1.0% capital",
            'take_profit_rules_raw' => "TP1 at Jump Across the Creek (JAC) resistance\nTP2 at higher timeframe distribution target (1:3 R:R)",
            'invalidation_rules_raw' => "Price closes and sustains below Spring low without reclaiming range",
            'source_author' => 'Richard D. Wyckoff',
            'source_title' => 'The Richard D. Wyckoff Method of Trading',
            'source_url' => 'https://en.wikipedia.org/wiki/Wyckoff_method',
        ]);

        $storeResponse->assertRedirect(route('admin.strategies.index'));
        $this->assertDatabaseHas('strategies', [
            'name' => 'Wyckoff Phase C Spring Accumulation Model',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $newStrategy = Strategy::where('name', 'Wyckoff Phase C Spring Accumulation Model')->firstOrFail();

        // 5. Admin can toggle active status
        $toggleResponse = $this->post(route('admin.strategies.toggle-active', $newStrategy));
        $toggleResponse->assertRedirect();
        $this->assertFalse($newStrategy->fresh()->is_active);

        // 6. Admin can view strategy evaluations
        $evalResponse = $this->get(route('admin.strategies.evaluations', $newStrategy));
        $evalResponse->assertStatus(200);
        $evalResponse->assertSee('Wyckoff Phase C Spring Accumulation Model');
        $evalResponse->assertSee('NOT ENOUGH VALIDATED DATA');
    }
}
