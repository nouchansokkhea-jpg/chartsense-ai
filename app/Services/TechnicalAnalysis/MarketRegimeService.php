<?php

namespace App\Services\TechnicalAnalysis;

class MarketRegimeService
{
    public const REGIME_TRENDING_BULLISH = 'TRENDING_BULLISH';
    public const REGIME_TRENDING_BEARISH = 'TRENDING_BEARISH';
    public const REGIME_RANGING_TIGHT    = 'RANGING_TIGHT';
    public const REGIME_RANGING_EXPANDING = 'RANGING_EXPANDING';
    public const REGIME_VOLATILE         = 'VOLATILE';
    public const REGIME_UNCERTAIN        = 'UNCERTAIN';

    /**
     * Classify current market regime deterministically from candlestick data.
     *
     * @param list<array{open: float, high: float, low: float, close: float, volume?: float}> $candles
     * @param array|null $precalculatedIndicators
     * @return array{
     *     regime: string,
     *     regime_label: string,
     *     confidence: float,
     *     description: string,
     *     metrics: array,
     *     suitable_categories: list<string>,
     *     unsuitable_categories: list<string>,
     *     suitable_strategies: list<string>,
     *     unsuitable_strategies: list<string>,
     *     risk_guidance: string
     * }
     */
    public function classify(array $candles, ?array $precalculatedIndicators = null): array
    {
        $count = count($candles);
        if ($count < 15) {
            return $this->fallbackRegime('Insufficient candle history to classify market regime deterministically.');
        }

        $closes = array_column($candles, 'close');
        $highs  = array_column($candles, 'high');
        $lows   = array_column($candles, 'low');
        $latestClose = end($closes);

        // 1. Calculate or extract Moving Averages (EMA 9, 21, 50, SMA 200)
        $ema9 = $precalculatedIndicators['indicators']['ema_9'] ?? $this->calculateEma($closes, 9);
        $ema21 = $precalculatedIndicators['indicators']['ema_21'] ?? $this->calculateEma($closes, 21);
        $ema50 = $precalculatedIndicators['indicators']['ema_50'] ?? $this->calculateEma($closes, 50);
        $sma200 = $precalculatedIndicators['indicators']['sma_200'] ?? $this->calculateSma($closes, min(200, $count));

        // 2. Calculate ADX (14) & Directional Movement
        $adxData = $this->calculateAdx($candles, 14);
        $adx = $adxData['adx'];
        $plusDi = $adxData['plus_di'];
        $minusDi = $adxData['minus_di'];

        // 3. Volatility metrics: ATR (14) & ATR Relative to rolling baseline
        $atr14 = $precalculatedIndicators['indicators']['atr_14'] ?? $this->calculateAtr($candles, 14);
        $atrPercentile = $this->calculateAtrPercentile($candles, 14, 50);

        // 4. Bollinger Bandwidth (BBW)
        $bb = $precalculatedIndicators['indicators']['bollinger'] ?? $this->calculateBollingerBands($closes, 20, 2.0);
        $bandwidth = ($bb && $bb['middle'] > 0) ? (($bb['upper'] - $bb['lower']) / $bb['middle']) * 100 : 0.0;

        // 5. Swing Structure (HH/HL vs LH/LL)
        $structure = $precalculatedIndicators['market_structure']['trend']
            ?? $this->detectSwingBias($candles);

        // 6. Moving Average Alignment Score (-100 to +100)
        $maScore = 0;
        if ($ema9 !== null && $ema21 !== null) {
            if ($ema50 !== null) {
                if ($latestClose > $ema9 && $ema9 > $ema21 && $ema21 > $ema50) {
                    $maScore = 100;
                } elseif ($latestClose > $ema21 && $ema21 > $ema50) {
                    $maScore = 60;
                } elseif ($latestClose < $ema9 && $ema9 < $ema21 && $ema21 < $ema50) {
                    $maScore = -100;
                } elseif ($latestClose < $ema21 && $ema21 < $ema50) {
                    $maScore = -60;
                }
            } else {
                if ($latestClose > $ema9 && $ema9 > $ema21) {
                    $maScore = 80;
                } elseif ($latestClose < $ema9 && $ema9 < $ema21) {
                    $maScore = -80;
                }
            }
        }

        // 7. Deterministic Regime Decision Matrix
        $regime = self::REGIME_UNCERTAIN;
        $confidence = 50.0;
        $description = '';

        $isTrendingBullish = ($adx >= 22.0 && $plusDi > $minusDi && $maScore >= 60);
        $isTrendingBearish = ($adx >= 22.0 && $minusDi > $plusDi && $maScore <= -60);
        $isHighVolatility = ($atrPercentile >= 85.0 || ($bandwidth > 4.5 && $adx < 22.0));
        $isTightRange = ($adx < 20.0 && $bandwidth < 2.0 && abs($maScore) < 50);
        $isExpandingRange = ($adx < 22.0 && $bandwidth >= 2.0 && $bandwidth <= 4.5 && abs($maScore) < 50);

        if ($isTrendingBullish) {
            $regime = self::REGIME_TRENDING_BULLISH;
            $confidence = min(95.0, 60.0 + ($adx - 20) * 1.2 + ($maScore === 100 ? 15 : 0));
            $description = "Strong upward directional trend confirmed by aligned EMAs (9 > 21 > 50), dominant +DI ({$plusDi} > {$minusDi}), and ADX at {$adx}.";
        } elseif ($isTrendingBearish) {
            $regime = self::REGIME_TRENDING_BEARISH;
            $confidence = min(95.0, 60.0 + ($adx - 20) * 1.2 + ($maScore === -100 ? 15 : 0));
            $description = "Strong downward directional trend confirmed by inverse EMAs (9 < 21 < 50), dominant -DI ({$minusDi} > {$plusDi}), and ADX at {$adx}.";
        } elseif ($isHighVolatility) {
            $regime = self::REGIME_VOLATILE;
            $confidence = 75.0 + min(15.0, ($atrPercentile - 80) * 0.75);
            $description = "High volatility environment with ATR in the {$atrPercentile}th percentile and elevated Bollinger Bandwidth ({$bandwidth}%). Trend continuity is unstable.";
        } elseif ($isTightRange) {
            $regime = self::REGIME_RANGING_TIGHT;
            $confidence = 80.0 + min(15.0, (20.0 - $adx) * 1.5);
            $description = "Low-volatility consolidation / squeeze. Bollinger Bandwidth is compressed ({$bandwidth}%) and ADX is weak ({$adx}), indicating range-bound oscillation.";
        } elseif ($isExpandingRange) {
            $regime = self::REGIME_RANGING_EXPANDING;
            $confidence = 70.0;
            $description = "Developing range with oscillating swings between key support/resistance boundaries without sustained multi-candle continuation.";
        } else {
            $regime = self::REGIME_UNCERTAIN;
            $confidence = 55.0;
            $description = "Transitional or conflicting market signals. Trend strength (ADX {$adx}) and moving average alignment do not exhibit unified directional consensus.";
        }

        // 8. Map Strategy Suitability Matrix
        $suitability = $this->getSuitabilityForRegime($regime);

        return [
            'regime' => $regime,
            'regime_label' => $this->getRegimeLabel($regime),
            'confidence' => round($confidence, 1),
            'description' => $description,
            'metrics' => [
                'adx' => round($adx, 2),
                'plus_di' => round($plusDi, 2),
                'minus_di' => round($minusDi, 2),
                'atr_14' => round($atr14, 5),
                'atr_percentile' => round($atrPercentile, 1),
                'bollinger_bandwidth' => round($bandwidth, 2),
                'ma_alignment_score' => $maScore,
                'swing_bias' => $structure,
                'ema_9' => $ema9 ? round($ema9, 5) : null,
                'ema_21' => $ema21 ? round($ema21, 5) : null,
                'ema_50' => $ema50 ? round($ema50, 5) : null,
            ],
            'suitable_categories' => $suitability['suitable_categories'],
            'unsuitable_categories' => $suitability['unsuitable_categories'],
            'suitable_strategies' => $suitability['suitable_strategies'],
            'unsuitable_strategies' => $suitability['unsuitable_strategies'],
            'risk_guidance' => $suitability['risk_guidance'],
        ];
    }

    /**
     * Map suitability matrix for a given regime.
     */
    public function getSuitabilityForRegime(string $regime): array
    {
        return match ($regime) {
            self::REGIME_TRENDING_BULLISH, self::REGIME_TRENDING_BEARISH => [
                'suitable_categories' => ['ict_smc', 'price_action', 'quantitative', 'bbma_oma_ally', 'fibonacci'],
                'unsuitable_categories' => ['range_trading', 'mean_reversion'],
                'suitable_strategies' => [
                    'ICT Silver Bullet',
                    'ICT 2022 Mentorship Model',
                    'Break and Retest (Price Action)',
                    'BBMA Oma Ally Re-Entry Continuation',
                    'Fibonacci Golden Zone Retracement (61.8% / 78.6%)',
                    'EMA Ribbon Pullback & Continuation',
                ],
                'unsuitable_strategies' => [
                    'Bollinger Band Mean Reversion & Bounce',
                    'Stochastic Counter-Trend Squeeze',
                ],
                'risk_guidance' => 'Trend-continuation pullbacks favored. Strictly avoid counter-trend fade setups or early top/bottom picking.',
            ],
            self::REGIME_RANGING_TIGHT => [
                'suitable_categories' => ['indicator_based', 'price_action', 'wyckoff'],
                'unsuitable_categories' => ['trend_following', 'momentum_breakout'],
                'suitable_strategies' => [
                    'Bollinger Band Mean Reversion & Bounce',
                    'Wyckoff Accumulation Spring & Distribution UTAD',
                    'RSI Range Extreme Fade',
                ],
                'unsuitable_strategies' => [
                    'EMA Ribbon Pullback & Continuation',
                    'Momentum Breakout Chaser',
                    'ICT Silver Bullet',
                ],
                'risk_guidance' => 'Tight compression preceding high-impact expansion. Mean reversion to range midpoint valid; prepare for breakout invalidation.',
            ],
            self::REGIME_RANGING_EXPANDING => [
                'suitable_categories' => ['price_action', 'fibonacci', 'wyckoff', 'indicator_based'],
                'unsuitable_categories' => ['trend_following'],
                'suitable_strategies' => [
                    'Wyckoff Accumulation Spring & Distribution UTAD',
                    'Fibonacci Golden Zone Retracement (61.8% / 78.6%)',
                    'Liquidity Sweep & Order Block Reversal',
                ],
                'unsuitable_strategies' => [
                    'EMA Ribbon Pullback & Continuation',
                ],
                'risk_guidance' => 'Fade extremes at verified key support/resistance boundaries with strict structural stops. Trailing trend stops will suffer chop.',
            ],
            self::REGIME_VOLATILE => [
                'suitable_categories' => ['quantitative', 'ict_smc'],
                'unsuitable_categories' => ['tight_stop_scalping', 'fixed_pip_stops'],
                'suitable_strategies' => [
                    'ATR Volatility Channel Breakout',
                    'Liquidity Sweep & Order Block Reversal',
                ],
                'unsuitable_strategies' => [
                    'Bollinger Band Mean Reversion & Bounce',
                    'Tight Scalping Models',
                ],
                'risk_guidance' => 'Reduce position size by 40-50% due to expanded ATR. Widen stop loss buffers to avoid slippage and stop sweeps.',
            ],
            default => [
                'suitable_categories' => ['quantitative', 'price_action'],
                'unsuitable_categories' => ['high_leverage_directional'],
                'suitable_strategies' => [
                    'Break and Retest (Price Action)',
                    'ATR Volatility Channel Breakout',
                ],
                'unsuitable_strategies' => [
                    'Counter-Trend Aggressive Reversals',
                ],
                'risk_guidance' => 'Transitional conditions. Wait for structural confirmation (CHOCH / BOS) before committing capital or abstain (WAIT / NO SETUP).',
            ],
        };
    }

    public function getRegimeLabel(string $regime): string
    {
        return match ($regime) {
            self::REGIME_TRENDING_BULLISH   => 'Trending Bullish (Strong Trend)',
            self::REGIME_TRENDING_BEARISH   => 'Trending Bearish (Strong Trend)',
            self::REGIME_RANGING_TIGHT      => 'Ranging Tight (Low Volatility Squeeze)',
            self::REGIME_RANGING_EXPANDING  => 'Ranging Expanding (Swing Consolidation)',
            self::REGIME_VOLATILE           => 'Volatile (High ATR / Wide Fluctuations)',
            default                         => 'Uncertain / Transitional',
        };
    }

    /**
     * Calculate ADX (Average Directional Index) with +DI and -DI.
     */
    public function calculateAdx(array $candles, int $period = 14): array
    {
        $count = count($candles);
        if ($count <= $period + 1) {
            return ['adx' => 15.0, 'plus_di' => 20.0, 'minus_di' => 20.0];
        }

        $trList = [];
        $plusDmList = [];
        $minusDmList = [];

        for ($i = 1; $i < $count; $i++) {
            $current = $candles[$i];
            $prev = $candles[$i - 1];

            $hl = $current['high'] - $current['low'];
            $hpc = abs($current['high'] - $prev['close']);
            $lpc = abs($current['low'] - $prev['close']);
            $trList[] = max($hl, $hpc, $lpc);

            $upMove = $current['high'] - $prev['high'];
            $downMove = $prev['low'] - $current['low'];

            $plusDmList[] = ($upMove > $downMove && $upMove > 0) ? $upMove : 0.0;
            $minusDmList[] = ($downMove > $upMove && $downMove > 0) ? $downMove : 0.0;
        }

        // Wilder smoothed averages
        $trSmoothed = array_sum(array_slice($trList, 0, $period));
        $plusDmSmoothed = array_sum(array_slice($plusDmList, 0, $period));
        $minusDmSmoothed = array_sum(array_slice($minusDmList, 0, $period));

        $dxList = [];
        $latestPlusDi = 20.0;
        $latestMinusDi = 20.0;

        for ($i = $period; $i < count($trList); $i++) {
            $trSmoothed = $trSmoothed - ($trSmoothed / $period) + $trList[$i];
            $plusDmSmoothed = $plusDmSmoothed - ($plusDmSmoothed / $period) + $plusDmList[$i];
            $minusDmSmoothed = $minusDmSmoothed - ($minusDmSmoothed / $period) + $minusDmList[$i];

            $plusDi = ($trSmoothed > 0) ? ($plusDmSmoothed / $trSmoothed) * 100 : 0.0;
            $minusDi = ($trSmoothed > 0) ? ($minusDmSmoothed / $trSmoothed) * 100 : 0.0;

            $latestPlusDi = $plusDi;
            $latestMinusDi = $minusDi;

            $diSum = $plusDi + $minusDi;
            $diDiff = abs($plusDi - $minusDi);
            $dx = ($diSum > 0) ? ($diDiff / $diSum) * 100 : 0.0;
            $dxList[] = $dx;
        }

        if (count($dxList) < $period) {
            $adx = !empty($dxList) ? array_sum($dxList) / count($dxList) : 15.0;
        } else {
            $adx = array_sum(array_slice($dxList, 0, $period)) / $period;
            for ($i = $period; $i < count($dxList); $i++) {
                $adx = (($adx * ($period - 1)) + $dxList[$i]) / $period;
            }
        }

        return [
            'adx' => round($adx, 2),
            'plus_di' => round($latestPlusDi, 2),
            'minus_di' => round($latestMinusDi, 2),
        ];
    }

    /**
     * Calculate ATR percentile relative to historical ATR lookback window.
     */
    protected function calculateAtrPercentile(array $candles, int $atrPeriod = 14, int $lookback = 50): float
    {
        $count = count($candles);
        if ($count < $atrPeriod + 5) {
            return 50.0;
        }

        $atrHistory = [];
        $windowSize = min($count - $atrPeriod, $lookback);
        for ($offset = 0; $offset < $windowSize; $offset++) {
            $slice = array_slice($candles, $count - $windowSize - $atrPeriod + $offset, $atrPeriod + 1);
            $atrHistory[] = $this->calculateAtr($slice, $atrPeriod);
        }

        if (empty($atrHistory)) {
            return 50.0;
        }

        $currentAtr = end($atrHistory);
        $lessCount = 0;
        foreach ($atrHistory as $val) {
            if ($val < $currentAtr) {
                $lessCount++;
            }
        }

        return round(($lessCount / count($atrHistory)) * 100, 1);
    }

    protected function detectSwingBias(array $candles): string
    {
        $count = count($candles);
        if ($count < 6) {
            return 'NEUTRAL';
        }

        $half = (int) floor($count / 2);
        $firstHalf = array_slice($candles, 0, $half);
        $secondHalf = array_slice($candles, $half);

        $firstHigh = max(array_column($firstHalf, 'high'));
        $secondHigh = max(array_column($secondHalf, 'high'));
        $firstLow = min(array_column($firstHalf, 'low'));
        $secondLow = min(array_column($secondHalf, 'low'));

        if ($secondHigh > $firstHigh && $secondLow > $firstLow) {
            return 'BULLISH';
        } elseif ($secondHigh < $firstHigh && $secondLow < $firstLow) {
            return 'BEARISH';
        }

        return 'RANGING';
    }

    protected function calculateAtr(array $candles, int $period = 14): float
    {
        $count = count($candles);
        if ($count < 2) {
            return 0.0;
        }

        $tr = [];
        for ($i = 1; $i < $count; $i++) {
            $h = $candles[$i]['high'];
            $l = $candles[$i]['low'];
            $pc = $candles[$i - 1]['close'];
            $tr[] = max($h - $l, abs($h - $pc), abs($l - $pc));
        }

        if (count($tr) < $period) {
            return !empty($tr) ? array_sum($tr) / count($tr) : 0.0;
        }

        $slice = array_slice($tr, -$period);
        return array_sum($slice) / count($slice);
    }

    protected function calculateBollingerBands(array $closes, int $period = 20, float $multiplier = 2.0): ?array
    {
        if (count($closes) < $period) {
            return null;
        }

        $slice = array_slice($closes, -$period);
        $mean = array_sum($slice) / $period;

        $variance = 0.0;
        foreach ($slice as $val) {
            $variance += pow($val - $mean, 2);
        }
        $stdDev = sqrt($variance / $period);

        return [
            'middle' => round($mean, 5),
            'upper'  => round($mean + ($multiplier * $stdDev), 5),
            'lower'  => round($mean - ($multiplier * $stdDev), 5),
        ];
    }

    protected function calculateEma(array $values, int $period): ?float
    {
        $count = count($values);
        if ($count < $period) {
            return null;
        }

        $k = 2.0 / ($period + 1);
        $ema = array_sum(array_slice($values, 0, $period)) / $period;

        for ($i = $period; $i < $count; $i++) {
            $ema = ($values[$i] * $k) + ($ema * (1 - $k));
        }

        return round($ema, 5);
    }

    protected function calculateSma(array $values, int $period): ?float
    {
        $count = count($values);
        if ($count < $period) {
            return null;
        }

        $slice = array_slice($values, -$period);
        return round(array_sum($slice) / $period, 5);
    }

    protected function fallbackRegime(string $reason): array
    {
        return [
            'regime' => self::REGIME_UNCERTAIN,
            'regime_label' => 'Uncertain (Insufficient Data)',
            'confidence' => 20.0,
            'description' => $reason,
            'metrics' => [
                'adx' => 0.0,
                'plus_di' => 0.0,
                'minus_di' => 0.0,
                'atr_14' => 0.0,
                'atr_percentile' => 50.0,
                'bollinger_bandwidth' => 0.0,
                'ma_alignment_score' => 0,
                'swing_bias' => 'UNKNOWN',
                'ema_9' => null,
                'ema_21' => null,
                'ema_50' => null,
            ],
            'suitable_categories' => [],
            'unsuitable_categories' => [],
            'suitable_strategies' => [],
            'unsuitable_strategies' => [],
            'risk_guidance' => 'Insufficient candlestick data to calculate regime metrics deterministically. Abstain from directional risk.',
        ];
    }
}
