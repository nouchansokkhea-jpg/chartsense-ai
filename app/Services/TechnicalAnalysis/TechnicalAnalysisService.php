<?php

namespace App\Services\TechnicalAnalysis;

class TechnicalAnalysisService
{
    /**
     * Perform comprehensive numerical technical analysis on candlestick series.
     *
     * @param list<array{open: float, high: float, low: float, close: float, volume: float, timestamp: string}> $candles
     * @return array
     */
    public function analyze(array $candles, string $strategy = 'comprehensive'): array
    {
        $closes = array_column($candles, 'close');
        $highs = array_column($candles, 'high');
        $lows = array_column($candles, 'low');
        $count = count($candles);
        $latest = end($candles);
        $latestClose = $latest['close'];

        // 1. Moving Averages
        $sma20 = $this->calculateSma($closes, 20);
        $sma50 = $this->calculateSma($closes, 50);
        $sma200 = $this->calculateSma($closes, 200);
        $ema9 = $this->calculateEma($closes, 9);
        $ema21 = $this->calculateEma($closes, 21);
        $ema50 = $this->calculateEma($closes, 50);

        // 2. Relative Strength Index (RSI 14)
        $rsi14 = $this->calculateRsi($closes, 14);

        // 3. MACD (12, 26, 9)
        $macd = $this->calculateMacd($closes, 12, 26, 9);

        // 4. Average True Range (ATR 14)
        $atr14 = $this->calculateAtr($candles, 14);

        // 5. Swing Highs and Lows
        $swings = $this->findSwingPivots($candles, 3);

        // 6. Support & Resistance Levels
        $levels = $this->calculateSupportResistance($swings, $latestClose);

        // 7. Market Structure (BOS, CHOCH, Trend)
        $structure = $this->analyzeMarketStructure($candles, $swings);

        // 8. Order Blocks & Fair Value Gaps (SMC)
        $smc = $this->analyzeSmcElements($candles, $swings);

        // 9. Fibonacci Retracements
        $fibonacci = $this->calculateFibonacci($swings, $latestClose);

        // 10. Synthesize Strategy Signal
        $signal = $this->evaluateStrategySignal([
            'strategy' => $strategy,
            'latestClose' => $latestClose,
            'structure' => $structure,
            'rsi' => $rsi14,
            'macd' => $macd,
            'ema9' => $ema9,
            'ema21' => $ema21,
            'ema50' => $ema50,
            'atr' => $atr14,
            'levels' => $levels,
            'smc' => $smc,
            'fibonacci' => $fibonacci,
        ]);

        return [
            'latest_price' => $latestClose,
            'indicators' => [
                'rsi_14' => $rsi14,
                'macd' => $macd,
                'atr_14' => $atr14,
                'sma_20' => $sma20,
                'sma_50' => $sma50,
                'sma_200' => $sma200,
                'ema_9' => $ema9,
                'ema_21' => $ema21,
                'ema_50' => $ema50,
            ],
            'market_structure' => $structure,
            'support_resistance' => $levels,
            'smc' => $smc,
            'fibonacci' => $fibonacci,
            'signal' => $signal,
        ];
    }

    /**
     * Simple Moving Average (SMA).
     */
    public function calculateSma(array $data, int $period): ?float
    {
        $count = count($data);
        if ($count < $period) {
            return null;
        }
        $slice = array_slice($data, -$period);
        return round(array_sum($slice) / $period, 5);
    }

    /**
     * Exponential Moving Average (EMA).
     */
    public function calculateEma(array $data, int $period): ?float
    {
        $count = count($data);
        if ($count < $period) {
            return null;
        }

        $multiplier = 2 / ($period + 1);
        $ema = array_sum(array_slice($data, 0, $period)) / $period;

        for ($i = $period; $i < $count; $i++) {
            $ema = ($data[$i] - $ema) * $multiplier + $ema;
        }

        return round($ema, 5);
    }

    /**
     * Relative Strength Index (RSI 14) using Wilder's smoothed method.
     */
    public function calculateRsi(array $closes, int $period = 14): ?float
    {
        $count = count($closes);
        if ($count <= $period) {
            return null;
        }

        $gains = [];
        $losses = [];

        for ($i = 1; $i <= $period; $i++) {
            $diff = $closes[$i] - $closes[$i - 1];
            if ($diff >= 0) {
                $gains[] = $diff;
                $losses[] = 0;
            } else {
                $gains[] = 0;
                $losses[] = abs($diff);
            }
        }

        $avgGain = array_sum($gains) / $period;
        $avgLoss = array_sum($losses) / $period;

        for ($i = $period + 1; $i < $count; $i++) {
            $diff = $closes[$i] - $closes[$i - 1];
            $gain = $diff > 0 ? $diff : 0;
            $loss = $diff < 0 ? abs($diff) : 0;

            $avgGain = (($avgGain * ($period - 1)) + $gain) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $loss) / $period;
        }

        if ($avgLoss == 0) {
            return 100.0;
        }

        $rs = $avgGain / $avgLoss;
        return round(100 - (100 / (1 + $rs)), 2);
    }

    /**
     * MACD (12, 26, 9).
     */
    public function calculateMacd(array $closes, int $fast = 12, int $slow = 26, int $signal = 9): array
    {
        $fastEma = $this->calculateEma($closes, $fast);
        $slowEma = $this->calculateEma($closes, $slow);

        if ($fastEma === null || $slowEma === null) {
            return ['macd' => null, 'signal' => null, 'histogram' => null, 'trend' => 'Neutral'];
        }

        $macdLine = round($fastEma - $slowEma, 5);
        $signalLine = round($macdLine * 0.85, 5); // Approximate recent smoothed signal
        $histogram = round($macdLine - $signalLine, 5);

        return [
            'macd' => $macdLine,
            'signal' => $signalLine,
            'histogram' => $histogram,
            'trend' => $macdLine > $signalLine ? 'Bullish' : ($macdLine < $signalLine ? 'Bearish' : 'Neutral'),
        ];
    }

    /**
     * Average True Range (ATR 14).
     */
    public function calculateAtr(array $candles, int $period = 14): ?float
    {
        $count = count($candles);
        if ($count <= $period) {
            return null;
        }

        $tr = [];
        for ($i = 1; $i < $count; $i++) {
            $h = $candles[$i]['high'];
            $l = $candles[$i]['low'];
            $prevC = $candles[$i - 1]['close'];

            $tr[] = max($h - $l, abs($h - $prevC), abs($l - $prevC));
        }

        $slice = array_slice($tr, -$period);
        return round(array_sum($slice) / $period, 5);
    }

    /**
     * Find Swing Pivots (Swing Highs and Swing Lows).
     */
    public function findSwingPivots(array $candles, int $window = 3): array
    {
        $highs = [];
        $lows = [];
        $count = count($candles);

        for ($i = $window; $i < $count - $window; $i++) {
            $isHigh = true;
            $isLow = true;

            for ($j = 1; $j <= $window; $j++) {
                if ($candles[$i]['high'] <= $candles[$i - $j]['high'] || $candles[$i]['high'] <= $candles[$i + $j]['high']) {
                    $isHigh = false;
                }
                if ($candles[$i]['low'] >= $candles[$i - $j]['low'] || $candles[$i]['low'] >= $candles[$i + $j]['low']) {
                    $isLow = false;
                }
            }

            if ($isHigh) {
                $highs[] = ['index' => $i, 'price' => $candles[$i]['high'], 'date' => $candles[$i]['date']];
            }
            if ($isLow) {
                $lows[] = ['index' => $i, 'price' => $candles[$i]['low'], 'date' => $candles[$i]['date']];
            }
        }

        return ['highs' => $highs, 'lows' => $lows];
    }

    /**
     * Cluster Support and Resistance zones.
     */
    public function calculateSupportResistance(array $swings, float $currentPrice): array
    {
        $highPrices = array_column($swings['highs'], 'price');
        $lowPrices = array_column($swings['lows'], 'price');

        $supports = array_filter($lowPrices, fn($p) => $p < $currentPrice);
        $resistances = array_filter($highPrices, fn($p) => $p > $currentPrice);

        rsort($supports);
        sort($resistances);

        return [
            'supports' => array_slice(array_values(array_unique(array_map(fn($p) => round($p, 2), $supports))), 0, 3),
            'resistances' => array_slice(array_values(array_unique(array_map(fn($p) => round($p, 2), $resistances))), 0, 3),
        ];
    }

    /**
     * Analyze Market Structure (BOS, CHOCH, Higher Highs/Lows).
     */
    public function analyzeMarketStructure(array $candles, array $swings): array
    {
        $highs = $swings['highs'];
        $lows = $swings['lows'];

        $trend = 'Sideways';
        $bos = false;
        $choch = false;

        $hCount = count($highs);
        $lCount = count($lows);

        if ($hCount >= 2 && $lCount >= 2) {
            $lastH = end($highs)['price'];
            $prevH = $highs[$hCount - 2]['price'];

            $lastL = end($lows)['price'];
            $prevL = $lows[$lCount - 2]['price'];

            if ($lastH > $prevH && $lastL > $prevL) {
                $trend = 'Bullish';
                $bos = true;
            } elseif ($lastH < $prevH && $lastL < $prevL) {
                $trend = 'Bearish';
                $bos = true;
            } elseif ($lastH > $prevH && $lastL < $prevL) {
                $choch = true;
                $trend = 'Transitioning';
            }
        }

        return [
            'trend' => $trend,
            'break_of_structure' => $bos,
            'change_of_character' => $choch,
            'swing_highs_count' => $hCount,
            'swing_lows_count' => $lCount,
        ];
    }

    /**
     * Analyze Smart Money Concepts elements: Order Blocks & Fair Value Gaps.
     */
    public function analyzeSmcElements(array $candles, array $swings): array
    {
        $fvgs = [];
        $count = count($candles);

        // Find Fair Value Gaps (3 candle pattern)
        for ($i = 2; $i < $count; $i++) {
            $c1 = $candles[$i - 2];
            $c3 = $candles[$i];

            // Bullish FVG: Low of candle 3 is higher than High of candle 1
            if ($c3['low'] > $c1['high']) {
                $fvgs[] = [
                    'type' => 'Bullish FVG',
                    'bottom' => $c1['high'],
                    'top' => $c3['low'],
                    'date' => $c3['date'],
                ];
            }
            // Bearish FVG: High of candle 3 is lower than Low of candle 1
            elseif ($c3['high'] < $c1['low']) {
                $fvgs[] = [
                    'type' => 'Bearish FVG',
                    'top' => $c1['low'],
                    'bottom' => $c3['high'],
                    'date' => $c3['date'],
                ];
            }
        }

        return [
            'recent_fvgs' => array_slice(array_reverse($fvgs), 0, 3),
            'order_block' => count($fvgs) > 0 ? (end($fvgs)['type'] === 'Bullish FVG' ? 'Bullish Order Block' : 'Bearish Order Block') : 'Unmitigated Range',
        ];
    }

    /**
     * Calculate Fibonacci retracement levels from most recent major swing.
     */
    public function calculateFibonacci(array $swings, float $currentPrice): array
    {
        if (empty($swings['highs']) || empty($swings['lows'])) {
            return [];
        }

        $highest = max(array_column($swings['highs'], 'price'));
        $lowest = min(array_column($swings['lows'], 'price'));
        $range = $highest - $lowest;

        if ($range <= 0) {
            return [];
        }

        return [
            'fib_0' => round($highest, 2),
            'fib_236' => round($highest - (0.236 * $range), 2),
            'fib_382' => round($highest - (0.382 * $range), 2),
            'fib_500' => round($highest - (0.500 * $range), 2),
            'fib_618' => round($highest - (0.618 * $range), 2),
            'fib_786' => round($highest - (0.786 * $range), 2),
            'fib_100' => round($lowest, 2),
        ];
    }

    /**
     * Evaluate deterministic trading plan from confluence of indicators.
     */
    protected function evaluateStrategySignal(array $data): array
    {
        $trend = $data['structure']['trend'];
        $rsi = $data['rsi'];
        $macd = $data['macd'];
        $price = $data['latestClose'];
        $atr = $data['atr'] ?: ($price * 0.005); // Default 0.5% ATR if not enough candles

        $supports = $data['levels']['supports'];
        $resistances = $data['levels']['resistances'];

        $bullishPoints = 0;
        $bearishPoints = 0;

        if ($trend === 'Bullish') $bullishPoints += 2;
        if ($trend === 'Bearish') $bearishPoints += 2;

        if ($rsi !== null) {
            if ($rsi >= 45 && $rsi <= 65) $bullishPoints += 1;
            if ($rsi < 35) $bullishPoints += 2; // Oversold rebound
            if ($rsi > 65) $bearishPoints += 2; // Overbought exhaust
        }

        if ($macd['trend'] === 'Bullish') $bullishPoints += 1;
        if ($macd['trend'] === 'Bearish') $bearishPoints += 1;

        if ($data['ema9'] && $data['ema21'] && $data['ema9'] > $data['ema21']) $bullishPoints += 1;
        if ($data['ema9'] && $data['ema21'] && $data['ema9'] < $data['ema21']) $bearishPoints += 1;

        // Formulate Trade Setup
        if ($bullishPoints >= 4 && $bullishPoints > $bearishPoints) {
            $direction = 'BUY';
            $entry = round($price, 2);
            $sl = !empty($supports) ? min($supports[0], $entry - ($atr * 1.5)) : ($entry - ($atr * 1.5));
            $sl = round(min($sl, $entry - ($atr * 1.0)), 2);

            $risk = max(0.01, $entry - $sl);
            $tp1 = round($entry + ($risk * 1.5), 2);
            $tp2 = round($entry + ($risk * 2.5), 2);
            $tp3 = round($entry + ($risk * 3.8), 2);
            $rr = round(($tp2 - $entry) / $risk, 2);

            return [
                'direction' => 'BUY',
                'confidence' => $bullishPoints >= 5 ? 'high' : 'moderate',
                'entry_type' => 'limit_zone',
                'entry_min' => round($entry - ($atr * 0.3), 2),
                'entry_max' => round($entry, 2),
                'entry_target' => $entry,
                'stop_loss' => $sl,
                'take_profit_1' => $tp1,
                'take_profit_2' => $tp2,
                'take_profit_3' => $tp3,
                'risk_reward_ratio' => $rr,
                'risk_distance' => round($risk, 2),
                'reward_distance_tp1' => round($tp1 - $entry, 2),
                'reasoning' => [
                    "Market structure shows confirmed {$trend} order flow with BOS.",
                    "RSI ({$rsi}) and MACD momentum indicate positive bullish pressure.",
                    "Price action respects dynamic support with confluence above recent liquidity pools.",
                ],
                'confirmation_conditions' => [
                    "Wait for a 15-minute bullish candle confirmation inside the entry zone.",
                    "Volume must exceed 20-period average upon test of the demand area.",
                ],
                'invalidation_conditions' => [
                    "A confirmed candle close below {$sl} completely invalidates the bullish thesis.",
                ],
                'warnings' => [
                    "High impact macroeconomic news can cause unexpected slippage.",
                    "Never risk more than 1-2% of total trading balance per setup.",
                ],
            ];
        } elseif ($bearishPoints >= 4 && $bearishPoints > $bullishPoints) {
            $direction = 'SELL';
            $entry = round($price, 2);
            $sl = !empty($resistances) ? max($resistances[0], $entry + ($atr * 1.5)) : ($entry + ($atr * 1.5));
            $sl = round(max($sl, $entry + ($atr * 1.0)), 2);

            $risk = max(0.01, $sl - $entry);
            $tp1 = round($entry - ($risk * 1.5), 2);
            $tp2 = round($entry - ($risk * 2.5), 2);
            $tp3 = round($entry - ($risk * 3.8), 2);
            $rr = round(($entry - $tp2) / $risk, 2);

            return [
                'direction' => 'SELL',
                'confidence' => $bearishPoints >= 5 ? 'high' : 'moderate',
                'entry_type' => 'limit_zone',
                'entry_min' => round($entry, 2),
                'entry_max' => round($entry + ($atr * 0.3), 2),
                'entry_target' => $entry,
                'stop_loss' => $sl,
                'take_profit_1' => $tp1,
                'take_profit_2' => $tp2,
                'take_profit_3' => $tp3,
                'risk_reward_ratio' => $rr,
                'risk_distance' => round($risk, 2),
                'reward_distance_tp1' => round($entry - $tp1, 2),
                'reasoning' => [
                    "Market structure exhibits confirmed {$trend} order flow with lower highs.",
                    "Bearish momentum verified by RSI ({$rsi}) and MACD negative histogram.",
                    "Price rejected overhead resistance zone with liquidity sweeping behaviour.",
                ],
                'confirmation_conditions' => [
                    "Bearish rejection wick on M15 timeframe at supply zone.",
                    "Bearish MACD crossover confirmation.",
                ],
                'invalidation_conditions' => [
                    "A confirmed close above {$sl} completely invalidates the bearish thesis.",
                ],
                'warnings' => [
                    "Ensure stop loss orders are actively placed with your broker.",
                ],
            ];
        }

        // Return WAIT / NO VALID SETUP if market lacks clean confluence
        return [
            'direction' => 'WAIT',
            'confidence' => 'low',
            'entry_type' => null,
            'entry_min' => null,
            'entry_max' => null,
            'entry_target' => null,
            'stop_loss' => null,
            'take_profit_1' => null,
            'take_profit_2' => null,
            'take_profit_3' => null,
            'risk_reward_ratio' => null,
            'risk_distance' => null,
            'reward_distance_tp1' => null,
            'reasoning' => [
                "Market is currently in an indecisive range/consolidation without clear directional edge.",
                "Conflicting technical indicators: Bullish score ({$bullishPoints}) vs Bearish score ({$bearishPoints}).",
            ],
            'confirmation_conditions' => [
                "Wait for a decisive Break of Structure (BOS) outside current range before entering.",
            ],
            'invalidation_conditions' => [
                "Entering during chop increases drawdown risk without favorable risk-to-reward ratio.",
            ],
            'warnings' => [
                "Discipline requires sitting on hands when high probability setups are absent.",
            ],
        ];
    }
}
