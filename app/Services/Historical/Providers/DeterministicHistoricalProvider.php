<?php

namespace App\Services\Historical\Providers;

use App\Services\Historical\Contracts\HistoricalDataProviderInterface;
use Carbon\Carbon;

class DeterministicHistoricalProvider implements HistoricalDataProviderInterface
{
    protected array $basePrices = [
        'EURUSD' => 1.0850,
        'GBPUSD' => 1.2700,
        'USDJPY' => 152.00,
        'AUDUSD' => 0.6650,
        'USDCAD' => 1.3600,
        'USDCHF' => 0.8900,
        'NZDUSD' => 0.6050,
        'XAUUSD' => 2350.00,
        'BTCUSD' => 65000.00,
        'SPX500' => 5200.00,
    ];

    public function getIdentifier(): string
    {
        return 'deterministic_reference';
    }

    public function supports(string $symbol, string $timeframe): bool
    {
        return true;
    }

    public function fetchCandles(string $symbol, string $timeframe, Carbon $startDate, Carbon $endDate): array
    {
        $sym = strtoupper($symbol);
        $tf = strtoupper($timeframe);
        $currentPrice = $this->basePrices[$sym] ?? 100.0;

        $stepMinutes = match ($tf) {
            'M1' => 1,
            'M5' => 5,
            'M15' => 15,
            'M30' => 30,
            'H1' => 60,
            'H4' => 240,
            'D1' => 1440,
            'W1' => 10080,
            default => 1440,
        };

        $volatility = match ($sym) {
            'BTCUSD' => 0.025,
            'XAUUSD' => 0.008,
            'SPX500' => 0.009,
            default  => 0.004,
        };

        // Scale volatility down for smaller timeframes
        $tfScale = sqrt($stepMinutes / 1440);
        $barVol = $volatility * max(0.1, $tfScale);

        $candles = [];
        $cursor = $startDate->copy();

        // Limit maximum bars per fetch to prevent memory exhaustion in single call
        $maxBars = 1500;
        $barCount = 0;

        while ($cursor->lte($endDate) && $barCount < $maxBars) {
            // Check weekend closure for non-crypto
            if ($sym !== 'BTCUSD' && ($cursor->isSaturday() || $cursor->isSunday())) {
                $cursor->addMinutes($stepMinutes);
                continue;
            }

            // Deterministic pseudo-random seed based on symbol and timestamp
            $seed = crc32($sym . $cursor->timestamp);
            mt_srand($seed);

            $randomFactor = (mt_rand(-1000, 1000) / 1000.0) * $barVol;
            $open = $currentPrice;
            $close = $open * (1 + $randomFactor);

            $wickUpper = (mt_rand(10, 300) / 1000.0) * $barVol * $open;
            $wickLower = (mt_rand(10, 300) / 1000.0) * $barVol * $open;

            $high = max($open, $close) + $wickUpper;
            $low = max(0.0001, min($open, $close) - $wickLower);

            $volume = mt_rand(500, 15000);

            $precision = ($sym === 'USDJPY' || $sym === 'XAUUSD' || $sym === 'SPX500') ? 2 : 5;
            if ($sym === 'BTCUSD') {
                $precision = 2;
            }

            $candles[] = [
                'timestamp' => $cursor->toIso8601String(),
                'open' => round($open, $precision),
                'high' => round($high, $precision),
                'low' => round($low, $precision),
                'close' => round($close, $precision),
                'volume' => $volume,
                'volume_type' => 'tick',
                'data_source' => 'deterministic_reference',
            ];

            // Next bar open is this bar's close
            $currentPrice = $close;
            $cursor->addMinutes($stepMinutes);
            $barCount++;
        }

        return $candles;
    }
}
