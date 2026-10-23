<?php

namespace App\Services\AI\Collaboration\DTOs;

namespace App\Services\AI\Collaboration;

use App\Services\AI\Collaboration\DTOs\CollaborationChallenge;
use App\Services\AI\Collaboration\DTOs\CollaborationClaim;
use App\Services\AI\Collaboration\DTOs\CollaborationMessageDTO;
use App\Services\AI\Collaboration\DTOs\CollaborationSession;
use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\ProviderRegistry;

class CrossReviewEngine
{
    public function __construct(
        protected ProviderRegistry $providerRegistry
    ) {}

    /**
     * Conduct cross-review across participating models.
     *
     * @param CollaborationSession $session
     * @param array<string, AgentAnalysisResult> $agentResults
     * @return array<CollaborationChallenge>
     */
    public function executeCrossReview(CollaborationSession $session, array $agentResults): array
    {
        $challenges = [];
        $challengeCounter = 1;

        $paResult = $agentResults['price_action'] ?? null;
        $msResult = $agentResults['market_structure'] ?? null;
        $smcResult = $agentResults['smc'] ?? null;
        $fibResult = $agentResults['fibonacci_patterns'] ?? null;
        $quantResult = $agentResults['quantitative_analyst'] ?? null;
        $regimeResult = $agentResults['market_regime'] ?? null;
        $indResult = $agentResults['technical_indicators'] ?? null;
        $mtfResult = $agentResults['multi_timeframe'] ?? null;
        $fnResult = $agentResults['fundamental_news'] ?? null;
        $stratResult = $agentResults['strategy_selector'] ?? null;
        $reviewResult = $agentResults['independent_reviewer'] ?? null;
        $riskResult = $agentResults['trading_risk'] ?? null;

        $context = $session->context;

        // 1. Market Structure reviews Price Action
        if ($paResult && $msResult) {
            $paBias = $paResult->conclusion;
            $msBias = $msResult->conclusion;

            if ($paBias !== 'WAIT' && $msBias !== 'WAIT' && $paBias !== $msBias) {
                $chlId = 'CHL-' . str_pad((string)$challengeCounter++, 2, '0', STR_PAD_LEFT);
                $chl = new CollaborationChallenge(
                    challengeId: $chlId,
                    challengerRole: 'market_structure',
                    targetRole: 'price_action',
                    targetClaimId: 'PA-01',
                    challengeType: 'CONTRADICTION',
                    objectionText: "Price Action asserts a {$paBias} setup, but Market Structure indicates a {$msBias} trend with opposing swing highs/lows.",
                    counterEvidence: $msResult->contradictoryPoints ?: ["Structural swing sequence points {$msBias}"],
                    proposedResolution: "Re-evaluate candlestick breakout against higher timeframe structural invalidation level."
                );
                $session->addChallenge($chl);
                $challenges[] = $chl;

                $session->recordMessage(new CollaborationMessageDTO(
                    sessionId: $session->sessionId,
                    roundNumber: 1,
                    stage: 'cross_review',
                    senderRole: 'market_structure',
                    senderProvider: $msResult->provider,
                    senderModel: $msResult->model,
                    targetRole: 'price_action',
                    messageType: 'CHALLENGE',
                    claim: $chl->objectionText,
                    evidenceReferences: $chl->counterEvidence,
                    relatedClaimIds: ['PA-01'],
                    validationStatus: 'challenged'
                ));
            }
        }

        // 2. Technical Indicators reviews Directional Bias
        if ($indResult && $paResult) {
            $paBias = $paResult->conclusion;
            $rsi = $context->indicators['rsi_14'] ?? null;

            if ($paBias === 'BULLISH' && $rsi && $rsi > 70) {
                $chlId = 'CHL-' . str_pad((string)$challengeCounter++, 2, '0', STR_PAD_LEFT);
                $chl = new CollaborationChallenge(
                    challengeId: $chlId,
                    challengerRole: 'technical_indicators',
                    targetRole: 'price_action',
                    targetClaimId: 'PA-01',
                    challengeType: 'CONTRADICTION',
                    objectionText: "Bullish price action thesis enters into severe RSI overbought condition ({$rsi}), risking imminent exhaustion pullbacks.",
                    counterEvidence: ["RSI 14 = {$rsi} indicates overextended momentum"],
                    proposedResolution: "Wait for RSI momentum reset or minor mean-reversion retest before entry."
                );
                $session->addChallenge($chl);
                $challenges[] = $chl;

                $session->recordMessage(new CollaborationMessageDTO(
                    sessionId: $session->sessionId,
                    roundNumber: 1,
                    stage: 'cross_review',
                    senderRole: 'technical_indicators',
                    senderProvider: $indResult->provider,
                    senderModel: $indResult->model,
                    targetRole: 'price_action',
                    messageType: 'COUNTERARGUMENT',
                    claim: $chl->objectionText,
                    evidenceReferences: $chl->counterEvidence,
                    relatedClaimIds: ['PA-01'],
                    validationStatus: 'challenged'
                ));
            } elseif ($paBias === 'BEARISH' && $rsi && $rsi < 30) {
                $chlId = 'CHL-' . str_pad((string)$challengeCounter++, 2, '0', STR_PAD_LEFT);
                $chl = new CollaborationChallenge(
                    challengeId: $chlId,
                    challengerRole: 'technical_indicators',
                    targetRole: 'price_action',
                    targetClaimId: 'PA-01',
                    challengeType: 'CONTRADICTION',
                    objectionText: "Bearish short thesis initiated into oversold territory ({$rsi}), vulnerable to aggressive short-covering squeeze.",
                    counterEvidence: ["RSI 14 = {$rsi} indicates extreme oversold state"],
                    proposedResolution: "Seek confirmation on pullback into premium supply rather than chasing lows."
                );
                $session->addChallenge($chl);
                $challenges[] = $chl;

                $session->recordMessage(new CollaborationMessageDTO(
                    sessionId: $session->sessionId,
                    roundNumber: 1,
                    stage: 'cross_review',
                    senderRole: 'technical_indicators',
                    senderProvider: $indResult->provider,
                    senderModel: $indResult->model,
                    targetRole: 'price_action',
                    messageType: 'COUNTERARGUMENT',
                    claim: $chl->objectionText,
                    evidenceReferences: $chl->counterEvidence,
                    relatedClaimIds: ['PA-01'],
                    validationStatus: 'challenged'
                ));
            }
        }

        // 3. Smart Money Concepts reviews Liquidity & Order Block Validity
        if ($smcResult && $msResult) {
            $fvgs = $context->smc['fair_value_gaps'] ?? [];
            if (empty($fvgs) && !empty($smcResult->contradictoryPoints)) {
                $chlId = 'CHL-' . str_pad((string)$challengeCounter++, 2, '0', STR_PAD_LEFT);
                $chl = new CollaborationChallenge(
                    challengeId: $chlId,
                    challengerRole: 'smc',
                    targetRole: 'market_structure',
                    targetClaimId: 'MS-01',
                    challengeType: 'MISSING_EVIDENCE',
                    objectionText: "Proposed structure shift lacks displacement and institutional imbalance (no clear Fair Value Gap confirmed on current timeframe).",
                    counterEvidence: ["Zero unmitigated FVGs found in immediate impulse"],
                    proposedResolution: "Verify displacement candle volume before confirming Break of Structure."
                );
                $session->addChallenge($chl);
                $challenges[] = $chl;

                $session->recordMessage(new CollaborationMessageDTO(
                    sessionId: $session->sessionId,
                    roundNumber: 1,
                    stage: 'cross_review',
                    senderRole: 'smc',
                    senderProvider: $smcResult->provider,
                    senderModel: $smcResult->model,
                    targetRole: 'market_structure',
                    messageType: 'CHALLENGE',
                    claim: $chl->objectionText,
                    evidenceReferences: $chl->counterEvidence,
                    relatedClaimIds: ['MS-01'],
                    validationStatus: 'challenged'
                ));
            }
        }

        // 4. Multi-Timeframe reviews HTF Alignment
        if ($mtfResult && $paResult) {
            if ($mtfResult->conclusion === 'BEARISH' && $paResult->conclusion === 'BULLISH') {
                $chlId = 'CHL-' . str_pad((string)$challengeCounter++, 2, '0', STR_PAD_LEFT);
                $chl = new CollaborationChallenge(
                    challengeId: $chlId,
                    challengerRole: 'multi_timeframe',
                    targetRole: 'price_action',
                    targetClaimId: 'PA-01',
                    challengeType: 'CONTRADICTION',
                    objectionText: "Lower timeframe bullish signal conflicts with prevailing higher timeframe macro bearish downtrend.",
                    counterEvidence: ["HTF structure remains decisively bearish"],
                    proposedResolution: "Treat as counter-trend scalp or stand aside for HTF alignment."
                );
                $session->addChallenge($chl);
                $challenges[] = $chl;

                $session->recordMessage(new CollaborationMessageDTO(
                    sessionId: $session->sessionId,
                    roundNumber: 1,
                    stage: 'cross_review',
                    senderRole: 'multi_timeframe',
                    senderProvider: $mtfResult->provider,
                    senderModel: $mtfResult->model,
                    targetRole: 'price_action',
                    messageType: 'CHALLENGE',
                    claim: $chl->objectionText,
                    evidenceReferences: $chl->counterEvidence,
                    relatedClaimIds: ['PA-01'],
                    validationStatus: 'challenged'
                ));
            }
        }

        // 5. Fundamental News Analyst reviews Technical Direction
        if ($fnResult && $paResult) {
            $isRiskShock = ($fnResult->conclusion === 'HIGH_VOLATILITY_RISK');
            $isContradiction = ($fnResult->conclusion !== 'WAIT' && $paResult->conclusion !== 'WAIT' && $fnResult->conclusion !== $paResult->conclusion);

            if ($isRiskShock || $isContradiction) {
                $chlId = 'CHL-' . str_pad((string)$challengeCounter++, 2, '0', STR_PAD_LEFT);
                $objection = $isRiskShock
                    ? "Pending high-impact macroeconomic catalyst creates extreme volatility and slippage risk against proposed technical entry."
                    : "Macro fundamental analysis indicates {$fnResult->conclusion} bias, contradicting the technical {$paResult->conclusion} setup.";

                $chl = new CollaborationChallenge(
                    challengeId: $chlId,
                    challengerRole: 'fundamental_news',
                    targetRole: 'price_action',
                    targetClaimId: 'PA-01',
                    challengeType: $isRiskShock ? 'RISK_VIOLATION' : 'CONTRADICTION',
                    objectionText: $objection,
                    counterEvidence: $fnResult->findings ?: ["High-impact fundamental divergence"],
                    proposedResolution: "Postpone immediate execution until event risk passes or fundamental direction aligns."
                );
                $session->addChallenge($chl);
                $challenges[] = $chl;

                $session->recordMessage(new CollaborationMessageDTO(
                    sessionId: $session->sessionId,
                    roundNumber: 1,
                    stage: 'cross_review',
                    senderRole: 'fundamental_news',
                    senderProvider: $fnResult->provider,
                    senderModel: $fnResult->model,
                    targetRole: 'price_action',
                    messageType: 'CHALLENGE',
                    claim: $chl->objectionText,
                    evidenceReferences: $chl->counterEvidence,
                    relatedClaimIds: ['PA-01', 'FN-01'],
                    validationStatus: 'challenged'
                ));
            }
        }

        // 6. Risk Analyst challenges Trade Geometry
        if ($riskResult) {
            $atr = $context->indicators['atr_14'] ?? null;
            $price = $context->getLatestPrice();

            if ($riskResult->conclusion === 'REJECT') {
                $chlId = 'CHL-' . str_pad((string)$challengeCounter++, 2, '0', STR_PAD_LEFT);
                $chl = new CollaborationChallenge(
                    challengeId: $chlId,
                    challengerRole: 'trading_risk',
                    targetRole: 'all',
                    targetClaimId: 'ALL-01',
                    challengeType: 'RISK_VIOLATION',
                    objectionText: "Risk review failed: Market uncertainty or extreme volatility parameters exceed capital protection rules.",
                    counterEvidence: $riskResult->contradictoryPoints ?: ["Risk thresholds breached"],
                    proposedResolution: "Enforce standing aside until volatility normalizes."
                );
                $session->addChallenge($chl);
                $challenges[] = $chl;

                $session->recordMessage(new CollaborationMessageDTO(
                    sessionId: $session->sessionId,
                    roundNumber: 1,
                    stage: 'cross_review',
                    senderRole: 'trading_risk',
                    senderProvider: $riskResult->provider,
                    senderModel: $riskResult->model,
                    targetRole: 'all',
                    messageType: 'RISK_OBJECTION',
                    claim: $chl->objectionText,
                    evidenceReferences: $chl->counterEvidence,
                    relatedClaimIds: ['ALL-01'],
                    validationStatus: 'vetoed'
                ));
            }
        }

        // 7. Market Regime Analyst challenges Strategy / Directional Bias
        if ($regimeResult && $paResult) {
            $regimeData = $context->marketRegime ?? [];
            $regime = $regimeData['regime'] ?? 'UNCERTAIN';
            $isRanging = in_array($regime, ['RANGING_TIGHT', 'RANGING_EXPANDING'], true);
            $isTrending = in_array($regime, ['TRENDING_BULLISH', 'TRENDING_BEARISH'], true);

            if ($isRanging && in_array($paResult->conclusion, ['BULLISH', 'BEARISH'], true) && ($regimeResult->conclusion === 'WAIT' || $regimeResult->confidence === 'low')) {
                $chlId = 'CHL-' . str_pad((string)$challengeCounter++, 2, '0', STR_PAD_LEFT);
                $chl = new CollaborationChallenge(
                    challengeId: $chlId,
                    challengerRole: 'market_regime',
                    targetRole: 'price_action',
                    targetClaimId: 'PA-01',
                    challengeType: 'REGIME_MISMATCH',
                    objectionText: "Directional thesis proposed during {$regime} compression. ADX indicates insufficient trend momentum, risking false breakout traps.",
                    counterEvidence: ["Market Regime = {$regime}", "ADX < 20 indicates non-trending consolidation"],
                    proposedResolution: "Switch to range-bound mean-reversion tactics or wait for confirmed expansion."
                );
                $session->addChallenge($chl);
                $challenges[] = $chl;
            }
        }

        // 8. Independent Reviewer (Red Team) challenges over-confidence or liquidity traps
        if ($reviewResult && $paResult && $paResult->conclusion !== 'WAIT') {
            $redAudit = $reviewResult->rawAnalysis['red_team_audit'] ?? [];
            $fakeoutRisk = $redAudit['fakeout_probability'] ?? 'moderate';
            $recommendation = $redAudit['recommendation'] ?? 'PROCEED_WITH_CAUTION';

            if ($fakeoutRisk === 'high' || $recommendation === 'ABSTAIN_WAIT' || $reviewResult->conclusion === 'NO VALID SETUP') {
                $chlId = 'CHL-' . str_pad((string)$challengeCounter++, 2, '0', STR_PAD_LEFT);
                $chl = new CollaborationChallenge(
                    challengeId: $chlId,
                    challengerRole: 'independent_reviewer',
                    targetRole: 'price_action',
                    targetClaimId: 'PA-01',
                    challengeType: 'RED_TEAM_OBJECTION',
                    objectionText: "Red Team Challenge: High fakeout or stop hunt probability detected. " . ($reviewResult->contradictoryPoints[0] ?? "Setup vulnerable to liquidity sweep."),
                    counterEvidence: $reviewResult->contradictoryPoints ?: ["Liquidity sweep trap imminent"],
                    proposedResolution: "Abstain (WAIT) or widen invalidation buffer beyond equal highs/lows."
                );
                $session->addChallenge($chl);
                $challenges[] = $chl;
            }
        }

        // 9. Fibonacci Analyst reviews Retracement Validity
        if ($fibResult && $paResult && $paResult->conclusion !== 'WAIT') {
            if ($fibResult->conclusion === 'WAIT' || $fibResult->conclusion === 'NO VALID SETUP') {
                $fibData = $context->fibonacci ?? [];
                if (empty($fibData['golden_zone']['tested'])) {
                    $chlId = 'CHL-' . str_pad((string)$challengeCounter++, 2, '0', STR_PAD_LEFT);
                    $chl = new CollaborationChallenge(
                        challengeId: $chlId,
                        challengerRole: 'fibonacci_patterns',
                        targetRole: 'price_action',
                        targetClaimId: 'PA-01',
                        challengeType: 'MISSING_EVIDENCE',
                        objectionText: "Price has not retraced into the mathematical Golden Zone (61.8% / 78.6%) for high-probability entry confluence.",
                        counterEvidence: ["Golden pocket untested; entering mid-range without discount/premium advantage"],
                        proposedResolution: "Patience until retracement re-tests verified Fibonacci ratio."
                    );
                    $session->addChallenge($chl);
                    $challenges[] = $chl;
                }
            }
        }

        return $challenges;
    }
}
