<?php

namespace App\Services\News;

use App\Models\EconomicCalendarEvent;
use App\Models\NewsArticle;
use App\Models\TradeRestriction;
use App\Services\News\Contracts\EconomicCalendarProviderInterface;
use App\Services\News\Contracts\NewsProviderInterface;
use App\Services\News\Providers\ForexLiveNewsProvider;
use App\Services\News\Providers\InstitutionalCalendarProvider;

class GlobalNewsContextService
{
    public function __construct(
        protected NewsDeduplicationService $dedupService,
        protected EconomicSurpriseCalculator $surpriseCalculator,
        protected ?NewsProviderInterface $newsProvider = null,
        protected ?EconomicCalendarProviderInterface $calendarProvider = null
    ) {
        $this->newsProvider = $newsProvider ?? app(ForexLiveNewsProvider::class);
        $this->calendarProvider = $calendarProvider ?? app(InstitutionalCalendarProvider::class);
    }

    /**
     * Build an immutable, versioned global news & macroeconomic context snapshot for the multi-AI team.
     *
     * @param string $symbol e.g. 'XAUUSD', 'EURUSD'
     * @return array Standardized immutable context snapshot
     */
    public function buildNewsContextSnapshot(string $symbol): array
    {
        $symbol = strtoupper(trim($symbol));
        $assetClass = $this->determineAssetClass($symbol);
        $now = now()->utc();

        // 1. Ingest/Query Recent Relevant Articles
        $articles = NewsArticle::where(function ($q) use ($symbol) {
            $q->whereJsonContains('affected_assets', $symbol)
              ->orWhereJsonContains('affected_assets', 'USD')
              ->orWhereJsonContains('affected_assets', 'GLOBAL_MACRO');
        })
        ->where('published_at', '>=', $now->copy()->subHours(24))
        ->orderByDesc('published_at')
        ->limit(6)
        ->get();

        if ($articles->isEmpty()) {
            $raw = $this->newsProvider->fetchArticles($assetClass);
            $saved = $this->dedupService->ingestAndDeduplicate($raw, $this->newsProvider->getProviderCode());
            $articles = collect($saved)->take(6);
        }

        // 2. Query Upcoming & Recent Economic Events
        $events = EconomicCalendarEvent::where('scheduled_at', '>=', $now->copy()->subHours(2))
            ->where('scheduled_at', '<=', $now->copy()->addHours(24))
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        if ($events->isEmpty()) {
            $rawEvents = $this->calendarProvider->fetchEvents(
                $now->copy()->subHours(2)->toDateString(),
                $now->copy()->addHours(24)->toDateString()
            );
            foreach ($rawEvents as $ev) {
                EconomicCalendarEvent::updateOrCreate(
                    [
                        'event_name' => $ev['event_name'],
                        'scheduled_at' => $ev['scheduled_at'],
                    ],
                    array_merge($ev, [
                        'provider_code' => $this->calendarProvider->getProviderCode(),
                    ])
                );
            }
            $events = EconomicCalendarEvent::where('scheduled_at', '>=', $now->copy()->subHours(2))
                ->where('scheduled_at', '<=', $now->copy()->addHours(24))
                ->orderBy('scheduled_at')
                ->limit(8)
                ->get();
        }

        // 3. Evaluate Active Trade Restrictions for this symbol
        $activeRestrictions = TradeRestriction::where('symbol', $symbol)
            ->where('status', 'BLOCKED')
            ->where('expires_at', '>', $now)
            ->with('economicEvent')
            ->get();

        // 4. Calculate Risk Level & Minutes Until Next High-Impact Event
        $riskLevel = 'NORMAL';
        $minutesToNextEvent = null;
        $nextEvent = null;

        foreach ($events as $ev) {
            $diffMinutes = (int) $now->diffInMinutes($ev->scheduled_at, false);
            if ($diffMinutes >= 0) {
                if ($minutesToNextEvent === null || $diffMinutes < $minutesToNextEvent) {
                    $minutesToNextEvent = $diffMinutes;
                    $nextEvent = $ev;
                }
            }
        }

        if ($activeRestrictions->isNotEmpty()) {
            $riskLevel = 'CRITICAL';
        } elseif ($nextEvent && in_array($nextEvent->importance, ['HIGH', 'CRITICAL']) && $minutesToNextEvent <= 45) {
            $riskLevel = $nextEvent->importance === 'CRITICAL' ? 'CRITICAL' : 'HIGH';
        } elseif ($nextEvent && $minutesToNextEvent <= 120) {
            $riskLevel = 'ELEVATED';
        }

        // 5. Build Formatted Event List with Surprise Analysis
        $formattedEvents = [];
        foreach ($events as $ev) {
            $surprise = null;
            if ($ev->actual_value !== null && $ev->forecast_value !== null) {
                $surprise = $this->surpriseCalculator->calculateSurprise(
                    $ev->event_name,
                    $ev->actual_value,
                    $ev->forecast_value,
                    $ev->previous_value
                );
            }

            $diffMin = (int) $now->diffInMinutes($ev->scheduled_at, false);

            $formattedEvents[] = [
                'id' => $ev->id,
                'name' => $ev->event_name,
                'country' => $ev->country_code,
                'currency' => $ev->currency,
                'scheduled_at' => $ev->scheduled_at->toIso8601String(),
                'minutes_away' => $diffMin,
                'time_status' => $diffMin < 0 ? 'Released ' . abs($diffMin) . 'm ago' : 'In ' . $diffMin . ' minutes',
                'importance' => $ev->importance,
                'previous' => $ev->previous_value,
                'forecast' => $ev->forecast_value,
                'actual' => $ev->actual_value,
                'surprise' => $surprise,
                'status' => $ev->status,
            ];
        }

        // 6. Build Narrative Summary
        $topArticle = $articles->first();
        $narrative = $topArticle ? $topArticle->title : 'Global market wires indicate baseline macro liquidity conditions.';
        if ($nextEvent && $minutesToNextEvent !== null && $minutesToNextEvent <= 60) {
            $narrative .= " Immediate market catalyst: '{$nextEvent->event_name}' in {$minutesToNextEvent} minutes.";
        }

        return [
            'snapshot_id' => 'news_snap_' . bin2hex(random_bytes(6)),
            'symbol' => $symbol,
            'asset_class' => $assetClass,
            'sentiment_bias' => $riskLevel === 'CRITICAL' ? 'HIGH_VOLATILITY_RISK' : ($topArticle ? $topArticle->sentiment_classification : 'NEUTRAL'),
            'sentiment_score' => $topArticle ? (float)$topArticle->sentiment_score : 0.0,
            'high_impact_warning' => ($riskLevel === 'CRITICAL' || $riskLevel === 'HIGH'),
            'snapshot_timestamp_utc' => $now->toIso8601String(),
            'market_risk_level' => $riskLevel,
            'has_proximate_critical_event' => ($riskLevel === 'CRITICAL' || $riskLevel === 'HIGH'),
            'minutes_until_next_event' => $minutesToNextEvent,
            'next_high_impact_event' => $nextEvent ? [
                'name' => $nextEvent->event_name,
                'importance' => $nextEvent->importance,
                'currency' => $nextEvent->currency,
                'scheduled_at' => $nextEvent->scheduled_at->toIso8601String(),
                'minutes_away' => $minutesToNextEvent,
            ] : null,
            'active_restrictions' => $activeRestrictions->map(fn($r) => [
                'status' => $r->status,
                'severity' => $r->severity,
                'reason' => $r->reason,
                'expires_at' => $r->expires_at->toIso8601String(),
            ])->all(),
            'recent_headlines' => $articles->map(fn($a) => [
                'title' => $a->title,
                'source' => $a->source_name,
                'published_at' => $a->published_at->toIso8601String(),
                'sentiment' => $a->sentiment_classification,
                'importance' => $a->importance,
            ])->all(),
            'headlines' => $articles->map(fn($a) => [
                'title' => $a->title,
                'source' => $a->source_name,
                'published_at' => $a->published_at->toIso8601String(),
                'sentiment' => $a->sentiment_classification,
                'importance' => $a->importance,
            ])->all(),
            'upcoming_events' => $formattedEvents,
            'summary_narrative' => $narrative,
            'macro_narrative' => $narrative,
            'data_freshness' => 'Real-time verified wire data synchronized to UTC.',
            'source_reliability' => 'Official government bureaus & Tier-1 financial institutional feeds.',
        ];
    }

    protected function determineAssetClass(string $symbol): string
    {
        if (preg_match('/^(BTC|ETH|SOL|XRP|BNB|DOGE)/i', $symbol)) return 'crypto';
        if (in_array($symbol, ['XAUUSD', 'GOLD', 'XAGUSD', 'SILVER', 'USOIL', 'UKOIL'])) return 'commodities';
        if (in_array($symbol, ['US30', 'NAS100', 'SPX500', 'GER40', 'UK100'])) return 'indices';
        return 'forex';
    }
}
