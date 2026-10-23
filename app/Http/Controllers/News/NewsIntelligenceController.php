<?php

namespace App\Http\Controllers\News;

use App\Http\Controllers\Controller;
use App\Models\EconomicCalendarEvent;
use App\Models\NewsArticle;
use App\Models\NewsProvider;
use App\Models\TradeRestriction;
use App\Services\News\GlobalNewsContextService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsIntelligenceController extends Controller
{
    public function __construct(
        protected GlobalNewsContextService $newsContextService,
        protected \App\Services\News\GlobalNewsAggregatorService $newsAggregator
    ) {}

    public function index(Request $request): View
    {
        $symbol = strtoupper($request->input('symbol', 'XAUUSD'));
        $assetFilter = strtolower($request->input('asset', 'all'));

        // Auto-refresh: If database has no fresh articles in last 2 minutes, trigger sync
        $latestArticle = NewsArticle::orderByDesc('id')->first();
        if (!$latestArticle || $latestArticle->created_at->diffInMinutes(now()) >= 2) {
            try {
                $this->newsAggregator->syncAll(false);
            } catch (\Throwable $e) {
                // Non-blocking fallback
            }
        }

        // 1. Snapshot and Market Risk Gauge
        $contextSnapshot = $this->newsContextService->buildNewsContextSnapshot($symbol);

        // 2. Upcoming High-Impact Economic Events (Next 48 Hours)
        $now = now()->utc();
        $eventsQuery = EconomicCalendarEvent::where('scheduled_at', '>=', $now->copy()->subHours(6))
            ->where('scheduled_at', '<=', $now->copy()->addHours(48))
            ->orderBy('scheduled_at');

        if ($request->filled('importance')) {
            $eventsQuery->where('importance', strtoupper($request->importance));
        }

        $upcomingEvents = $eventsQuery->limit(25)->get();

        // 3. News Articles
        $articlesQuery = NewsArticle::orderByDesc('published_at');
        if ($assetFilter !== 'all') {
            $articlesQuery->where(function ($q) use ($assetFilter) {
                $q->where('event_category', $assetFilter)
                  ->orWhereJsonContains('affected_assets', strtoupper($assetFilter));
            });
        }
        $recentNews = $articlesQuery->paginate(15)->withQueryString();

        // 4. Active Trade Restrictions (News Risk Firewall)
        $activeRestrictions = TradeRestriction::where('expires_at', '>', $now)
            ->with('economicEvent')
            ->orderByDesc('activated_at')
            ->get();

        // 5. News Provider Health Status
        $providers = NewsProvider::all();

        return view('news.index', compact(
            'contextSnapshot',
            'upcomingEvents',
            'recentNews',
            'activeRestrictions',
            'providers',
            'symbol',
            'assetFilter'
        ));
    }

    /**
     * Real-time live polling API endpoint for live UI streaming.
     */
    public function liveStream(Request $request): \Illuminate\Http\JsonResponse
    {
        $symbol = strtoupper($request->input('symbol', 'XAUUSD'));
        $assetFilter = strtolower($request->input('asset', 'all'));
        $afterId = (int)$request->input('after_id', 0);

        // Auto-sync if last article was ingested more than 60s ago
        $latestArticle = NewsArticle::orderByDesc('id')->first();
        if (!$latestArticle || $latestArticle->created_at->diffInSeconds(now()) >= 60) {
            try {
                $this->newsAggregator->syncAll(false);
            } catch (\Throwable $e) {
                // Non-blocking
            }
        }

        // Query new articles published after afterId
        $newArticlesQuery = NewsArticle::orderByDesc('published_at');
        if ($afterId > 0) {
            $newArticlesQuery->where('id', '>', $afterId);
        }

        if ($assetFilter !== 'all') {
            $newArticlesQuery->where(function ($q) use ($assetFilter) {
                $q->where('event_category', $assetFilter)
                  ->orWhereJsonContains('affected_assets', strtoupper($assetFilter));
            });
        }

        $newArticles = $newArticlesQuery->limit(20)->get()->map(function ($a) {
            return [
                'id' => $a->id,
                'title' => $a->title,
                'canonical_url' => $a->canonical_url,
                'summary' => $a->summary,
                'source_name' => $a->source_name,
                'published_at' => $a->published_at->toIso8601String(),
                'time_ago' => $a->published_at->diffForHumans(),
                'country_code' => $a->country_code,
                'affected_assets' => $a->affected_assets ?? [],
                'event_category' => $a->event_category,
                'sentiment' => $a->sentiment_classification,
                'sentiment_score' => $a->sentiment_score,
                'importance' => $a->importance,
            ];
        });

        // Current snapshot and active events
        $now = now()->utc();
        $contextSnapshot = $this->newsContextService->buildNewsContextSnapshot($symbol);
        $upcomingEvents = EconomicCalendarEvent::where('scheduled_at', '>=', $now)
            ->where('scheduled_at', '<=', $now->copy()->addHours(24))
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get()
            ->map(function ($ev) use ($now) {
                return [
                    'id' => $ev->id,
                    'event_name' => $ev->event_name,
                    'country_code' => $ev->country_code,
                    'currency' => $ev->currency,
                    'scheduled_at' => $ev->scheduled_at->toIso8601String(),
                    'importance' => $ev->importance,
                    'minutes_until' => $now->diffInMinutes($ev->scheduled_at, false),
                    'time_remaining_str' => $now->diffForHumans($ev->scheduled_at, true),
                ];
            });

        $activeRestrictions = TradeRestriction::where('expires_at', '>', $now)
            ->with('economicEvent')
            ->get();

        $maxId = NewsArticle::max('id') ?? 0;

        return response()->json([
            'success' => true,
            'latest_id' => $maxId,
            'new_count' => $newArticles->count(),
            'articles' => $newArticles,
            'market_risk' => [
                'risk_score' => $contextSnapshot['sentiment_score'] ?? 0,
                'sentiment_bias' => $contextSnapshot['sentiment_bias'] ?? 'NEUTRAL',
                'macro_narrative' => $contextSnapshot['macro_narrative'] ?? '',
                'active_catalysts_count' => count($contextSnapshot['recent_headlines'] ?? []),
            ],
            'upcoming_events' => $upcomingEvents,
            'active_restrictions_count' => $activeRestrictions->count(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Force immediate real-time synchronization from all global news feeds.
     */
    public function syncNow(): \Illuminate\Http\JsonResponse
    {
        try {
            $result = $this->newsAggregator->syncAll(true);
            return response()->json([
                'success' => true,
                'message' => "Live synchronization complete. Ingested {$result['new_articles_inserted']} brand new global articles.",
                'details' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
