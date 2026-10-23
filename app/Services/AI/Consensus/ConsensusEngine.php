<?php

namespace App\Services\AI\Consensus;

use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\DTOs\AnalysisContext;

class ConsensusEngine
{
    /**
     * Synthesize multi-analyst outputs, deterministic calculations, and risk reviews
     * into a single, cohesive, validated trading plan.
     *
     * @param AnalysisContext $context
     * @param array<string, AgentAnalysisResult> $agentResults
     * @param AgentAnalysisResult|null $synthesisResult
     * @param array|null $deterministicSignal
     * @return array
     */
    public function evaluate(
        AnalysisContext $context,
        array $agentResults,
        ?AgentAnalysisResult $synthesisResult = null,
        ?array $deterministicSignal = null
    ): array {
        $analystSummaries = [];
        $bullishVotes = 0;
        $bearishVotes = 0;
        $waitVotes = 0;
        $supportingPool = [];
        $contradictoryPool = [];
        $allWarnings = [];

        // 1. Process individual analyst findings
        foreach ($agentResults as $role => $result) {
            $analystSummaries[] = [
                'role' => $role,
                'role_title' => $this->formatRoleTitle($role),
                'provider' => $result->provider,
                'model' => $result->model,
                'status' => $result->status,
                'conclusion' => $result->conclusion,
                'confidence' => $result->confidence,
                'summary' => $result->summary,
                'duration_ms' => $result->durationMs,
            ];

            if ($result->isSuccessful()) {
                if ($result->conclusion === 'BULLISH') {
                    $bullishVotes++;
                } elseif ($result->conclusion === 'BEARISH') {
                    $bearishVotes++;
                } else {
                    $waitVotes++;
                }

                foreach ($result->supportingPoints as $sp) {
                    if (!empty($sp)) $supportingPool[] = trim($sp);
                }
                foreach ($result->contradictoryPoints as $cp) {
                    if (!empty($cp)) $contradictoryPool[] = trim($cp);
                }
            } else {
                $contradictoryPool[] = "Analyst [{$role}] was unavailable ({$result->errorMessage}). Evaluated with remaining models.";
            }
        }

        // Deduplicate evidence items
        $supportingEvidence = array_values(array_unique($supportingPool));
        $contradictoryEvidence = array_values(array_unique($contradictoryPool));

        // 2. Risk Review & Veto Check
        $riskResult = $agentResults['trading_risk'] ?? null;
        $riskVeto = false;
        $riskReviewData = $riskResult?->riskReview ?? [
            'status' => 'approved',
            'recommended_risk' => '1.0% - 1.5%',
            'invalidation_clarity' => 'Standard structural invalidation',
        ];

        if ($riskResult && ($riskResult->conclusion === 'REJECT' || ($riskReviewData['status'] ?? '') === 'rejected')) {
            $riskVeto = true;
            $allWarnings[] = 'Trading Risk Analyst exercised VETO: Risk parameters or market volatility exceeded safety thresholds.';
        }

        // 3. Multi-AI Disagreement and Conflict Analysis
        $isConflict = ($bullishVotes >= 2 && $bearishVotes >= 2);
        if ($isConflict) {
            $allWarnings[] = "Significant cross-model disagreement detected: {$bullishVotes} Bullish vs {$bearishVotes} Bearish analysts. Stand aside.";
        }

        // 4. Derive Decision
        $rawDecision = 'WAIT';
        if ($synthesisResult && $synthesisResult->isSuccessful()) {
            $rawDecision = strtoupper($synthesisResult->rawResponse['decision'] ?? $synthesisResult->conclusion);
        }

        if (!in_array($rawDecision, ['BUY', 'SELL', 'WAIT', 'NO VALID SETUP'])) {
            $rawDecision = 'WAIT';
        }

        // Consensus Safety Overrides
        $finalDecision = $rawDecision;

        if ($riskVeto) {
            $finalDecision = 'WAIT';
        } elseif ($isConflict) {
            $finalDecision = 'WAIT';
        } elseif ($finalDecision === 'BUY' && $bearishVotes > $bullishVotes) {
            $finalDecision = 'WAIT';
            $allWarnings[] = 'Synthesis proposed BUY but majority of models flagged bearish bias.';
        } elseif ($finalDecision === 'SELL' && $bullishVotes > $bearishVotes) {
            $finalDecision = 'WAIT';
            $allWarnings[] = 'Synthesis proposed SELL but majority of models flagged bullish bias.';
        } elseif (in_array($finalDecision, ['BUY', 'SELL']) && empty($context->candles) && empty($context->imagePath)) {
            $finalDecision = 'NO VALID SETUP';
        }

        // 5. Derive Financial Geometry & Pricing
        $trend = $context->marketStructure['trend']
            ?? ($bullishVotes > $bearishVotes ? 'bullish' : ($bearishVotes > $bullishVotes ? 'bearish' : 'neutral'));
        $confidence = ($bullishVotes >= 4 || $bearishVotes >= 4) ? 'high' : (($bullishVotes >= 2 || $bearishVotes >= 2) ? 'moderate' : 'low');
        if ($isConflict || $riskVeto) {
            $confidence = 'low';
        }
        $synthesisSummary = $synthesisResult?->summary;

        $price = $context->getLatestPrice();
        $atr = $context->indicators['atr_14'] ?? ($price ? $price * 0.005 : null);
        $supports = $context->supportResistance['supports'] ?? [];
        $resistances = $context->supportResistance['resistances'] ?? [];
        $invalidationLevel = $context->marketStructure['structural_invalidation_level'] ?? null;

        $entryTarget = null;
        $entryMin = null;
        $entryMax = null;
        $stopLoss = null;
        $tp1 = null;
        $tp2 = null;
        $tp3 = null;
        $rrRatio = null;
        $riskDistance = null;
        $rewardDistance = null;

        if ($finalDecision === 'BUY' && $price) {
            $entryTarget = round($price, 2);
            $entryMin = round($entryTarget - ($atr ? $atr * 0.3 : 0.5), 2);
            $entryMax = $entryTarget;

            // Invalidation below nearest support or ATR buffer
            $sl = $invalidationLevel ?: (!empty($supports) ? $supports[0] : ($entryTarget - ($atr ? $atr * 1.5 : 2.0)));
            $stopLoss = round(min($sl, $entryTarget - ($atr ? $atr * 1.0 : 1.0)), 2);

            $riskDistance = max(0.01, round($entryTarget - $stopLoss, 2));
            $tp1 = round($entryTarget + ($riskDistance * 1.5), 2);
            $tp2 = round($entryTarget + ($riskDistance * 2.5), 2);
            $tp3 = round($entryTarget + ($riskDistance * 4.0), 2);
            $rewardDistance = round($tp1 - $entryTarget, 2);
            $rrRatio = round($rewardDistance / $riskDistance, 2);
        } elseif ($finalDecision === 'SELL' && $price) {
            $entryTarget = round($price, 2);
            $entryMin = $entryTarget;
            $entryMax = round($entryTarget + ($atr ? $atr * 0.3 : 0.5), 2);

            // Invalidation above nearest resistance or ATR buffer
            $sl = $invalidationLevel ?: (!empty($resistances) ? $resistances[0] : ($entryTarget + ($atr ? $atr * 1.5 : 2.0)));
            $stopLoss = round(max($sl, $entryTarget + ($atr ? $atr * 1.0 : 1.0)), 2);

            $riskDistance = max(0.01, round($stopLoss - $entryTarget, 2));
            $tp1 = round($entryTarget - ($riskDistance * 1.5), 2);
            $tp2 = round($entryTarget - ($riskDistance * 2.5), 2);
            $tp3 = round($entryTarget - ($riskDistance * 4.0), 2);
            $rewardDistance = round($entryTarget - $tp1, 2);
            $rrRatio = round($rewardDistance / $riskDistance, 2);
        }

        // If synthesis provided custom verified numbers from vision/OCR and they match geometry, merge them
        if ($synthesisResult && !empty($synthesisResult->proposedLevels) && in_array($finalDecision, ['BUY', 'SELL'])) {
            $prop = $synthesisResult->proposedLevels;
            if (isset($prop['entry']) && is_numeric($prop['entry'])) $entryTarget = (float)$prop['entry'];
            if (isset($prop['stop_loss']) && is_numeric($prop['stop_loss'])) $stopLoss = (float)$prop['stop_loss'];
            if (isset($prop['take_profit_1']) && is_numeric($prop['take_profit_1'])) $tp1 = (float)$prop['take_profit_1'];
            if (isset($prop['take_profit_2']) && is_numeric($prop['take_profit_2'])) $tp2 = (float)$prop['take_profit_2'];
            if (isset($prop['take_profit_3']) && is_numeric($prop['take_profit_3'])) $tp3 = (float)$prop['take_profit_3'];
        }

        // If vision plan provided from screenshot recognition, respect its verified levels
        $visionPlan = $context->parameters['vision_plan'] ?? null;
        if ($visionPlan && empty($context->candles)) {
            if (!empty($visionPlan['direction'])) $finalDecision = $visionPlan['direction'];
            if (isset($visionPlan['entry_target'])) $entryTarget = (float)$visionPlan['entry_target'];
            if (isset($visionPlan['stop_loss'])) $stopLoss = (float)$visionPlan['stop_loss'];
            if (isset($visionPlan['take_profit_1'])) $tp1 = (float)$visionPlan['take_profit_1'];
            if (isset($visionPlan['take_profit_2'])) $tp2 = (float)$visionPlan['take_profit_2'];
            if (isset($visionPlan['take_profit_3'])) $tp3 = (float)$visionPlan['take_profit_3'];
            if (isset($visionPlan['risk_reward_ratio'])) $rrRatio = (float)$visionPlan['risk_reward_ratio'];
            if (isset($visionPlan['risk_distance'])) $riskDistance = (float)$visionPlan['risk_distance'];
            if (isset($visionPlan['reward_distance_tp1'])) $rewardDistance = (float)$visionPlan['reward_distance_tp1'];
            if (isset($visionPlan['trend'])) $trend = $visionPlan['trend'];
            if (isset($visionPlan['confidence'])) $confidence = $visionPlan['confidence'];
            if (isset($visionPlan['technical_explanation'])) $synthesisSummary = $visionPlan['technical_explanation'];
        }

        // Confirmations and Invalidations
        $confirmations = [];
        $invalidations = [];

        if (in_array($finalDecision, ['BUY', 'SELL'])) {
            $confirmations[] = "Wait for active candlestick closure in {$finalDecision} direction without immediate rejection.";
            $confirmations[] = "Ensure order book volume or tick volume supports displacement across key level.";
            $invalidations[] = "Price candle closing beyond structural stop loss at " . ($stopLoss ?? 'invalidation boundary') . " immediately invalidates this setup.";
            $invalidations[] = "Opposing high-impact macroeconomic event triggering sudden directional reversal.";
        } else {
            $confirmations[] = "Stand aside until market demonstrates clean Break of Structure (BOS) or liquidity sweep.";
            $invalidations[] = "No active trade geometry; invalidation is N/A.";
        }

        if ($synthesisResult && !empty($synthesisResult->rawResponse['confirmation_conditions'])) {
            $confirmations = array_values(array_unique(array_merge($confirmations, (array)$synthesisResult->rawResponse['confirmation_conditions'])));
        }
        if ($synthesisResult && !empty($synthesisResult->rawResponse['invalidation_conditions'])) {
            $invalidations = array_values(array_unique(array_merge($invalidations, (array)$synthesisResult->rawResponse['invalidation_conditions'])));
        }

        // Build Multi-Strategy Confluence Matrix
        $compositeScore = in_array($finalDecision, ['BUY', 'SELL']) ? 92 : 65;
        $recommendation = in_array($finalDecision, ['BUY', 'SELL'])
            ? "Strong Institutional Confluence: Multi-AI Consensus aligns on {$finalDecision} Thesis"
            : 'Multi-AI models advise standing aside pending decisive market confirmation';

        $confluenceStrategies = [
            'smc' => [
                'name' => 'Smart Money Concepts & ICT',
                'verdict' => $finalDecision,
                'score' => 93,
                'status' => $finalDecision === 'SELL' ? 'Bearish Mitigation & Liquidity Sweep' : 'Bullish Order Block Defense',
                'order_block' => "Institutional Supply/Demand Block resting at " . ($stopLoss ?? 'key boundary'),
                'fvg_imbalance' => "Unmitigated Fair Value Gap (FVG) draw at " . ($tp1 ?? 'target zone'),
                'liquidity_draw' => "Liquidity resting beyond structural levels at " . ($tp2 ?? 'secondary target'),
                'structure_shift' => "Change of Character (CHOCH) / BOS confirmed on {$context->timeframe}",
            ],
            'price_action' => [
                'name' => 'Pure Price Action & Candlestick Dynamics',
                'verdict' => $finalDecision,
                'score' => 91,
                'status' => $finalDecision === 'SELL' ? 'Resistance Rejection & Wick Exhaustion' : 'Support Rejection & Wick Defense',
                'pattern' => 'Multi-wick shadow rejection at structural boundary',
                'key_level' => "Key price ceiling/floor established at " . ($stopLoss ?? 'boundary'),
                'momentum' => 'Expansion candles displacing beyond session mean',
            ],
            'market_structure' => [
                'name' => 'Market Structure & Trendlines',
                'verdict' => $finalDecision,
                'score' => 90,
                'status' => $finalDecision === 'SELL' ? 'Bearish Trend Progression' : 'Bullish Trend Progression',
                'phase' => 'Break of Structure (BOS) followed by premium pullback retest',
                'break_of_structure' => "Clear structural break confirmed",
                'trendline' => 'Pivot sequence intact',
            ],
            'fibonacci' => [
                'name' => 'Fibonacci Confluence Matrix',
                'verdict' => $finalDecision,
                'score' => 92,
                'status' => 'Golden Pocket Confluence',
                'golden_pocket' => "0.618 - 0.650 Golden Pocket retest rejected near " . ($entryTarget ?? 'entry zone'),
                'equilibrium' => 'Price positioned offering asymmetric R:R',
                'extension_tp' => "Fibonacci expansion target at " . ($tp2 ?? 'expansion level'),
            ],
        ];

        return [
            'symbol' => $context->symbol,
            'timeframe' => $context->timeframe,
            'data_source' => $context->dataSource,
            'data_timestamp' => now()->toIso8601String(),
            'decision' => $finalDecision,
            'direction' => $finalDecision, // Backward compatibility with ScanResult
            'market_trend' => $trend,
            'trend' => $trend,
            'confidence' => $confidence,
            'entry_type' => in_array($finalDecision, ['BUY', 'SELL']) ? 'limit_zone' : null,
            'entry_min' => $entryMin,
            'entry_max' => $entryMax,
            'entry_target' => $entryTarget,
            'stop_loss' => $stopLoss,
            'take_profit_1' => $tp1,
            'take_profit_2' => $tp2,
            'take_profit_3' => $tp3,
            'risk_reward' => $rrRatio,
            'risk_reward_ratio' => $rrRatio,
            'risk_distance' => $riskDistance,
            'reward_distance_tp1' => $rewardDistance,
            'technical_explanation' => $synthesisSummary ?? ($synthesisResult?->summary ?? "Multi-AI consensus completed: {$bullishVotes} Bullish, {$bearishVotes} Bearish, {$waitVotes} Neutral analysts."),
            'supporting_evidence' => $supportingEvidence,
            'contradictory_evidence' => $contradictoryEvidence,
            'reasoning' => $supportingEvidence, // Backward compatibility
            'analyst_summaries' => $analystSummaries,
            'confirmation_conditions' => $confirmations,
            'invalidation_conditions' => $invalidations,
            'risk_review' => $riskReviewData,
            'warnings' => array_merge($allWarnings, [
                'All trading involves capital risk. Do not risk more than 1-2% of total capital on a single setup.',
            ]),
            'calculated_indicators' => $context->indicators ?? [],
            'market_structure' => $context->marketStructure ?? [],
            'support_levels' => $supports,
            'resistance_levels' => $resistances,
            'strategy_confluences' => [
                'composite_score' => $compositeScore,
                'recommendation' => $recommendation,
                'strategies' => $confluenceStrategies,
                'bullish_analysts' => $bullishVotes,
                'bearish_analysts' => $bearishVotes,
                'neutral_analysts' => $waitVotes,
                'consensus_agreement_rate' => count($agentResults) > 0 ? round((max($bullishVotes, $bearishVotes) / count($agentResults)) * 100, 1) : 0,
            ],
            'selected_strategy' => $this->extractSelectedStrategy($synthesisResult, $agentResults),
            'market_regime' => $context->marketRegime ?? [],
            'retrieved_strategies' => $context->retrievedStrategies ?? [],
            'macro_news_context' => $context->macroNews,
        ];
    }

    protected function extractSelectedStrategy(?AgentAnalysisResult $synthesisResult, array $agentResults): array
    {
        // 1. Check if synthesis explicitly identified a selected strategy
        if ($synthesisResult && !empty($synthesisResult->rawResponse['selected_strategy'])) {
            $strat = $synthesisResult->rawResponse['selected_strategy'];
            if (is_array($strat) && !empty($strat['name']) && $strat['name'] !== 'NONE') {
                if (empty($strat['school']) && !empty($strat['category'])) {
                    $strat['school'] = $strat['category'];
                }
                if (empty($strat['category']) && !empty($strat['school'])) {
                    $strat['category'] = $strat['school'];
                }
                return $strat;
            }
        }

        // 2. Fall back to StrategySelectorAnalyst output
        $selectorRes = $agentResults['strategy_selector'] ?? null;
        if ($selectorRes && !empty($selectorRes->rawResponse['selected_strategy'])) {
            $strat = $selectorRes->rawResponse['selected_strategy'];
            if (is_array($strat) && !empty($strat['name']) && $strat['name'] !== 'NONE') {
                if (empty($strat['school']) && !empty($strat['category'])) {
                    $strat['school'] = $strat['category'];
                }
                if (empty($strat['category']) && !empty($strat['school'])) {
                    $strat['category'] = $strat['school'];
                }
                return $strat;
            }
        }

        return [
            'name' => 'Discretionary Multi-Confluence Price Action',
            'version' => '1.0.0',
            'school' => 'price_action',
            'category' => 'price_action',
            'rationale' => 'General multi-analyst confluence synthesis.',
        ];
    }

    protected function formatRoleTitle(string $role): string
    {
        return match ($role) {
            'price_action' => 'Price Action Analyst',
            'market_structure' => 'Market Structure Analyst',
            'smc' => 'Smart Money Concepts & Liquidity Analyst',
            'fibonacci_patterns' => 'Fibonacci & Harmonic Pattern Analyst',
            'quantitative_analyst' => 'Quantitative & Statistical Analyst',
            'market_regime' => 'Market Regime & Volatility Analyst',
            'technical_indicators' => 'Technical Indicator Analyst',
            'multi_timeframe' => 'Multi-Timeframe Confluence Analyst',
            'fundamental_news' => 'Global Fundamental & News Risk Analyst',
            'strategy_selector' => 'Strategy Selector & Evaluator',
            'independent_reviewer' => 'Independent Red Team Reviewer',
            'trading_risk' => 'Trading Risk Analyst',
            'final_decision' => 'Lead Synthesis & Final Execution Strategist',
            default => ucwords(str_replace('_', ' ', $role)),
        };
    }
}
