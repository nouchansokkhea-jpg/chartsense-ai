<?php

namespace App\Services\News;

use App\Models\NewsArticle;
use App\Models\NewsProvider;
use App\Services\News\Contracts\NewsProviderInterface;
use App\Services\News\Providers\ForexFactoryNewsProvider;
use App\Services\News\Providers\InstitutionalCalendarProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GlobalNewsAggregatorService
{
    public function __construct(
        protected NewsDeduplicationService $dedupService,
        protected ?InstitutionalCalendarProvider $calendarProvider = null,
        protected ?ForexFactoryNewsProvider $forexFactoryProvider = null
    ) {
        $this->calendarProvider = $calendarProvider ?? app(InstitutionalCalendarProvider::class);
        $this->forexFactoryProvider = $forexFactoryProvider ?? app(ForexFactoryNewsProvider::class);
    }

    /**
     * Known global financial news RSS & direct wire feeds.
     */
    protected array $globalFeedConfigs = [
        'forexfactory' => [
            'name' => 'Forex Factory Live Real-Time News & Release Wire',
            'url' => 'https://www.forexfactory.com/news',
            'type' => 'forexfactory',
            'source_name' => 'Forex Factory',
            'default_category' => 'market_macro',
        ],
        'forexlive' => [
            'name' => 'ForexLive Global Macro Feed',
            'url' => 'https://www.forexlive.com/feed/news',
            'type' => 'rss',
            'source_name' => 'ForexLive',
            'default_category' => 'market_macro',
        ],
        'investing_live' => [
            'name' => 'InvestingLive Breaking Wire',
            'url' => 'https://investinglive.com/feed/',
            'type' => 'rss',
            'source_name' => 'InvestingLive',
            'default_category' => 'market_macro',
        ],
        'cnbc_markets' => [
            'name' => 'CNBC Global Markets & Rates',
            'url' => 'https://www.cnbc.com/id/10000664/device/rss/rss.html',
            'type' => 'rss',
            'source_name' => 'CNBC Markets',
            'default_category' => 'market_macro',
        ],
        'bbc_business' => [
            'name' => 'BBC World Business & Trade',
            'url' => 'https://feeds.bbci.co.uk/news/business/rss.xml',
            'type' => 'rss',
            'source_name' => 'BBC World Business',
            'default_category' => 'geopolitical',
        ],
        'marketwatch' => [
            'name' => 'MarketWatch Dow Jones Macro',
            'url' => 'https://feeds.content.dowjones.io/public/rss/mw_topstories',
            'type' => 'rss',
            'source_name' => 'MarketWatch',
            'default_category' => 'market_macro',
        ],
        'coindesk' => [
            'name' => 'CoinDesk Digital Assets & Liquidity',
            'url' => 'https://www.coindesk.com/arc/outboundfeeds/rss/',
            'type' => 'rss',
            'source_name' => 'CoinDesk',
            'default_category' => 'crypto',
        ],
    ];

    /**
     * Synchronize live news articles from all active global providers.
     *
     * @param bool $force Force sync even if synced within the last minute
     * @param int $maxPerFeed Max articles to inspect per feed
     * @return array Sync telemetry report
     */
    public function syncAll(bool $force = false, int $maxPerFeed = 40): array
    {
        $startTime = microtime(true);
        $totalFetched = 0;
        $newInserted = 0;
        $providerReports = [];

        // Ensure global providers exist in database
        $this->ensureProvidersRegistered();

        // Count existing articles before sync
        $beforeCount = NewsArticle::count();

        foreach ($this->globalFeedConfigs as $code => $config) {
            $providerModel = NewsProvider::where('code', $code)->first();
            if ($providerModel && !$providerModel->is_active) {
                continue;
            }

            // Rate-limit safety: skip if synced less than 30s ago unless forced
            if (!$force && $providerModel?->last_sync_at && $providerModel->last_sync_at->diffInSeconds(now()) < 30) {
                $providerReports[$code] = [
                    'status' => 'skipped_throttled',
                    'message' => 'Synced recently (' . $providerModel->last_sync_at->diffForHumans() . ')',
                ];
                continue;
            }

            $feedStart = microtime(true);
            try {
                if (($config['type'] ?? '') === 'forexfactory') {
                    $rawArticles = $this->forexFactoryProvider->fetchArticles('forex');
                    $latency = (int)round((microtime(true) - $feedStart) * 1000);
                } else {
                    \App\Services\Security\SsrfProtectionService::validateUrl($config['url']);
                    $response = Http::timeout(5)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                            'Accept' => 'application/rss+xml, application/xml, text/xml, */*',
                        ])
                        ->get($config['url']);

                    $latency = (int)round((microtime(true) - $feedStart) * 1000);

                    if (!$response->successful()) {
                        $this->updateProviderStatus($code, 'degraded', "HTTP {$response->status()}", $latency);
                        $providerReports[$code] = [
                            'status' => 'failed_http',
                            'code' => $response->status(),
                        ];
                        continue;
                    }

                    $rawArticles = $this->parseRssFeed($response->body(), $config, $maxPerFeed);
                }

                $totalFetched += count($rawArticles);

                if (!empty($rawArticles)) {
                    $ingested = $this->dedupService->ingestAndDeduplicate($rawArticles, $code);
                    $this->updateProviderStatus($code, 'healthy', 'Sync successful. Ingested ' . count($ingested) . ' items.', $latency);
                    $providerReports[$code] = [
                        'status' => 'synced',
                        'fetched' => count($rawArticles),
                        'latency_ms' => $latency,
                    ];
                } else {
                    $this->updateProviderStatus($code, 'healthy', 'Feed returned 0 items.', $latency);
                    $providerReports[$code] = [
                        'status' => 'empty',
                        'latency_ms' => $latency,
                    ];
                }
            } catch (\Throwable $e) {
                $latency = (int)round((microtime(true) - $feedStart) * 1000);
                $this->updateProviderStatus($code, 'offline', $e->getMessage(), $latency);
                $providerReports[$code] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
                Log::warning("GlobalNewsAggregatorService [{$code}] sync error: " . $e->getMessage());
            }
        }

        $afterCount = NewsArticle::count();
        $newInserted = max(0, $afterCount - $beforeCount);

        // Also refresh upcoming economic calendar events if needed
        $this->syncCalendarEvents();

        $durationMs = (int)round((microtime(true) - $startTime) * 1000);

        return [
            'success' => true,
            'total_fetched' => $totalFetched,
            'new_articles_inserted' => $newInserted,
            'total_articles_in_db' => $afterCount,
            'duration_ms' => $durationMs,
            'providers' => $providerReports,
            'synced_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Parse standard RSS / XML feed into normalized article schema.
     */
    protected function parseRssFeed(string $xmlContent, array $config, int $max = 40): array
    {
        $articles = [];
        $xml = @simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);

        if (!$xml) {
            return [];
        }

        $items = [];
        if (isset($xml->channel->item)) {
            $items = $xml->channel->item;
        } elseif (isset($xml->item)) {
            $items = $xml->item;
        } elseif (isset($xml->entry)) { // Atom feed support
            $items = $xml->entry;
        }

        $count = 0;
        foreach ($items as $item) {
            if ($count >= $max) break;

            $title = trim(html_entity_decode((string)($item->title ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (empty($title)) continue;

            $link = trim((string)($item->link ?? ''));
            if (empty($link) && isset($item->link['href'])) {
                $link = (string)$item->link['href'];
            }

            // PubDate parsing
            $rawDate = (string)($item->pubDate ?? $item->published ?? $item->updated ?? '');
            $pubDate = !empty($rawDate) ? Carbon::parse($rawDate)->utc() : now()->utc();

            // Description / snippet
            $rawDesc = (string)($item->description ?? $item->summary ?? $item->content ?? '');
            $desc = trim(strip_tags(html_entity_decode($rawDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            if (strlen($desc) > 350) {
                $desc = substr($desc, 0, 347) . '...';
            }

            $articles[] = [
                'title' => $title,
                'canonical_url' => $link ?: null,
                'summary' => $desc ?: $title,
                'source_name' => $config['source_name'],
                'published_at' => $pubDate->toDateTimeString(),
                'country_code' => $this->inferCountry($title),
                'affected_assets' => $this->inferAssets($title),
                'event_category' => $this->inferCategory($title, $config['default_category']),
                'importance' => $this->inferImportance($title),
                'sentiment' => $this->scoreSentiment($title . ' ' . $desc),
            ];

            $count++;
        }

        return $articles;
    }

    /**
     * Infer country / geopolitical origin from title.
     */
    protected function inferCountry(string $title): string
    {
        $t = strtoupper($title);
        if (str_contains($t, 'FED') || str_contains($t, 'DOLLAR') || str_contains($t, 'US ') || str_contains($t, 'U.S.') || str_contains($t, 'POWELL') || str_contains($t, 'WALL ST') || str_contains($t, 'TREASURY') || str_contains($t, 'NFP') || str_contains($t, 'CPI') || str_contains($t, 'BIDEN') || str_contains($t, 'TRUMP')) return 'US';
        if (str_contains($t, 'ECB') || str_contains($t, 'EURO') || str_contains($t, 'LAGARDE') || str_contains($t, 'GERMANY') || str_contains($t, 'FRANCE') || str_contains($t, 'ITALY')) return 'EU';
        if (str_contains($t, 'BOE') || str_contains($t, 'POUND') || str_contains($t, 'UK ') || str_contains($t, 'BRITISH') || str_contains($t, 'BAILEY') || str_contains($t, 'ENGLAND')) return 'GB';
        if (str_contains($t, 'BOJ') || str_contains($t, 'YEN') || str_contains($t, 'JAPAN') || str_contains($t, 'UEDA') || str_contains($t, 'TOKYO')) return 'JP';
        if (str_contains($t, 'CHINA') || str_contains($t, 'PBOC') || str_contains($t, 'YUAN') || str_contains($t, 'BEIJING') || str_contains($t, 'XI ')) return 'CN';
        if (str_contains($t, 'RBA') || str_contains($t, 'AUSTRALIA') || str_contains($t, 'AUD')) return 'AU';
        if (str_contains($t, 'BOC') || str_contains($t, 'CANADA') || str_contains($t, 'CAD')) return 'CA';
        if (str_contains($t, 'SNB') || str_contains($t, 'SWISS') || str_contains($t, 'FRANC')) return 'CH';
        if (str_contains($t, 'MIDDLE EAST') || str_contains($t, 'ISRAEL') || str_contains($t, 'IRAN') || str_contains($t, 'RUSSIA') || str_contains($t, 'UKRAINE') || str_contains($t, 'OPEC')) return 'GEOPOLITICAL';
        return 'GLOBAL';
    }

    /**
     * Infer affected financial assets from text.
     */
    protected function inferAssets(string $title): array
    {
        $t = strtoupper($title);
        $assets = [];

        if (str_contains($t, 'GOLD') || str_contains($t, 'XAU') || str_contains($t, 'PRECIOUS METAL') || str_contains($t, 'SILVER')) {
            $assets[] = 'XAUUSD';
        }
        if (str_contains($t, 'OIL') || str_contains($t, 'CRUDE') || str_contains($t, 'BRENT') || str_contains($t, 'WTI') || str_contains($t, 'OPEC') || str_contains($t, 'ENERGY')) {
            $assets[] = 'USOIL';
        }
        if (str_contains($t, 'DOLLAR') || str_contains($t, 'DXY') || str_contains($t, 'FED') || str_contains($t, 'TREASURY') || str_contains($t, 'YIELD') || str_contains($t, 'INFLATION') || str_contains($t, 'NFP') || str_contains($t, 'POWELL')) {
            $assets[] = 'USD';
        }
        if (str_contains($t, 'EUR') || str_contains($t, 'ECB') || str_contains($t, 'EUROPE')) {
            $assets[] = 'EURUSD';
        }
        if (str_contains($t, 'GBP') || str_contains($t, 'POUND') || str_contains($t, 'BOE') || str_contains($t, 'STERLING')) {
            $assets[] = 'GBPUSD';
        }
        if (str_contains($t, 'JPY') || str_contains($t, 'YEN') || str_contains($t, 'BOJ')) {
            $assets[] = 'USDJPY';
        }
        if (str_contains($t, 'BITCOIN') || str_contains($t, 'BTC') || str_contains($t, 'CRYPTO') || str_contains($t, 'ETHEREUM') || str_contains($t, 'ETH')) {
            $assets[] = 'BTCUSD';
        }
        if (str_contains($t, 'S&P') || str_contains($t, 'NASDAQ') || str_contains($t, 'DOW') || str_contains($t, 'WALL STREET') || str_contains($t, 'EQUITIES')) {
            $assets[] = 'US30';
        }

        return empty($assets) ? ['GLOBAL_MACRO'] : array_unique($assets);
    }

    /**
     * Infer news category.
     */
    protected function inferCategory(string $title, string $default): string
    {
        $t = strtolower($title);
        if (str_contains($t, 'fed') || str_contains($t, 'ecb') || str_contains($t, 'boj') || str_contains($t, 'boe') || str_contains($t, 'interest rate') || str_contains($t, 'rate cut') || str_contains($t, 'rate hike') || str_contains($t, 'central bank') || str_contains($t, 'fomc')) {
            return 'central_bank';
        }
        if (str_contains($t, 'gold') || str_contains($t, 'oil') || str_contains($t, 'crude') || str_contains($t, 'energy') || str_contains($t, 'silver') || str_contains($t, 'copper')) {
            return 'commodities';
        }
        if (str_contains($t, 'bitcoin') || str_contains($t, 'crypto') || str_contains($t, 'ethereum') || str_contains($t, 'token') || str_contains($t, 'binance')) {
            return 'crypto';
        }
        if (str_contains($t, 'war') || str_contains($t, 'military') || str_contains($t, 'tariff') || str_contains($t, 'sanctions') || str_contains($t, 'election') || str_contains($t, 'missile') || str_contains($t, 'geopolitic')) {
            return 'geopolitical';
        }
        return $default;
    }

    /**
     * Infer importance severity.
     */
    protected function inferImportance(string $title): string
    {
        $t = strtolower($title);
        if (str_contains($t, 'fomc') || str_contains($t, 'cpi') || str_contains($t, 'nfp') || str_contains($t, 'non-farm') || str_contains($t, 'rate decision') || str_contains($t, 'emergency') || str_contains($t, 'war declared') || str_contains($t, 'flash crash') || str_contains($t, 'debt default')) {
            return 'CRITICAL';
        }
        if (str_contains($t, 'powell') || str_contains($t, 'gdp') || str_contains($t, 'pmi') || str_contains($t, 'ppi') || str_contains($t, 'retail sales') || str_contains($t, 'unemployment') || str_contains($t, 'lagarde') || str_contains($t, 'rate cut') || str_contains($t, 'rate hike') || str_contains($t, 'opec meeting')) {
            return 'HIGH';
        }
        if (str_contains($t, 'earnings') || str_contains($t, 'trade balance') || str_contains($t, 'consumer confidence') || str_contains($t, 'yield') || str_contains($t, 'rally') || str_contains($t, 'plunge')) {
            return 'MODERATE';
        }
        return 'LOW';
    }

    /**
     * Compute sentiment classification.
     */
    protected function scoreSentiment(string $text): string
    {
        $bullish = ['surge', 'jump', 'gain', 'rally', 'boost', 'rise', 'higher', 'beat', 'cut', 'optimism', 'dovish', 'rebound', 'strong', 'growth', 'easing', 'stimulus', 'record'];
        $bearish = ['drop', 'fall', 'plunge', 'decline', 'lower', 'miss', 'hike', 'slump', 'weak', 'hawkish', 'crash', 'sell-off', 'loss', 'recession', 'contraction', 'tightening', 'fears', 'warning'];

        $b = 0; $s = 0;
        $t = strtolower($text);
        foreach ($bullish as $w) if (str_contains($t, $w)) $b++;
        foreach ($bearish as $w) if (str_contains($t, $w)) $s++;

        if ($b > $s && $b >= 1) return 'BULLISH';
        if ($s > $b && $s >= 1) return 'BEARISH';
        if ($b === $s && $b > 0) return 'MIXED';
        return 'UNCERTAIN';
    }

    /**
     * Update health and sync state of provider in database.
     */
    protected function updateProviderStatus(string $code, string $status, ?string $message, int $latency): void
    {
        NewsProvider::updateOrCreate(
            ['code' => $code],
            [
                'health_status' => $status,
                'health_message' => $message,
                'latency_ms' => $latency,
                'last_sync_at' => now(),
            ]
        );
    }

    /**
     * Register any new providers in the database if not already present.
     */
    protected function ensureProvidersRegistered(): void
    {
        foreach ($this->globalFeedConfigs as $code => $config) {
            NewsProvider::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $config['name'],
                    'type' => $config['type'],
                    'base_url' => $config['url'],
                    'is_active' => true,
                    'rate_limit_per_minute' => 60,
                    'health_status' => 'healthy',
                ]
            );
        }
    }

    /**
     * Sync economic calendar events if needed.
     */
    protected function syncCalendarEvents(): void
    {
        try {
            if ($this->calendarProvider) {
                $events = $this->calendarProvider->fetchUpcomingEvents(48);
                // Ensure calendar provider health is logged
                NewsProvider::where('code', 'calendar_feed')->update([
                    'last_sync_at' => now(),
                    'health_status' => 'healthy',
                ]);
            }
        } catch (\Throwable $e) {
            Log::info("Calendar sync notice: " . $e->getMessage());
        }
    }
}
