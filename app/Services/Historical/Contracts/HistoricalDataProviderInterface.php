<?php

namespace App\Services\Historical\Contracts;

use Carbon\Carbon;

interface HistoricalDataProviderInterface
{
    /**
     * Get the provider's unique identifier.
     */
    public function getIdentifier(): string;

    /**
     * Fetch historical candle records for an instrument between two dates.
     *
     * @param string $symbol e.g. EURUSD, XAUUSD, BTCUSD
     * @param string $timeframe e.g. M15, H1, H4, D1
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array Array of raw candle associative arrays [timestamp, open, high, low, close, volume]
     */
    public function fetchCandles(string $symbol, string $timeframe, Carbon $startDate, Carbon $endDate): array;

    /**
     * Check if provider supports the requested symbol and timeframe.
     */
    public function supports(string $symbol, string $timeframe): bool;
}
