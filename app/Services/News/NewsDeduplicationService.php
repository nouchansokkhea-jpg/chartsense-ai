<?php

namespace App\Services\News;

use App\Models\NewsArticle;

class NewsDeduplicationService
{
    /**
     * Deduplicate and cluster incoming news articles against existing database records.
     *
     * @param array $rawArticles
     * @param string $providerCode
     * @return array Ingested or existing NewsArticle records
     */
    public function ingestAndDeduplicate(array $rawArticles, string $providerCode): array
    {
        $ingested = [];

        foreach ($rawArticles as $raw) {
            $title = trim($raw['title'] ?? '');
            if (empty($title)) continue;

            $clusterHash = $this->generateClusterHash($title);

            // Check if identical canonical_url already exists
            $existing = null;
            if (!empty($raw['canonical_url'])) {
                $existing = NewsArticle::where('canonical_url', $raw['canonical_url'])->first();
            }

            // Check if article with same cluster hash was published in last 12 hours
            if (!$existing) {
                $existing = NewsArticle::where('cluster_hash', $clusterHash)
                    ->where('published_at', '>=', now()->subHours(12))
                    ->first();
            }

            if ($existing) {
                $ingested[] = $existing;
                continue;
            }

            $article = NewsArticle::create([
                'provider_code' => $providerCode,
                'title' => $title,
                'canonical_url' => $raw['canonical_url'] ?? null,
                'summary' => $raw['summary'] ?? null,
                'source_name' => $raw['source_name'] ?? 'Financial Wire',
                'published_at' => $raw['published_at'] ?? now(),
                'received_at' => now(),
                'country_code' => $raw['country_code'] ?? 'GLOBAL',
                'affected_assets' => $raw['affected_assets'] ?? ['GLOBAL_MACRO'],
                'event_category' => $raw['event_category'] ?? 'market_macro',
                'sentiment_classification' => $raw['sentiment'] ?? 'UNCERTAIN',
                'sentiment_score' => $this->sentimentToScore($raw['sentiment'] ?? 'UNCERTAIN'),
                'importance' => $raw['importance'] ?? 'MODERATE',
                'cluster_hash' => $clusterHash,
                'verification_status' => 'reputable_wire',
            ]);

            $ingested[] = $article;
        }

        return $ingested;
    }

    /**
     * Generate normalized cluster hash for headline similarity.
     */
    public function generateClusterHash(string $title): string
    {
        // Lowercase, strip punctuation and stop words
        $clean = strtolower($title);
        $clean = preg_replace('/[^a-z0-9\s]/', '', $clean);
        $words = explode(' ', $clean);

        $stopWords = ['the', 'and', 'for', 'with', 'from', 'in', 'on', 'at', 'to', 'of', 'a', 'an', 'is', 'as', 'by', 'amid', 'signals', 'after'];
        $filtered = array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
        sort($filtered);

        return hash('sha256', implode('_', array_slice($filtered, 0, 8)));
    }

    protected function sentimentToScore(string $sentiment): float
    {
        return match (strtoupper($sentiment)) {
            'BULLISH' => 75.0,
            'BEARISH' => -75.0,
            'MIXED' => 10.0,
            default => 0.0,
        };
    }
}
