<?php

namespace App\Services\Historical;

use App\Models\HistoricalMarketCandle;
use App\Models\MarketFeature;
use App\Services\TechnicalAnalysis\MarketRegimeService;
use App\Services\TechnicalAnalysis\TechnicalAnalysisService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketFeatureEngine
{
    public const FEATURE_VERSION = 'v1.0.0';

    protected TechnicalAnalysisService $taService;
    protected MarketRegimeService $regimeService;

    public function __construct(
        TechnicalAnalysisService $taService,
        MarketRegimeService $regimeService
    ) {
        $this->taService = $taService;
        $this->regimeService = $regimeService;
    }

    /**
     * Compute and persist market features for a symbol and timeframe with ZERO look-ahead bias.
     * Every candle feature at timestamp T is calculated exclusively from candles <= T.
     *
     * @param string $symbol
     * @param string $timeframe
     * @param int $batchSize
     * @return array Ingestion stats
     */
    public function computeFeatures(string $symbol, string $timeframe, int $batchSize = 250): array
    {
        $symbol = strtoupper($symbol);
        $timeframe = strtoupper($timeframe);

        $candles = HistoricalMarketCandle::forInstrument($symbol, $timeframe)
            ->verifiedQuality(60)
            ->orderBy('candle_timestamp', 'asc')
            ->get();

        $count = $candles->count();
        if ($count < 20) {
            return [
                'status' => 'skipped',
                'reason' => 'Insufficient candle history for feature extraction (minimum 20 required)',
                'features_created' => 0,
            ];
        }

        $featuresCreated = 0;
        $candlesArray = $candles->toArray();

        // Calculate features progressively through time: index i only has access to candles 0..i
        for ($i = 19; $i < $count; $i++) {
            $slice = array_slice($candlesArray, 0, $i + 1);
            $currentBar = $candlesArray[$i];
            $prevBar = $candlesArray[$i - 1];

            $featureRow = $this->extractFeaturesForSlice($slice, $currentBar, $prevBar, $symbol, $timeframe);

            MarketFeature::updateOrCreate(
                [
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'candle_timestamp' => Carbon::parse($currentBar['candle_timestamp']),
                    'feature_version' => self::FEATURE_VERSION,
                ],
                $featureRow
            );

            $featuresCreated++;
        }

        return [
            'status' => 'completed',
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'feature_version' => self::FEATURE_VERSION,
            'features_created' => $featuresCreated,
        ];
    }

    /**
     * Extract zero-lookahead feature vector for the latest candle in the given historical slice.
     */
    protected function extractFeaturesForSlice(
        array $slice,
        array $currentBar,
        array $prevBar,
        string $symbol,
        string $timeframe
    ): array {
        $closes = array_column($slice, 'close');
        $highs = array_column($slice, 'high');
        $lows = array_column($slice, 'low');
        $sliceCount = count($slice);

        $close = (float)$currentBar['close'];
        $open = (float)$currentBar['open'];
        $high = (float)$currentBar['high'];
        $low = (float)$currentBar['low'];

        // 1. Log return and candle geometry
        $prevClose = (float)$prevBar['close'];
        $logReturn = ($prevClose > 0 && $close > 0) ? log($close / $prevClose) : 0.0;

        $range = max(0.00001, $high - $low);
        $body = abs($close - $open);
        $bodyWickRatio = round($body / $range, 4);

        // 2. Technical Indicators strictly over past slice
        $rsi14 = $this->calculateRsi($closes, 14);
        $atr14 = $this->calculateAtr($slice, 14);
        $ema20 = $this->calculateEma($closes, 20);
        $ema50 = $this->calculateEma($closes, min(50, $sliceCount));
        $ema200 = $sliceCount >= 200 ? $this->calculateEma($closes, 200) : null;

        $macd = $this->calculateMacd($closes, 12, 26, 9);

        // 3. Backward-confirmed Swing Pivots (NO future candles used)
        // A swing high at index k is confirmed at bar i if i >= k + 2
        $swingHigh = null;
        $swingLow = null;
        for ($k = $sliceCount - 3; $k >= max(2, $sliceCount - 25); $k--) {
            $kHigh = (float)$slice[$k]['high'];
            $kLow = (float)$slice[$k]['low'];

            // Check if k was higher than its 2 preceding and 2 succeeding bars (which are <= i)
            if (
                $kHigh > (float)$slice[$k - 1]['high'] &&
                $kHigh > (float)$slice[$k - 2]['high'] &&
                $kHigh > (float)$slice[$k + 1]['high'] &&
                $kHigh > (float)$slice[$k + 2]['high'] &&
                $swingHigh === null
            ) {
                $swingHigh = $kHigh;
            }

            if (
                $kLow < (float)$slice[$k - 1]['low'] &&
                $kLow < (float)$slice[$k - 2]['low'] &&
                $kLow < (float)$slice[$k + 1]['low'] &&
                $kLow < (float)$slice[$k + 2]['low'] &&
                $swingLow === null
            ) {
                $swingLow = $kLow;
            }

            if ($swingHigh !== null && $swingLow !== null) {
                break;
            }
        }

        // 4. Fair Value Gap (FVG) candidate strictly using bars i-2, i-1, i
        $isFvgCandidate = false;
        if ($sliceCount >= 3) {
            $bar1 = $slice[$sliceCount - 3];
            $bar3 = $currentBar;
            // Bullish FVG: Bar 1 High < Bar 3 Low
            // Bearish FVG: Bar 1 Low > Bar 3 High
            if ((float)$bar1['high'] < (float)$bar3['low'] || (float)$bar1['low'] > (float)$bar3['high']) {
                $isFvgCandidate = true;
            }
        }

        // 5. Order Block candidate
        // Previous bar is opposite direction and current bar has large body displacement > 1.5 * ATR
        $isOrderBlockCandidate = false;
        if ($atr14 && $range > (1.3 * $atr14)) {
            $currentBullish = $close > $open;
            $prevBullish = (float)$prevBar['close'] > (float)$prevBar['open'];
            if ($currentBullish !== $prevBullish) {
                $isOrderBlockCandidate = true;
            }
        }

        // 6. Liquidity Sweep candidate: Price swept past recent swing high/low then closed back inside
        $isLiquiditySweep = false;
        if ($swingHigh && $high > $swingHigh && $close < $swingHigh) {
            $isLiquiditySweep = true;
        } elseif ($swingLow && $low < $swingLow && $close > $swingLow) {
            $isLiquiditySweep = true;
        }

        // 7. Market Regime from backward slice
        $regimeResult = $this->regimeService->classify($slice);
        $marketRegime = $regimeResult['regime'] ?? 'UNCERTAIN';

        return [
            'log_return' => round($logReturn, 6),
            'body_wick_ratio' => $bodyWickRatio,
            'atr_14' => $atr14 ? round($atr14, 5) : null,
            'trend_strength_adx' => round($regimeResult['metrics']['adx'] ?? 20.0, 2),
            'rsi_14' => $rsi14 ? round($rsi14, 2) : null,
            'macd_line' => $macd['macd'] ? round($macd['macd'], 5) : null,
            'macd_signal' => $macd['signal'] ? round($macd['signal'], 5) : null,
            'ema_20' => $ema20 ? round($ema20, 5) : null,
            'ema_50' => $ema50 ? round($ema50, 5) : null,
            'ema_200' => $ema200 ? round($ema200, 5) : null,
            'swing_high' => $swingHigh,
            'swing_low' => $swingLow,
            'order_block_candidate' => $isOrderBlockCandidate,
            'fvg_candidate' => $isFvgCandidate,
            'liquidity_sweep_candidate' => $isLiquiditySweep,
            'market_regime' => $marketRegime,
            'features_json' => [
                'version' => self::FEATURE_VERSION,
                'regime_label' => $regimeResult['regime_label'] ?? 'Uncertain',
                'regime_confidence' => $regimeResult['confidence'] ?? 0.5,
                'bar_range' => round($range, 5),
                'bar_body' => round($body, 5),
            ],
        ];
    }

    protected function calculateRsi(array $closes, int $period = 14): ?float
    {
        $count = count($closes);
        if ($count <= $period) return null;

        $gains = [];
        $losses = [];
        for ($i = 1; $i < $count; $i++) {
            $diff = $closes[$i] - $closes[$i - 1];
            $gains[] = max(0.0, $diff);
            $losses[] = max(0.0, -$diff);
        }

        $avgGain = array_sum(array_slice($gains, -$period)) / $period;
        $avgLoss = array_sum(array_slice($losses, -$period)) / $period;

        if ($avgLoss == 0) return 100.0;
        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    protected function calculateAtr(array $candles, int $period = 14): ?float
    {
        $count = count($candles);
        if ($count < 2) return null;

        $trs = [];
        for ($i = 1; $i < $count; $i++) {
            $h = (float)$candles[$i]['high'];
            $l = (float)$candles[$i]['low'];
            $pc = (float)$candles[$i - 1]['close'];
            $trs[] = max($h - $l, abs($h - $pc), abs($l - $pc));
        }

        $slice = array_slice($trs, -$period);
        return count($slice) > 0 ? (array_sum($slice) / count($slice)) : null;
    }

    protected function calculateEma(array $values, int $period): ?float
    {
        $count = count($values);
        if ($count < $period) return null;

        $k = 2 / ($period + 1);
        $ema = array_sum(array_slice($values, 0, $period)) / $period;

        for ($i = $period; $i < $count; $i++) {
            $ema = ($values[$i] * $k) + ($ema * (1 - $k));
        }

        return $ema;
    }

    protected function calculateMacd(array $closes, int $fast = 12, int $slow = 26, int $signal = 9): array
    {
        $fastEma = $this->calculateEma($closes, $fast);
        $slowEma = $this->calculateEma($closes, $slow);

        if ($fastEma === null || $slowEma === null) {
            return ['macd' => null, 'signal' => null];
        }

        $macd = $fastEma - $slowEma;
        // Approximation of signal from current macd
        $signalVal = $macd * (2 / ($signal + 1));

        return ['macd' => $macd, 'signal' => $signalVal];
    }
}
