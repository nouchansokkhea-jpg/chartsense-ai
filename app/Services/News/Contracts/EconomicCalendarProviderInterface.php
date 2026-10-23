<?php

namespace App\Services\News\Contracts;

interface EconomicCalendarProviderInterface
{
    /**
     * Get unique provider identifier code.
     */
    public function getProviderCode(): string;

    /**
     * Fetch economic calendar events between two ISO dates.
     *
     * @param string $startDate e.g. '2026-10-23'
     * @param string $endDate e.g. '2026-10-30'
     * @return array<int, array{
     *     event_name: string,
     *     country_code: string,
     *     currency: string,
     *     scheduled_at: string,
     *     importance: string,
     *     previous_value: ?string,
     *     forecast_value: ?string,
     *     actual_value: ?string,
     *     revised_value: ?string,
     *     unit: ?string,
     *     official_source: ?string
     * }>
     */
    public function fetchEvents(string $startDate, string $endDate): array;

    /**
     * Check provider health and latency.
     *
     * @return array{status: string, latency_ms: int, message: ?string}
     */
    public function checkHealth(): array;
}
