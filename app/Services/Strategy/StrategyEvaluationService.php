<?php

namespace App\Services\Strategy;

use App\Models\Strategy;
use App\Models\StrategyEvaluation;
use Illuminate\Support\Collection;

class StrategyEvaluationService
{
    public const MIN_SAMPLE_SIZE_FOR_RANKING = 20;

    /**
     * Compute multi-metric performance evaluation on a series of simulated or live trades.
     *
     * @param list<array{outcome: 'win'|'loss'|'breakeven', r_multiple: float, slippage_r?: float}> $trades
     * @return array{
     *     sample_size: int,
     *     wins: int,
     *     losses: int,
     *     breakevens: int,
     *     win_rate_pct: float,
     *     profit_factor: float,
     *     expectancy_r: float,
     *     max_drawdown_r: float,
     *     net_profit_r: float,
     *     avg_rr_ratio: float,
     *     slippage_sensitivity_score: float,
     *     ranking_tier: string,
     *     evidence_status: string,
     *     is_rankable: bool,
     *     summary_label: string
     * }
     */
    public function evaluateTrades(array $trades): array
    {
        $count = count($trades);

        if ($count === 0) {
            return [
                'sample_size' => 0,
                'wins' => 0,
                'losses' => 0,
                'breakevens' => 0,
                'win_rate_pct' => 0.0,
                'profit_factor' => 0.0,
                'expectancy_r' => 0.0,
                'max_drawdown_r' => 0.0,
                'net_profit_r' => 0.0,
                'avg_rr_ratio' => 0.0,
                'slippage_sensitivity_score' => 0.0,
                'ranking_tier' => 'insufficient_data',
                'evidence_status' => 'NOT ENOUGH VALIDATED DATA TO RANK THIS STRATEGY',
                'is_rankable' => false,
                'summary_label' => 'NOT ENOUGH VALIDATED DATA (<20 trades) TO RANK',
            ];
        }

        $wins = 0;
        $losses = 0;
        $breakevens = 0;
        $winRSum = 0.0;
        $lossRSum = 0.0;
        $netR = 0.0;
        $peakEquityR = 0.0;
        $maxDrawdownR = 0.0;
        $currentEquityR = 0.0;

        $slippageDeductions = 0.0;

        foreach ($trades as $t) {
            $r = (float)($t['r_multiple'] ?? 0.0);
            $outcome = $t['outcome'] ?? ($r > 0 ? 'win' : ($r < 0 ? 'loss' : 'breakeven'));
            $slippageR = (float)($t['slippage_r'] ?? 0.05);
            $slippageDeductions += $slippageR;

            $currentEquityR += $r;
            if ($currentEquityR > $peakEquityR) {
                $peakEquityR = $currentEquityR;
            }
            $drawdown = $peakEquityR - $currentEquityR;
            if ($drawdown > $maxDrawdownR) {
                $maxDrawdownR = $drawdown;
            }

            if ($outcome === 'win' || $r > 0.01) {
                $wins++;
                $winRSum += $r;
            } elseif ($outcome === 'loss' || $r < -0.01) {
                $losses++;
                $lossRSum += abs($r);
            } else {
                $breakevens++;
            }

            $netR += $r;
        }

        $winRatePct = round(($wins / $count) * 100, 2);
        $profitFactor = $lossRSum > 0 ? round($winRSum / $lossRSum, 2) : ($winRSum > 0 ? 99.0 : 0.0);

        $avgWinR = $wins > 0 ? $winRSum / $wins : 0.0;
        $avgLossR = $losses > 0 ? $lossRSum / $losses : 0.0;

        // Expectancy in R: (WinRate * AvgWinR) - (LossRate * AvgLossR)
        $winRateFrac = $wins / $count;
        $lossRateFrac = $losses / $count;
        $expectancyR = round(($winRateFrac * $avgWinR) - ($lossRateFrac * $avgLossR), 2);

        $avgRR = $avgLossR > 0 ? round($avgWinR / $avgLossR, 2) : 1.5;
        $slippageSensitivity = round(min(1.0, ($slippageDeductions / max(1.0, abs($netR)))), 2);

        // Guardrail: Minimum sample size constraint
        $isRankable = ($count >= self::MIN_SAMPLE_SIZE_FOR_RANKING);
        $rankingTier = 'insufficient_data';
        $evidenceStatus = 'NOT ENOUGH VALIDATED DATA TO RANK THIS STRATEGY';
        $summaryLabel = sprintf('NOT ENOUGH VALIDATED DATA (N=%d < 20 trades) TO RANK', $count);

        if ($isRankable) {
            if ($count >= 100 && $profitFactor >= 1.80 && $expectancyR >= 0.80 && $maxDrawdownR <= 8.0) {
                $rankingTier = 'tier_s';
            } elseif ($count >= 50 && $profitFactor >= 1.40 && $expectancyR >= 0.40) {
                $rankingTier = 'tier_a';
            } elseif ($count >= 30 && $profitFactor >= 1.15 && $expectancyR >= 0.15) {
                $rankingTier = 'tier_b';
            } elseif ($profitFactor >= 1.0 && $expectancyR >= 0.0) {
                $rankingTier = 'tier_c';
            } else {
                $rankingTier = 'unprofitable';
            }

            $evidenceStatus = strtoupper(str_replace('_', ' ', $rankingTier));
            $summaryLabel = sprintf(
                '%s: %.1f%% WR | PF %.2f | Exp +%.2fR (N=%d)',
                strtoupper(str_replace('_', ' ', $rankingTier)),
                $winRatePct,
                $profitFactor,
                $expectancyR,
                $count
            );
        }

        return [
            'sample_size' => $count,
            'wins' => $wins,
            'losses' => $losses,
            'breakevens' => $breakevens,
            'win_rate_pct' => $winRatePct,
            'profit_factor' => $profitFactor,
            'expectancy_r' => $expectancyR,
            'max_drawdown_r' => round($maxDrawdownR, 2),
            'net_profit_r' => round($netR, 2),
            'avg_rr_ratio' => $avgRR,
            'slippage_sensitivity_score' => $slippageSensitivity,
            'ranking_tier' => $rankingTier,
            'evidence_status' => $evidenceStatus,
            'is_rankable' => $isRankable,
            'summary_label' => $summaryLabel,
        ];
    }

    /**
     * Record an evaluation into the database for a strategy.
     */
    public function recordEvaluation(Strategy $strategy, array $evaluationMetrics, array $context = []): StrategyEvaluation
    {
        return StrategyEvaluation::create([
            'strategy_id' => $strategy->id,
            'symbol' => $context['symbol'] ?? 'ALL',
            'timeframe' => $context['timeframe'] ?? 'ALL',
            'market_regime' => $context['market_regime'] ?? 'ALL',
            'sample_size_trades' => $evaluationMetrics['sample_size'],
            'wins' => $evaluationMetrics['wins'],
            'losses' => $evaluationMetrics['losses'],
            'win_rate_pct' => $evaluationMetrics['win_rate_pct'],
            'profit_factor' => $evaluationMetrics['profit_factor'],
            'expectancy_r' => $evaluationMetrics['expectancy_r'],
            'max_drawdown_r' => $evaluationMetrics['max_drawdown_r'],
            'net_profit_r' => $evaluationMetrics['net_profit_r'],
            'avg_rr_ratio' => $evaluationMetrics['avg_rr_ratio'],
            'slippage_sensitivity_score' => $evaluationMetrics['slippage_sensitivity_score'],
            'out_of_sample_tested' => $context['out_of_sample_tested'] ?? false,
            'ranking_tier' => $evaluationMetrics['ranking_tier'],
            'evaluation_notes' => $context['notes'] ?? 'Automated evaluation run.',
            'evaluated_at' => now(),
        ]);
    }

    /**
     * Return ranked strategies leaderboard, segregating validated strategies from insufficient data.
     *
     * @param string|null $categorySlug
     * @param string|null $regime
     * @return array{
     *     ranked: list<array>,
     *     unranked_insufficient_data: list<array>,
     *     categories: list<string>
     * }
     */
    public function getRankedLeaderboard(?string $categorySlug = null, ?string $regime = null): array
    {
        $query = Strategy::with(['category', 'currentVersion', 'evaluations'])->where('is_active', true);

        if ($categorySlug) {
            $query->whereHas('category', fn($q) => $q->where('slug', $categorySlug));
        }

        $strategies = $query->get();

        $ranked = [];
        $unranked = [];

        foreach ($strategies as $strat) {
            $eval = $strat->evaluations->sortByDesc('sample_size_trades')->first();
            $currentVer = $strat->currentVersion;

            $entry = [
                'id' => $strat->id,
                'name' => $strat->name,
                'slug' => $strat->slug,
                'category_name' => $strat->category_name ?? $strat->category?->name ?? 'General',
                'category_slug' => $strat->category?->slug ?? 'general',
                'version' => $currentVer?->version_number ?? '1.0.0',
                'implementation_status' => $strat->implementation_status,
                'market_regimes' => $strat->market_regime_suitability ?? [],
                'sample_size' => $eval?->sample_size_trades ?? 0,
                'win_rate_pct' => $eval?->win_rate_pct ?? 0.0,
                'profit_factor' => $eval?->profit_factor ?? 0.0,
                'expectancy_r' => $eval?->expectancy_r ?? 0.0,
                'max_drawdown_r' => $eval?->max_drawdown_r ?? 0.0,
                'ranking_tier' => $eval?->ranking_tier ?? 'insufficient_data',
                'is_rankable' => ($eval && $eval->sample_size_trades >= self::MIN_SAMPLE_SIZE_FOR_RANKING),
                'label' => ($eval && $eval->sample_size_trades >= self::MIN_SAMPLE_SIZE_FOR_RANKING)
                    ? sprintf('%s: %.1f%% WR | PF %.2f | Exp +%.2fR', strtoupper(str_replace('_', ' ', $eval->ranking_tier)), $eval->win_rate_pct, $eval->profit_factor, $eval->expectancy_r)
                    : 'NOT ENOUGH VALIDATED DATA TO RANK THIS STRATEGY',
            ];

            if ($entry['is_rankable']) {
                $ranked[] = $entry;
            } else {
                $unranked[] = $entry;
            }
        }

        // Sort ranked by Profit Factor and Expectancy descending
        usort($ranked, function ($a, $b) {
            $tierWeight = [
                'tier_s' => 5,
                'tier_a' => 4,
                'tier_b' => 3,
                'tier_c' => 2,
                'unprofitable' => 1,
                'insufficient_data' => 0,
            ];
            $tA = $tierWeight[$a['ranking_tier']] ?? 0;
            $tB = $tierWeight[$b['ranking_tier']] ?? 0;

            if ($tA !== $tB) {
                return $tB <=> $tA;
            }

            return $b['profit_factor'] <=> $a['profit_factor'];
        });

        return [
            'ranked' => $ranked,
            'unranked_insufficient_data' => $unranked,
            'total_strategies' => count($strategies),
            'ranked_count' => count($ranked),
            'unranked_count' => count($unranked),
        ];
    }
}
