<?php

namespace App\Services\Strategy;

use App\Models\Strategy;
use App\Models\StrategyEvaluation;
use App\Services\TechnicalAnalysis\MarketRegimeService;
use Illuminate\Support\Collection;

class StrategyRetrievalService
{
    protected MarketRegimeService $regimeService;

    public function __construct(MarketRegimeService $regimeService)
    {
        $this->regimeService = $regimeService;
    }

    /**
     * Retrieve matching trading strategies based on current market regime, asset, timeframe, and technical signals.
     *
     * @param string $symbol e.g. "EURUSD", "XAUUSD"
     * @param string $timeframe e.g. "15m", "1h", "4h", "1d"
     * @param string $marketRegime e.g. "TRENDING_BULLISH", "RANGING_TIGHT"
     * @param array $technicalContext Output from TechnicalAnalysisService
     * @param int $limit Maximum number of matching strategies to retrieve
     * @return array{
     *     candidates: list<array>,
     *     regime_context: array,
     *     prompt_context: string
     * }
     */
    public function retrieveRelevantStrategies(
        string $symbol,
        string $timeframe,
        string $marketRegime,
        array $technicalContext = [],
        int $limit = 4
    ): array {
        $strategies = Strategy::with(['currentVersion', 'category', 'evaluations'])
            ->where('is_active', true)
            ->get();

        $regimeSuitability = $this->regimeService->getSuitabilityForRegime($marketRegime);
        $suitableCategories = $regimeSuitability['suitable_categories'] ?? [];
        $unsuitableCategories = $regimeSuitability['unsuitable_categories'] ?? [];

        $scoredCandidates = [];

        foreach ($strategies as $strategy) {
            $score = $this->calculateSuitabilityScore(
                $strategy,
                $symbol,
                $timeframe,
                $marketRegime,
                $suitableCategories,
                $unsuitableCategories,
                $technicalContext
            );

            // Latest evaluation for this symbol or general
            $eval = $this->getBestEvaluation($strategy, $symbol);

            $evidenceStatus = 'insufficient_data';
            $evidenceLabel = 'NOT ENOUGH VALIDATED DATA (<20 trades) TO RANK';
            if ($eval && $eval->sample_size_trades >= 20) {
                $evidenceStatus = $eval->ranking_tier ?? 'tested';
                $evidenceLabel = sprintf(
                    'Tier %s: %s%% WR | PF %.2f | Expectancy +%.2fR (N=%d)',
                    strtoupper($eval->ranking_tier ?? 'C'),
                    $eval->win_rate_pct,
                    $eval->profit_factor,
                    $eval->expectancy_r,
                    $eval->sample_size_trades
                );
            }

            $currentVersion = $strategy->currentVersion;

            $scoredCandidates[] = [
                'id' => $strategy->id,
                'name' => $strategy->name,
                'slug' => $strategy->slug,
                'category_name' => $strategy->category_name ?? $strategy->category?->name ?? 'General',
                'category_slug' => $strategy->category?->slug ?? 'general',
                'score' => $score,
                'is_regime_match' => $strategy->isSuitableForRegime($marketRegime),
                'implementation_status' => $strategy->implementation_status,
                'evidence_status' => $evidenceStatus,
                'evidence_label' => $evidenceLabel,
                'evaluation' => $eval ? [
                    'sample_size' => $eval->sample_size_trades,
                    'win_rate_pct' => $eval->win_rate_pct,
                    'profit_factor' => $eval->profit_factor,
                    'expectancy_r' => $eval->expectancy_r,
                    'max_drawdown_r' => $eval->max_drawdown_r,
                    'ranking_tier' => $eval->ranking_tier,
                ] : null,
                'description' => $strategy->description,
                'intended_regimes' => $strategy->market_regime_suitability ?? [],
                'known_weaknesses' => $strategy->known_weaknesses ?? [],
                'rules' => [
                    'entry' => $currentVersion?->entry_rules ?? [],
                    'stop_loss' => $currentVersion?->stop_loss_rules ?? [],
                    'take_profit' => $currentVersion?->take_profit_rules ?? [],
                    'invalidation' => $currentVersion?->invalidation_rules ?? [],
                    'exit' => $currentVersion?->exit_rules ?? [],
                ],
                'version' => $currentVersion?->version_number ?? '1.0.0',
            ];
        }

        // Sort descending by score
        usort($scoredCandidates, fn($a, $b) => $b['score'] <=> $a['score']);

        $topCandidates = array_slice($scoredCandidates, 0, $limit);

        // Build prompt context string
        $promptContext = $this->buildRetrievalPromptContext(
            $topCandidates,
            $marketRegime,
            $symbol,
            $timeframe
        );

        return [
            'candidates' => $topCandidates,
            'regime_context' => [
                'regime' => $marketRegime,
                'label' => $this->regimeService->getRegimeLabel($marketRegime),
                'suitable_categories' => $suitableCategories,
                'unsuitable_categories' => $unsuitableCategories,
                'guidance' => $regimeSuitability['risk_guidance'] ?? '',
            ],
            'prompt_context' => $promptContext,
        ];
    }

    /**
     * Calculate multi-factor match score for a strategy.
     */
    protected function calculateSuitabilityScore(
        Strategy $strategy,
        string $symbol,
        string $timeframe,
        string $marketRegime,
        array $suitableCategories,
        array $unsuitableCategories,
        array $technicalContext
    ): int {
        $score = 50; // Baseline

        // 1. Regime match
        $catSlug = $strategy->category?->slug ?? '';
        if (in_array($catSlug, $suitableCategories, true)) {
            $score += 35;
        } elseif (in_array($catSlug, $unsuitableCategories, true)) {
            $score -= 40;
        }

        if ($strategy->isSuitableForRegime($marketRegime)) {
            $score += 25;
        } else {
            $score -= 20;
        }

        // 2. Instrument suitability
        $instruments = $strategy->supported_instruments ?? [];
        if (empty($instruments) || in_array('all', $instruments, true)) {
            $score += 10;
        } else {
            $matched = false;
            foreach ($instruments as $inst) {
                if (stripos($symbol, $inst) !== false || stripos($inst, $symbol) !== false) {
                    $matched = true;
                    break;
                }
            }
            $score += $matched ? 15 : -10;
        }

        // 3. Timeframe suitability
        $timeframes = $strategy->timeframes ?? [];
        if (empty($timeframes) || in_array('all', $timeframes, true)) {
            $score += 10;
        } else {
            $matchedTf = false;
            foreach ($timeframes as $tf) {
                if (strcasecmp($tf, $timeframe) === 0) {
                    $matchedTf = true;
                    break;
                }
            }
            $score += $matchedTf ? 15 : -5;
        }

        // 4. Technical feature alignment
        if (!empty($technicalContext)) {
            // If SMC elements present and strategy is ICT/SMC
            if ($catSlug === 'ict_smc' && (!empty($technicalContext['smc']['fair_value_gaps']) || !empty($technicalContext['smc']['order_blocks']))) {
                $score += 20;
            }

            // If Fibonacci golden zone present and strategy is Fibonacci
            if ($catSlug === 'fibonacci' && !empty($technicalContext['fibonacci']['golden_zone'])) {
                $score += 20;
            }

            // If RSI extreme and strategy is indicator-based
            $rsi = $technicalContext['indicators']['rsi_14'] ?? 50;
            if ($catSlug === 'indicator_based' && ($rsi <= 30 || $rsi >= 70)) {
                $score += 15;
            }

            // If Break & Retest level tested
            if ($catSlug === 'price_action' && !empty($technicalContext['support_resistance']['nearest_support'])) {
                $score += 15;
            }
        }

        return max(0, min(100, $score));
    }

    protected function getBestEvaluation(Strategy $strategy, string $symbol): ?StrategyEvaluation
    {
        // Try exact symbol match first
        $eval = $strategy->evaluations->firstWhere('symbol', $symbol);
        if ($eval) {
            return $eval;
        }

        // Fall back to general evaluation with largest sample size
        return $strategy->evaluations->sortByDesc('sample_size_trades')->first();
    }

    /**
     * Build RAG injection prompt context for multi-AI analysts.
     * Note: Explicitly disclaims model training/weight alteration.
     */
    public function buildRetrievalPromptContext(
        array $candidates,
        string $marketRegime,
        string $symbol,
        string $timeframe
    ): string {
        $out = [];
        $out[] = "================================================================================";
        $out[] = "=== RETRIEVED STRATEGY SPECIFICATIONS (DYNAMIC IN-CONTEXT REFERENCE ONLY) ===";
        $out[] = "================================================================================";
        $out[] = "IMPORTANT ARCHITECTURAL NOTICE FOR AI ANALYSTS:";
        $out[] = "- The strategies below are retrieved dynamically from the ChartSense Strategy Knowledge Base.";
        $out[] = "- They are provided as analytical criteria for this specific chart ({$symbol} - {$timeframe}).";
        $out[] = "- They DO NOT represent model fine-tuning, training weights, or permanent model updates.";
        $out[] = "- You must evaluate each strategy deterministically against confirmed price levels and market structure.";
        $out[] = "- Active Market Regime: {$marketRegime} ({$this->regimeService->getRegimeLabel($marketRegime)}).";
        $out[] = "================================================================================\n";

        foreach ($candidates as $i => $strat) {
            $idx = $i + 1;
            $out[] = "### STRATEGY #{$idx}: {$strat['name']} (Category: {$strat['category_name']})";
            $out[] = "- Version: {$strat['version']} | Feasibility: {$strat['implementation_status']} | Match Score: {$strat['score']}/100";
            $out[] = "- Empirical Status: {$strat['evidence_label']}";
            $out[] = "- Description: {$strat['description']}";

            if (!empty($strat['intended_regimes'])) {
                $out[] = "- Intended Regimes: " . implode(', ', $strat['intended_regimes']);
            }

            // Entry rules
            if (!empty($strat['rules']['entry'])) {
                $out[] = "- Mandatory Entry Rules:";
                foreach ($strat['rules']['entry'] as $rule) {
                    $out[] = "    * " . (is_string($rule) ? $rule : json_encode($rule));
                }
            }

            // Stop loss rules
            if (!empty($strat['rules']['stop_loss'])) {
                $out[] = "- Stop Loss Rules:";
                foreach ($strat['rules']['stop_loss'] as $rule) {
                    $out[] = "    * " . (is_string($rule) ? $rule : json_encode($rule));
                }
            }

            // Take profit rules
            if (!empty($strat['rules']['take_profit'])) {
                $out[] = "- Take Profit Rules:";
                foreach ($strat['rules']['take_profit'] as $rule) {
                    $out[] = "    * " . (is_string($rule) ? $rule : json_encode($rule));
                }
            }

            // Invalidation rules
            if (!empty($strat['rules']['invalidation'])) {
                $out[] = "- Setup Invalidation Rules:";
                foreach ($strat['rules']['invalidation'] as $rule) {
                    $out[] = "    * " . (is_string($rule) ? $rule : json_encode($rule));
                }
            }

            // Known weaknesses
            if (!empty($strat['known_weaknesses'])) {
                $out[] = "- Known Pitfalls & Regime Hazards:";
                foreach ($strat['known_weaknesses'] as $weakness) {
                    $out[] = "    ! {$weakness}";
                }
            }

            $out[] = "";
        }

        $out[] = "AI COLLABORATION INSTRUCTION:";
        $out[] = "1. Compare this chart's price structure against the above retrieved strategies.";
        $out[] = "2. Identify which strategy best fits the current regime or state explicitly if NO strategy matches.";
        $out[] = "3. If a strategy's mandatory entry or invalidation rules are violated, REJECT the setup.";
        $out[] = "4. If market conditions are uncertain or conflicting, recommend 'WAIT' or 'NO VALID SETUP'.\n";

        return implode("\n", $out);
    }
}
