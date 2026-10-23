<?php

namespace Tests\Unit;

use App\Services\AI\Adapters\ClaudeProviderAdapter;
use App\Services\AI\Adapters\DeterministicRuleAdapter;
use App\Services\AI\Adapters\GeminiProviderAdapter;
use App\Services\AI\Consensus\ConsensusEngine;
use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\DTOs\AnalysisContext;
use App\Services\TechnicalAnalysis\TechnicalAnalysisService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MultiAiConsensusAndAdaptersTest extends TestCase
{
    protected function makeSampleCandles(): array
    {
        $candles = [];
        $basePrice = 2000.0;
        $timestamp = strtotime('2026-10-23 00:00:00');

        for ($i = 0; $i < 50; $i++) {
            $open = $basePrice + ($i * 0.5);
            $high = $open + 2.0;
            $low = $open - 1.5;
            $close = $open + 1.0;
            $candles[] = [
                'timestamp' => date('Y-m-d H:i:s', $timestamp + ($i * 3600)),
                'open' => round($open, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($close, 2),
                'volume' => 1000 + ($i * 10),
            ];
            $basePrice = $close;
        }

        return $candles;
    }

    public function test_deterministic_rule_adapter_executes_all_roles_offline(): void
    {
        $adapter = new DeterministicRuleAdapter();
        $candles = $this->makeSampleCandles();

        $context = new AnalysisContext(
            symbol: 'XAUUSD',
            timeframe: 'H1',
            strategy: 'comprehensive',
            candles: $candles,
            indicators: ['rsi_14' => 62.5, 'ema_50' => 2010.0, 'ema_200' => 1990.0, 'atr_14' => 4.5],
            marketStructure: ['trend' => 'bullish', 'structural_invalidation_level' => 1995.0],
            supportResistance: ['supports' => [2015.0, 2005.0], 'resistances' => [2040.0, 2055.0]],
            smc: ['active_bias' => 'bullish_expansion'],
            fibonacci: ['golden_pocket_min' => 2012.0, 'golden_pocket_max' => 2015.0],
            dataSource: 'numerical_ohlcv_dataset'
        );

        $roles = [
            'price_action',
            'market_structure',
            'smc',
            'technical_indicators',
            'multi_timeframe',
            'trading_risk',
            'final_decision',
        ];

        foreach ($roles as $role) {
            $result = $adapter->analyze($context, $role);
            $this->assertInstanceOf(AgentAnalysisResult::class, $result);
            $this->assertTrue($result->isSuccessful());
            $this->assertEquals($role, $result->role);
            $this->assertEquals('deterministic', $result->provider);
            $this->assertNotEmpty($result->summary);
        }
    }

    public function test_gemini_adapter_handles_successful_api_response(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'conclusion' => 'BULLISH',
                                        'confidence' => 'high',
                                        'summary' => 'Strong bullish order block rejection at support.',
                                        'findings' => ['Clear rejection candle', 'Higher high sequence'],
                                        'supporting_points' => ['Wick rejection at key demand', 'RSI above 50'],
                                        'contradictory_points' => ['Minor overhead resistance at 2035'],
                                        'proposed_levels' => [
                                            'entry' => 2020.0,
                                            'stop_loss' => 2010.0,
                                            'take_profit_1' => 2035.0,
                                        ],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $adapter = new GeminiProviderAdapter(
            apiKey: 'test-api-key',
            modelId: 'gemini-1.5-flash',
            timeoutSeconds: 10
        );

        $context = new AnalysisContext(
            symbol: 'XAUUSD',
            timeframe: 'H1',
            strategy: 'smc',
            candles: $this->makeSampleCandles()
        );

        $result = $adapter->analyze($context, 'price_action');
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('BULLISH', $result->conclusion);
        $this->assertEquals('high', $result->confidence);
        $this->assertCount(2, $result->supportingPoints);
    }

    public function test_gemini_adapter_handles_timeout_gracefully(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(null, 504),
        ]);

        $adapter = new GeminiProviderAdapter(
            apiKey: 'test-api-key',
            modelId: 'gemini-1.5-flash',
            timeoutSeconds: 5
        );

        $context = new AnalysisContext(symbol: 'EURUSD', timeframe: 'M15');
        $result = $adapter->analyze($context, 'price_action');

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals('failed', $result->status);
        $this->assertEquals('WAIT', $result->conclusion);
        $this->assertNotEmpty($result->errorMessage);
    }

    public function test_consensus_engine_detects_conflict_and_forces_wait(): void
    {
        $engine = new ConsensusEngine();
        $context = new AnalysisContext(
            symbol: 'XAUUSD',
            timeframe: 'H1',
            candles: $this->makeSampleCandles()
        );

        // 2 Bullish vs 2 Bearish = Conflict!
        $agentResults = [
            'price_action' => new AgentAnalysisResult(
                role: 'price_action',
                provider: 'gemini',
                model: 'flash',
                status: 'success',
                conclusion: 'BULLISH',
                confidence: 'high',
                summary: 'Bullish action',
                supportingPoints: ['Bullish pin bar']
            ),
            'market_structure' => new AgentAnalysisResult(
                role: 'market_structure',
                provider: 'claude',
                model: 'sonnet',
                status: 'success',
                conclusion: 'BULLISH',
                confidence: 'high',
                summary: 'Bullish structure',
                supportingPoints: ['Higher high formed']
            ),
            'smc' => new AgentAnalysisResult(
                role: 'smc',
                provider: 'openai',
                model: 'gpt-4o',
                status: 'success',
                conclusion: 'BEARISH',
                confidence: 'high',
                summary: 'Liquidity sweep reversal',
                contradictoryPoints: ['Swept buy-side liquidity']
            ),
            'technical_indicators' => new AgentAnalysisResult(
                role: 'technical_indicators',
                provider: 'deepseek',
                model: 'chat',
                status: 'success',
                conclusion: 'BEARISH',
                confidence: 'high',
                summary: 'Bearish divergence',
                contradictoryPoints: ['RSI bearish divergence']
            ),
            'trading_risk' => new AgentAnalysisResult(
                role: 'trading_risk',
                provider: 'deterministic',
                model: 'rule',
                status: 'success',
                conclusion: 'APPROVE',
                confidence: 'moderate',
                summary: 'Risk acceptable',
                riskReview: ['status' => 'approved']
            ),
        ];

        $report = $engine->evaluate($context, $agentResults);

        $this->assertEquals('WAIT', $report['decision']);
        $this->assertEquals('low', $report['confidence']);
        $this->assertStringContainsString('disagreement', implode(' ', $report['warnings']));
    }

    public function test_consensus_engine_respects_trading_risk_veto(): void
    {
        $engine = new ConsensusEngine();
        $context = new AnalysisContext(
            symbol: 'GBPUSD',
            timeframe: 'H4',
            candles: $this->makeSampleCandles()
        );

        // All models say BULLISH, but Risk Analyst exercises VETO (REJECT)
        $agentResults = [
            'price_action' => new AgentAnalysisResult(
                role: 'price_action',
                provider: 'gemini',
                model: 'flash',
                status: 'success',
                conclusion: 'BULLISH',
                confidence: 'high',
                summary: 'Bullish pin bar',
                supportingPoints: ['Bullish']
            ),
            'market_structure' => new AgentAnalysisResult(
                role: 'market_structure',
                provider: 'claude',
                model: 'sonnet',
                status: 'success',
                conclusion: 'BULLISH',
                confidence: 'high',
                summary: 'Higher highs',
                supportingPoints: ['Uptrend']
            ),
            'trading_risk' => new AgentAnalysisResult(
                role: 'trading_risk',
                provider: 'deterministic',
                model: 'rule',
                status: 'success',
                conclusion: 'REJECT',
                confidence: 'high',
                summary: 'Volatility excessive ahead of NFP release',
                contradictoryPoints: ['NFP upcoming'],
                riskReview: [
                    'status' => 'rejected',
                    'reason' => 'High impact economic risk',
                ]
            ),
        ];

        $report = $engine->evaluate($context, $agentResults);

        $this->assertEquals('WAIT', $report['decision']);
        $this->assertStringContainsString('VETO', implode(' ', $report['warnings']));
        $this->assertEquals('rejected', $report['risk_review']['status']);
    }

    public function test_technical_analysis_engine_calculates_bollinger_bands_and_volume(): void
    {
        $ta = new TechnicalAnalysisService();
        $candles = $this->makeSampleCandles();

        $result = $ta->analyze($candles, 'comprehensive');

        $this->assertArrayHasKey('indicators', $result);
        $this->assertArrayHasKey('parameters', $result);
        $this->assertArrayHasKey('bollinger', $result['indicators']);
        $this->assertArrayHasKey('volume', $result['indicators']);

        $bb = $result['indicators']['bollinger'];
        $this->assertNotNull($bb);
        $this->assertArrayHasKey('upper', $bb);
        $this->assertArrayHasKey('middle', $bb);
        $this->assertArrayHasKey('lower', $bb);

        // Upper band must be greater than or equal to middle band, middle >= lower
        $this->assertGreaterThanOrEqual($bb['middle'], $bb['upper']);
        $this->assertGreaterThanOrEqual($bb['lower'], $bb['middle']);

        // Check parameters recorded
        $this->assertEquals(20, $result['parameters']['bollinger']['period']);
        $this->assertEquals(2.0, $result['parameters']['bollinger']['std_dev']);
    }
}
