<?php

namespace App\Services\News\Contracts;

interface NewsProviderInterface
{
    /**
     * Get unique provider identifier code.
     */
    public function getProviderCode(): string;

    /**
     * Fetch news articles relevant to the asset class (forex, commodities, indices, crypto).
     *
     * @param string $assetClass
     * @return array<int, array{
     *     title: string,
     *     canonical_url: ?string,
     *     summary: ?string,
     *     source_name: string,
     *     published_at: string,
     *     country_code: ?string,
     *     affected_assets: array,
     *     importance: string,
     *     sentiment: string
     * }>
     */
    public function fetchArticles(string $assetClass): array;

    /**
     * Check provider connectivity, health, and latency.
     *
     * @return array{status: string, latency_ms: int, message: ?string}
     */
    public function checkHealth(): array;
}
