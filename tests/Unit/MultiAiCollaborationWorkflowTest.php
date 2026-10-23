<?php

namespace Tests\Unit;

use App\Models\AnalysisCollaborationMessage;
use App\Models\AnalysisRun;
use App\Models\Scan;
use App\Models\User;
use App\Services\AI\Collaboration\CrossReviewEngine;
use App\Services\AI\Collaboration\DebateCoordinator;
use App\Services\AI\Collaboration\DTOs\CollaborationChallenge;
use App\Services\AI\Collaboration\DTOs\CollaborationClaim;
use App\Services\AI\Collaboration\DTOs\CollaborationMessageDTO;
use App\Services\AI\Collaboration\DTOs\CollaborationSession;
use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\DTOs\AnalysisContext;
use App\Services\AI\MultiAiAnalysisService;
use App\Services\AI\ProviderRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiAiCollaborationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSampleCandles(): array
    {
        $candles = [];
        $basePrice = 2650.0;
        $timestamp = strtotime('2026-10-23 00:00:00');

        for ($i = 0; $i < 50; $i++) {
            $open = $basePrice + ($i * 0.5);
            $high = $open + 2.5;
            $low = $open - 1.5;
            $close = $open + 1.2;
            $candles[] = [
                'timestamp' => date('Y-m-d H:i:s', $timestamp + ($i * 3600)),
                'open' => round($open, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($close, 2),
                'volume' => 1200 + ($i * 15),
            ];
            $basePrice = $close;
        }

        return $candles;
    }

    public function test_collaboration_session_manages_claims_and_protocol_messages(): void
    {
        $context = new AnalysisContext(symbol: 'XAUUSD', timeframe: 'H1');
        $session = new CollaborationSession('test-session-123', $context);

        $claimPA = new CollaborationClaim(
            claimId: 'PA-01',
            role: 'price_action',
            claimText: 'Bullish breakout above resistance',
            bias: 'BULLISH',
            confidence: 'high'
        );
        $claimMS = new CollaborationClaim(
            claimId: 'MS-01',
            role: 'market_structure',
            claimText: 'Bearish structure with lower-high sequence',
            bias: 'BEARISH',
            confidence: 'high'
        );

        $session->addClaim($claimPA);
        $session->addClaim($claimMS);

        $this->assertCount(2, $session->getAllClaims());
        $this->assertEquals('PA-01', $session->getClaim('PA-01')->claimId);
        $this->assertTrue($session->hasUnresolvedConflicts());

        $session->recordMessage(new CollaborationMessageDTO(
            sessionId: 'test-session-123',
            roundNumber: 1,
            stage: 'initial_analysis',
            senderRole: 'price_action',
            senderProvider: 'deterministic',
            senderModel: 'rule-v2',
            targetRole: 'all',
            messageType: 'INITIAL_ANALYSIS',
            claim: 'Bullish breakout',
            relatedClaimIds: ['PA-01']
        ));

        $this->assertCount(1, $session->getMessages());
    }

    public function test_cross_review_engine_detects_contradiction_between_price_action_and_market_structure(): void
    {
        $context = new AnalysisContext(
            symbol: 'XAUUSD',
            timeframe: 'H1',
            candles: $this->makeSampleCandles()
        );
        $session = new CollaborationSession('session-test-cross', $context);

        $registry = app(ProviderRegistry::class);
        $engine = new CrossReviewEngine($registry);

        $agentResults = [
            'price_action' => new AgentAnalysisResult(
                role: 'price_action',
                provider: 'deterministic',
                model: 'rule',
                status: 'success',
                conclusion: 'BULLISH',
                confidence: 'high',
                summary: 'Bullish pin bar retest'
            ),
            'market_structure' => new AgentAnalysisResult(
                role: 'market_structure',
                provider: 'deterministic',
                model: 'rule',
                status: 'success',
                conclusion: 'BEARISH',
                confidence: 'high',
                summary: 'Bearish lower-high structure'
            ),
        ];

        $challenges = $engine->executeCrossReview($session, $agentResults);

        $this->assertNotEmpty($challenges);
        $chl = $challenges[0];
        $this->assertEquals('market_structure', $chl->challengerRole);
        $this->assertEquals('price_action', $chl->targetRole);
        $this->assertEquals('CONTRADICTION', $chl->challengeType);
        $this->assertStringContainsString('BULLISH', $chl->objectionText);
    }

    public function test_cross_review_engine_detects_rsi_overbought_divergence_challenge(): void
    {
        $context = new AnalysisContext(
            symbol: 'EURUSD',
            timeframe: 'H1',
            indicators: ['rsi_14' => 74.8] // Overbought
        );
        $session = new CollaborationSession('session-rsi-test', $context);

        $registry = app(ProviderRegistry::class);
        $engine = new CrossReviewEngine($registry);

        $agentResults = [
            'price_action' => new AgentAnalysisResult(
                role: 'price_action',
                provider: 'gemini',
                model: 'flash',
                status: 'success',
                conclusion: 'BULLISH',
                confidence: 'high',
                summary: 'Bullish expansion impulse'
            ),
            'technical_indicators' => new AgentAnalysisResult(
                role: 'technical_indicators',
                provider: 'deepseek',
                model: 'chat',
                status: 'success',
                conclusion: 'WAIT',
                confidence: 'moderate',
                summary: 'Momentum indicator check'
            ),
        ];

        $challenges = $engine->executeCrossReview($session, $agentResults);

        $this->assertNotEmpty($challenges);
        $rsiChallenge = collect($challenges)->firstWhere('challengerRole', 'technical_indicators');
        $this->assertNotNull($rsiChallenge);
        $this->assertEquals('price_action', $rsiChallenge->targetRole);
        $this->assertStringContainsString('overbought', $rsiChallenge->objectionText);
    }

    public function test_debate_coordinator_causes_model_to_adjust_to_wait_on_severe_challenge(): void
    {
        $context = new AnalysisContext(
            symbol: 'XAUUSD',
            timeframe: 'H1',
            indicators: ['rsi_14' => 75.0]
        );
        $session = new CollaborationSession('session-debate-test', $context);

        $claim = new CollaborationClaim(
            claimId: 'PA-01',
            role: 'price_action',
            claimText: 'Bullish expansion impulse',
            bias: 'BULLISH',
            confidence: 'high'
        );
        $session->addClaim($claim);

        $challenge = new CollaborationChallenge(
            challengeId: 'CHL-01',
            challengerRole: 'technical_indicators',
            targetRole: 'price_action',
            targetClaimId: 'PA-01',
            challengeType: 'CONTRADICTION',
            objectionText: 'Bullish price action thesis enters into severe RSI overbought condition (75.0).'
        );

        $agentResults = [
            'price_action' => new AgentAnalysisResult(
                role: 'price_action',
                provider: 'deterministic',
                model: 'rule-engine-v2',
                status: 'success',
                conclusion: 'BULLISH',
                confidence: 'high',
                summary: 'Bullish expansion'
            ),
        ];

        $registry = app(ProviderRegistry::class);
        $coordinator = new DebateCoordinator($registry);

        $reassessments = $coordinator->coordinateDebate($session, [$challenge], $agentResults, 1);

        $this->assertArrayHasKey('price_action', $reassessments);
        $paReassess = $reassessments['price_action'];
        $this->assertEquals('ADJUST', $paReassess['action']);
        $this->assertEquals('WAIT', $paReassess['updated_conclusion']);
        $this->assertEquals('conceded', $challenge->status);
        $this->assertEquals('conceded', $claim->status);

        // Active in-memory conclusion should be updated to WAIT
        $this->assertEquals('WAIT', $agentResults['price_action']->conclusion);
    }

    public function test_full_collaboration_pipeline_persists_messages_to_postgresql(): void
    {
        $user = User::factory()->create([
            'role' => 'trader',
            'account_status' => 'active',
        ]);

        $scan = Scan::create([
            'user_id' => $user->id,
            'symbol' => 'XAUUSD',
            'timeframe' => 'H1',
            'input_type' => 'csv',
            'data_source' => 'user_csv',
            'strategy' => 'comprehensive',
            'status' => 'processing',
        ]);

        $candles = $this->makeSampleCandles();
        $orchestrator = app(MultiAiAnalysisService::class);

        $plan = $orchestrator->analyze(
            imagePath: null,
            candles: $candles,
            metadata: ['symbol' => 'XAUUSD', 'timeframe' => 'H1', 'strategy' => 'comprehensive'],
            scanId: $scan->id,
            userId: $user->id
        );

        $this->assertNotNull($plan);
        $this->assertArrayHasKey('collaboration_session', $plan);
        $this->assertNotEmpty($plan['collaboration_session']['claims']);

        // Verify AnalysisRun and AnalysisCollaborationMessage records in DB
        $run = AnalysisRun::where('scan_id', $scan->id)->first();
        $this->assertNotNull($run);

        $messages = AnalysisCollaborationMessage::where('analysis_run_id', $run->id)->get();
        $this->assertNotEmpty($messages);

        // Must contain INITIAL_ANALYSIS and FINAL_SYNTHESIS stages
        $stages = $messages->pluck('stage')->unique()->all();
        $this->assertContains('initial_analysis', $stages);
        $this->assertContains('final_synthesis', $stages);

        $types = $messages->pluck('message_type')->unique()->all();
        $this->assertContains('INITIAL_ANALYSIS', $types);
        $this->assertContains('FINAL_SYNTHESIS', $types);

        // Check relationship on model
        $this->assertEquals($messages->count(), $run->collaborationMessages()->count());
    }
}
