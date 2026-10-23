<?php

namespace App\Services\Historical;

use Carbon\Carbon;

class DataQualityValidator
{
    /**
     * Validate an individual candle against mathematical and market structure rules.
     *
     * @param array $candle ['timestamp', 'open', 'high', 'low', 'close', 'volume', ...]
     * @param array|null $previousCandle
     * @return array ['valid' => bool, 'quality_score' => int, 'issues' => array, 'trading_session' => string]
     */
    public function validateCandle(array $candle, ?array $previousCandle = null): array
    {
        $issues = [];
        $qualityScore = 100;

        $open = (float)($candle['open'] ?? 0);
        $high = (float)($candle['high'] ?? 0);
        $low = (float)($candle['low'] ?? 0);
        $close = (float)($candle['close'] ?? 0);
        $volume = isset($candle['volume']) ? (float)$candle['volume'] : null;

        // 1. Basic Positivity and Non-Zero Check
        if ($open <= 0 || $high <= 0 || $low <= 0 || $close <= 0) {
            $issues[] = 'Zero or negative price values detected';
            $qualityScore -= 40;
        }

        // 2. High / Low Bounds Consistency
        if ($high < $low) {
            $issues[] = "High price ({$high}) is lower than low price ({$low})";
            $qualityScore -= 50;
        }

        if ($high < $open || $high < $close) {
            $issues[] = "High price ({$high}) is less than Open ({$open}) or Close ({$close})";
            $qualityScore -= 30;
        }

        if ($low > $open || $low > $close) {
            $issues[] = "Low price ({$low}) is greater than Open ({$open}) or Close ({$close})";
            $qualityScore -= 30;
        }

        // 3. Extreme Discontinuity / Spike Check against previous candle
        if ($previousCandle && !empty($previousCandle['close']) && $previousCandle['close'] > 0) {
            $prevClose = (float)$previousCandle['close'];
            $gapPct = abs($open - $prevClose) / $prevClose;

            if ($gapPct > 0.15) { // Greater than 15% overnight / weekend jump
                $issues[] = sprintf('Extreme price gap between bars: %.2f%% jump', $gapPct * 100);
                $qualityScore -= 15;
            }
        }

        // 4. Volume Verification
        if ($volume !== null && $volume < 0) {
            $issues[] = "Negative volume recorded ({$volume})";
            $qualityScore -= 20;
        }

        $qualityScore = max(0, min(100, $qualityScore));
        $valid = $qualityScore >= 60 && empty(array_filter($issues, fn($i) => str_contains($i, 'is lower than low')));

        // Determine Trading Session based on UTC timestamp
        $session = $this->determineSession($candle['timestamp'] ?? null);

        return [
            'valid' => $valid,
            'quality_score' => $qualityScore,
            'issues' => $issues,
            'trading_session' => $session,
        ];
    }

    /**
     * Detect gaps between consecutive timestamps for a given timeframe.
     *
     * @param Carbon $prevTimestamp
     * @param Carbon $currTimestamp
     * @param string $timeframe
     * @return bool
     */
    public function isGap(Carbon $prevTimestamp, Carbon $currTimestamp, string $timeframe): bool
    {
        $expectedIntervalSeconds = $this->getExpectedIntervalSeconds($timeframe);
        $diffSeconds = $currTimestamp->diffInSeconds($prevTimestamp);

        // Allow 2.5x the expected interval before flagging a gap (to account for weekends in daily candles)
        if ($timeframe === 'D1') {
            return $diffSeconds > (86400 * 3.5); // More than Friday -> Monday
        }

        return $diffSeconds > ($expectedIntervalSeconds * 2.2);
    }

    /**
     * Assign UTC trading session.
     */
    public function determineSession(?string $timestamp): string
    {
        if (!$timestamp) {
            return 'unknown';
        }

        try {
            $hour = Carbon::parse($timestamp)->utc()->hour;

            if ($hour >= 12 && $hour < 16) {
                return 'overlap'; // London / New York overlap (highest liquidity)
            } elseif ($hour >= 7 && $hour < 16) {
                return 'london';
            } elseif ($hour >= 12 && $hour < 21) {
                return 'new_york';
            } elseif ($hour >= 0 && $hour < 9) {
                return 'asian';
            }

            return 'off_peak';
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    /**
     * Map timeframe string to seconds.
     */
    public function getExpectedIntervalSeconds(string $timeframe): int
    {
        return match (strtoupper($timeframe)) {
            'M1' => 60,
            'M5' => 300,
            'M15' => 900,
            'M30' => 1800,
            'H1' => 3600,
            'H4' => 14400,
            'D1' => 86400,
            'W1' => 604800,
            default => 86400,
        };
    }
}
