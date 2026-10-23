<?php

namespace App\Services\Evaluation;

use App\Services\AI\MultiAiAnalysisService;
use App\Services\TechnicalAnalysis\TechnicalAnalysisService;

class BacktestEvaluationService
{
    public function __construct(
        protected MultiAiAnalysisService $multiAiService,
        protected TechnicalAnalysisService $taService
    ) {}

    /**
     * Run comparative forward-simulation backtest on a series of historical candles.
     * Evaluates single-engine vs multi-AI consensus without look-ahead bias.
     *
     * @param array $candles Historical candles in chronological order
     * @param array $options ['spread' => 0.2, 'slippage' => 0.1, 'window_size' => 40, 'symbol' => 'XAUUSD', 'timeframe' => 'M15']
     * @return array
     */
    public function runEvaluation(array $candles, array $options = []): array
    {
        $windowSize = (int)($options['window_size'] ?? 40);
        $spread = (float)($options['spread'] ?? 0.2);
        $slippage = (float)($options['slippage'] ?? 0.1);
        $symbol = $options['symbol'] ?? 'XAUUSD';
        $timeframe = $options['timeframe'] ?? 'M15';

        $totalCandles = count($candles);
        if ($totalCandles < $windowSize + 10) {
            return [
                'error' => "Insufficient candles ({$totalCandles}) for evaluation. Minimum required: " . ($windowSize + 10),
            ];
        }

        $singleEngineTrades = [];
        $multiAiTrades = [];

        // Step through historical candles without look-ahead bias
        for ($i = $windowSize; $i < $totalCandles - 10; $i += 5) {
            $slice = array_slice($candles, $i - $windowSize, $windowSize);
            $futureCandles = array_slice($candles, $i, 15); // Subsequent 15 bars to simulate trade outcome

            // 1. Single Algorithmic Engine Signal
            $singleTa = $this->taService->analyze($slice);
            $singleSignal = $singleTa['signal'] ?? [];

            if (in_array($singleSignal['direction'] ?? 'WAIT', ['BUY', 'SELL'])) {
                $tradeResult = $this->simulateTradeOutcome(
                    $singleSignal,
                    $futureCandles,
                    $spread,
                    $slippage
                );
                if ($tradeResult) {
                    $singleEngineTrades[] = $tradeResult;
                }
            }

            // 2. Multi-AI Consensus Signal
            $multiPlan = $this->multiAiService->analyze(null, $slice, [
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'strategy' => 'comprehensive',
            ]);

            if (in_array($multiPlan['direction'] ?? 'WAIT', ['BUY', 'SELL'])) {
                $tradeResult = $this->simulateTradeOutcome(
                    $multiPlan,
                    $futureCandles,
                    $spread,
                    $slippage
                );
                if ($tradeResult) {
                    $multiAiTrades[] = $tradeResult;
                }
            }
        }

        return [
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'candles_evaluated' => $totalCandles,
            'parameters' => [
                'window_size' => $windowSize,
                'spread' => $spread,
                'slippage' => $slippage,
            ],
            'single_engine' => $this->computeMetrics($singleEngineTrades),
            'multi_ai_consensus' => $this->computeMetrics($multiAiTrades),
        ];
    }

    /**
     * Run news-aware historical backtest without look-ahead bias.
     * Compares pure technical execution vs News Risk Firewall filtered execution.
     *
     * @param array $candles
     * @param array $historicalEvents Array of ['event_name' => '...', 'scheduled_at' => '...']
     * @param array $options
     * @return array
     */
    public function runNewsAwareEvaluation(array $candles, array $historicalEvents = [], array $options = []): array
    {
        $windowSize = (int)($options['window_size'] ?? 40);
        $spread = (float)($options['spread'] ?? 0.2);
        $slippage = (float)($options['slippage'] ?? 0.1);
        $symbol = $options['symbol'] ?? 'XAUUSD';
        $timeframe = $options['timeframe'] ?? 'M15';
        $preBlockMin = (int)($options['pre_block_min'] ?? 30);
        $postStabilizeMin = (int)($options['post_stabilize_min'] ?? 20);

        $totalCandles = count($candles);
        if ($totalCandles < $windowSize + 10) {
            return [
                'error' => "Insufficient candles ({$totalCandles}) for news evaluation.",
            ];
        }

        $unfilteredTrades = [];
        $newsFilteredTrades = [];
        $avoidedTrades = [];

        for ($i = $windowSize; $i < $totalCandles - 10; $i += 4) {
            $slice = array_slice($candles, $i - $windowSize, $windowSize);
            $futureCandles = array_slice($candles, $i, 15);
            $currentBar = end($slice);
            $currentTs = strtotime($currentBar['timestamp'] ?? $currentBar['date'] ?? now()->toIso8601String());

            // Check if current candle timestamp falls within any historical news event risk window
            $isNewsBlocked = false;
            $blockingEvent = null;

            foreach ($historicalEvents as $ev) {
                $eventTs = strtotime($ev['scheduled_at']);
                $diffMin = ($eventTs - $currentTs) / 60;

                // Pre-event window: within $preBlockMin
                if ($diffMin >= 0 && $diffMin <= $preBlockMin) {
                    $isNewsBlocked = true;
                    $blockingEvent = $ev;
                    break;
                }
                // Post-event stabilization window: within $postStabilizeMin
                if ($diffMin < 0 && abs($diffMin) <= $postStabilizeMin) {
                    $isNewsBlocked = true;
                    $blockingEvent = $ev;
                    break;
                }
            }

            // Signal Generation
            $singleTa = $this->taService->analyze($slice);
            $signal = $singleTa['signal'] ?? [];

            if (in_array($signal['direction'] ?? 'WAIT', ['BUY', 'SELL'])) {
                $tradeResult = $this->simulateTradeOutcome(
                    $signal,
                    $futureCandles,
                    $spread,
                    $slippage
                );

                if ($tradeResult) {
                    $tradeResult['timestamp'] = date('Y-m-d H:i:s', $currentTs);
                    $unfilteredTrades[] = $tradeResult;

                    if ($isNewsBlocked) {
                        $avoidedTrades[] = array_merge($tradeResult, [
                            'blocked_reason' => $blockingEvent['event_name'] ?? 'High Impact Event',
                            'avoided_loss' => $tradeResult['status'] === 'loss',
                            'avoided_profit' => $tradeResult['status'] === 'win',
                        ]);
                    } else {
                        $newsFilteredTrades[] = $tradeResult;
                    }
                }
            }
        }

        $avoidedLosses = count(array_filter($avoidedTrades, fn($t) => $t['avoided_loss']));
        $avoidedProfits = count(array_filter($avoidedTrades, fn($t) => $t['avoided_profit']));

        return [
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'candles_evaluated' => $totalCandles,
            'parameters' => [
                'pre_block_window_min' => $preBlockMin,
                'post_stabilization_min' => $postStabilizeMin,
                'spread' => $spread,
                'slippage' => $slippage,
            ],
            'unfiltered_performance' => $this->computeMetrics($unfilteredTrades),
            'news_firewall_performance' => $this->computeMetrics($newsFilteredTrades),
            'firewall_attribution' => [
                'total_avoided_trades' => count($avoidedTrades),
                'toxic_trades_avoided' => $avoidedLosses,
                'profitable_trades_missed' => $avoidedProfits,
                'avoidance_efficacy_pct' => count($avoidedTrades) > 0 ? round(($avoidedLosses / count($avoidedTrades)) * 100, 1) : 100.0,
            ],
        ];
    }

    /**
     * Simulate trade execution forward across subsequent candles.
     */
    protected function simulateTradeOutcome(array $plan, array $futureCandles, float $spread, float $slippage): ?array
    {
        $dir = $plan['direction'] ?? 'WAIT';
        $entryTarget = (float)($plan['entry_target'] ?? 0);
        $stopLoss = (float)($plan['stop_loss'] ?? 0);
        $tp1 = (float)($plan['take_profit_1'] ?? 0);
        $tp2 = (float)($plan['take_profit_2'] ?? 0);

        if ($entryTarget <= 0 || $stopLoss <= 0 || $tp1 <= 0) {
            return null;
        }

        // Apply slippage and spread to effective entry
        $effectiveEntry = $dir === 'BUY' ? ($entryTarget + $slippage + $spread) : ($entryTarget - $slippage - $spread);
        $risk = abs($effectiveEntry - $stopLoss);
        if ($risk <= 0.001) return null;

        $status = 'open';
        $pnlR = 0.0;
        $barsHeld = 0;

        foreach ($futureCandles as $bar) {
            $barsHeld++;
            $high = (float)$bar['high'];
            $low = (float)$bar['low'];

            if ($dir === 'BUY') {
                // Check if SL hit
                if ($low <= $stopLoss) {
                    $status = 'loss';
                    $pnlR = -1.0;
                    break;
                }
                // Check if TP2 hit
                if ($tp2 > 0 && $high >= $tp2) {
                    $status = 'win';
                    $pnlR = round(($tp2 - $effectiveEntry) / $risk, 2);
                    break;
                }
                // Check if TP1 hit
                if ($high >= $tp1) {
                    $status = 'win';
                    $pnlR = round(($tp1 - $effectiveEntry) / $risk, 2);
                    break;
                }
            } else { // SELL
                // Check if SL hit
                if ($high >= $stopLoss) {
                    $status = 'loss';
                    $pnlR = -1.0;
                    break;
                }
                // Check if TP2 hit
                if ($tp2 > 0 && $low <= $tp2) {
                    $status = 'win';
                    $pnlR = round(($effectiveEntry - $tp2) / $risk, 2);
                    break;
                }
                // Check if TP1 hit
                if ($low <= $tp1) {
                    $status = 'win';
                    $pnlR = round(($effectiveEntry - $tp1) / $risk, 2);
                    break;
                }
            }
        }

        if ($status === 'open') {
            // Evaluated at expiration of window
            $lastClose = (float)end($futureCandles)['close'];
            $pnlR = $dir === 'BUY' ? round(($lastClose - $effectiveEntry) / $risk, 2) : round(($effectiveEntry - $lastClose) / $risk, 2);
            $status = $pnlR > 0 ? 'win' : 'loss';
        }

        return [
            'direction' => $dir,
            'entry' => $effectiveEntry,
            'stop_loss' => $stopLoss,
            'tp1' => $tp1,
            'status' => $status,
            'pnl_r' => $pnlR,
            'bars_held' => $barsHeld,
        ];
    }

    /**
     * Compute quantitative performance metrics from trade outcomes.
     */
    protected function computeMetrics(array $trades): array
    {
        $total = count($trades);
        if ($total === 0) {
            return [
                'total_trades' => 0,
                'wins' => 0,
                'losses' => 0,
                'win_rate_pct' => 0.0,
                'profit_factor' => 0.0,
                'max_drawdown_r' => 0.0,
                'expectancy_r' => 0.0,
                'net_profit_r' => 0.0,
            ];
        }

        $wins = array_filter($trades, fn($t) => $t['status'] === 'win');
        $winCount = count($wins);
        $winRate = round(($winCount / $total) * 100, 1);

        $grossProfit = 0.0;
        $grossLoss = 0.0;
        $cumR = 0.0;
        $peakR = 0.0;
        $maxDd = 0.0;

        foreach ($trades as $t) {
            $r = (float)$t['pnl_r'];
            if ($r > 0) {
                $grossProfit += $r;
            } else {
                $grossLoss += abs($r);
            }

            $cumR += $r;
            if ($cumR > $peakR) {
                $peakR = $cumR;
            }
            $dd = $peakR - $cumR;
            if ($dd > $maxDd) {
                $maxDd = $dd;
            }
        }

        $profitFactor = $grossLoss > 0 ? round($grossProfit / $grossLoss, 2) : round($grossProfit, 2);
        $netProfitR = round($cumR, 2);
        $expectancy = round($netProfitR / $total, 2);

        return [
            'total_trades' => $total,
            'wins' => $winCount,
            'losses' => $total - $winCount,
            'win_rate_pct' => $winRate,
            'profit_factor' => $profitFactor,
            'max_drawdown_r' => round($maxDd, 2),
            'expectancy_r' => $expectancy,
            'net_profit_r' => $netProfitR,
        ];
    }
}
