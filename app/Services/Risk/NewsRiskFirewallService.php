<?php

namespace App\Services\Risk;

use App\Models\EconomicCalendarEvent;
use App\Models\EventAssetMapping;
use App\Models\SystemSetting;
use App\Models\TradeRestriction;
use App\Services\Notifications\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NewsRiskFirewallService
{
    public function __construct(
        protected ?TelegramService $telegramService = null
    ) {
        $this->telegramService = $telegramService ?? app(TelegramService::class);
    }

    /**
     * Evaluate proposed trade against upcoming high-impact economic news and market conditions.
     * Enforces an independent, non-overridable trade block during dangerous news conditions.
     *
     * @param string $symbol e.g. 'XAUUSD', 'EURUSD'
     * @param array $tradingPlan The candidate trading plan from AI / Technical analysis
     * @param array|null $newsContext Optional pre-constructed news context snapshot
     * @return array Evaluation result with firewall status, hard veto flags, and adjusted plan
     */
    public function evaluateTradeSetup(string $symbol, array $tradingPlan, ?array $newsContext = null): array
    {
        $symbol = strtoupper(trim($symbol));
        $now = now()->utc();

        // 1. Check if Firewall is enabled
        $enabled = SystemSetting::where('key', 'firewall_enabled')->value('value') ?? 'true';
        if ($enabled !== 'true' && $enabled !== '1') {
            return [
                'firewall_status' => 'CLEARED',
                'is_blocked' => false,
                'is_stabilizing' => false,
                'reason' => 'News Risk Firewall is currently disabled in system settings.',
                'active_event' => null,
                'trading_plan' => $tradingPlan,
            ];
        }

        // 2. Fetch Configurable Window Parameters
        $criticalWindowMin = (int)(SystemSetting::where('key', 'firewall_critical_block_minutes')->value('value') ?? 30);
        $highWindowMin = (int)(SystemSetting::where('key', 'firewall_high_block_minutes')->value('value') ?? 15);
        $postStabilizationMin = (int)(SystemSetting::where('key', 'firewall_post_stabilization_minutes')->value('value') ?? 15);

        // 3. Query Proximate Economic Events for this symbol (from -60m to +180m)
        $relevantEvents = $this->getRelevantEventsForSymbol($symbol, $now->copy()->subMinutes(60), $now->copy()->addMinutes(180));

        $blockingEvent = null;
        $stabilizingEvent = null;
        $blockingReason = null;

        foreach ($relevantEvents as $event) {
            $diffMinutes = (int) $now->diffInMinutes($event->scheduled_at, false);
            $importance = strtoupper($event->importance);

            // A. Pre-Event Window Check (Upcoming event)
            if ($diffMinutes >= 0) {
                if ($importance === 'CRITICAL' && $diffMinutes <= $criticalWindowMin) {
                    $blockingEvent = $event;
                    $blockingReason = sprintf(
                        "NEWS RISK — TRADE BLOCKED: %s (%s) is scheduled in %d minutes (CRITICAL). " .
                        "Automated trade entries are blocked to protect capital against wide spreads, slippage, and whipsaws.",
                        $event->event_name,
                        $event->currency,
                        $diffMinutes
                    );
                    break;
                }

                if ($importance === 'HIGH' && $diffMinutes <= $highWindowMin) {
                    $blockingEvent = $event;
                    $blockingReason = sprintf(
                        "NEWS RISK — TRADE BLOCKED: %s (%s) is scheduled in %d minutes (HIGH). " .
                        "Pre-event entry window restricted by risk policy.",
                        $event->event_name,
                        $event->currency,
                        $diffMinutes
                    );
                    break;
                }
            }

            // B. Post-Release Stabilization Check (Just released)
            if ($diffMinutes < 0) {
                $elapsedMinutes = abs($diffMinutes);
                if (in_array($importance, ['CRITICAL', 'HIGH']) && $elapsedMinutes <= $postStabilizationMin) {
                    $stabilizingEvent = $event;
                    $blockingReason = sprintf(
                        "NEWS RISK — STABILIZING: %s (%s) was released %d minutes ago. " .
                        "Market is absorbing initial volatility impulse. Normal entry eligibility will restore in %d minutes.",
                        $event->event_name,
                        $event->currency,
                        $elapsedMinutes,
                        max(1, $postStabilizationMin - $elapsedMinutes)
                    );
                    break;
                }
            }
        }

        // 4. If Pre-Event Block or Post-Release Stabilization is triggered: Enforce HARD VETO
        if ($blockingEvent || $stabilizingEvent) {
            $triggerEvent = $blockingEvent ?: $stabilizingEvent;
            $status = $blockingEvent ? 'NEWS RISK — TRADE BLOCKED' : 'NEWS RISK — STABILIZING';
            $severity = strtoupper($triggerEvent->importance) === 'CRITICAL' ? 'CRITICAL' : 'HIGH';

            // Record restriction in database
            $expiresAt = $blockingEvent 
                ? $triggerEvent->scheduled_at->copy()->addMinutes($postStabilizationMin)
                : $triggerEvent->scheduled_at->copy()->addMinutes($postStabilizationMin);

            TradeRestriction::updateOrCreate(
                [
                    'symbol' => $symbol,
                    'economic_event_id' => $triggerEvent->id,
                ],
                [
                    'status' => $blockingEvent ? 'BLOCKED' : 'STABILIZING',
                    'severity' => $severity,
                    'reason' => $blockingReason,
                    'activated_at' => $now,
                    'expires_at' => $expiresAt,
                    'override_prohibited' => true,
                ]
            );

            // Modify Trading Plan: Hard Gatekeeper Veto
            // Preserve the original technical setup for informational reference, but block active trade recommendation
            $adjustedPlan = $tradingPlan;
            $adjustedPlan['trade_eligibility'] = 'BLOCKED_BY_NEWS_FIREWALL';
            $adjustedPlan['firewall_status'] = $status;
            $adjustedPlan['firewall_block_reason'] = $blockingReason;
            $adjustedPlan['original_direction'] = $tradingPlan['direction'] ?? 'WAIT';
            $adjustedPlan['direction'] = 'WAIT'; // Overridden to WAIT: No entry permitted

            // Dispatch automated Telegram notification with dedup protection
            try {
                $this->telegramService->sendAlert(
                    alertType: $blockingEvent ? 'trade_blocked' : 'trade_cleared',
                    symbol: $symbol,
                    title: $triggerEvent->event_name,
                    message: $blockingReason,
                    severity: $severity
                );
            } catch (\Throwable $e) {
                Log::info('Telegram firewall alert note: ' . $e->getMessage());
            }

            $adjustedPlan['conditional_scenario'] = [
                'intended_direction' => $adjustedPlan['original_direction'],
                'technical_entry' => $tradingPlan['entry_target'] ?? ($tradingPlan['entry_zone']['optimal_entry'] ?? null),
                'technical_stop' => $tradingPlan['stop_loss']['price'] ?? ($tradingPlan['stop_loss'] ?? null),
                'technical_tp' => $tradingPlan['take_profit_targets'][0]['price'] ?? ($tradingPlan['take_profit_1'] ?? null),
                'notice' => 'Technical analysis preserved for informational review only. Entry prohibited until news risk clears.',
            ];

            return [
                'firewall_status' => $status,
                'is_blocked' => true,
                'is_stabilizing' => ($stabilizingEvent !== null),
                'reason' => $blockingReason,
                'active_event' => [
                    'id' => $triggerEvent->id,
                    'name' => $triggerEvent->event_name,
                    'currency' => $triggerEvent->currency,
                    'scheduled_at' => $triggerEvent->scheduled_at->toIso8601String(),
                    'importance' => $triggerEvent->importance,
                ],
                'trading_plan' => $adjustedPlan,
            ];
        }

        // 5. Normal Cleared Conditions
        $tradingPlan['trade_eligibility'] = 'APPROVED';
        $tradingPlan['firewall_status'] = 'CLEARED';
        $tradingPlan['firewall_block_reason'] = null;

        return [
            'firewall_status' => 'CLEARED',
            'is_blocked' => false,
            'is_stabilizing' => false,
            'reason' => 'No proximate high-impact economic news risk detected for ' . $symbol . '.',
            'active_event' => null,
            'trading_plan' => $tradingPlan,
        ];
    }

    /**
     * Retrieve relevant economic events matching symbol and currencies.
     */
    protected function getRelevantEventsForSymbol(string $symbol, Carbon $from, Carbon $to): array
    {
        $symbol = strtoupper($symbol);
        $currencies = $this->extractCurrenciesForSymbol($symbol);

        $events = EconomicCalendarEvent::where('scheduled_at', '>=', $from)
            ->where('scheduled_at', '<=', $to)
            ->whereIn('currency', $currencies)
            ->whereIn('importance', ['CRITICAL', 'HIGH'])
            ->orderBy('scheduled_at')
            ->get();

        // Also check explicit event-to-asset pattern mappings
        $patternMappings = EventAssetMapping::where('is_active', true)->get();
        $matchedEvents = [];

        foreach ($events as $ev) {
            $isDirectCurrency = in_array($ev->currency, $currencies);
            $isMapped = false;

            foreach ($patternMappings as $map) {
                if (stripos($ev->event_name, $map->event_pattern) !== false) {
                    if (is_array($map->affected_symbols) && in_array($symbol, $map->affected_symbols)) {
                        $isMapped = true;
                        break;
                    }
                }
            }

            if ($isDirectCurrency || $isMapped) {
                $matchedEvents[] = $ev;
            }
        }

        return $matchedEvents;
    }

    /**
     * Extract relevant currencies for a given trading symbol.
     */
    protected function extractCurrenciesForSymbol(string $symbol): array
    {
        $symbol = strtoupper($symbol);

        if ($symbol === 'XAUUSD' || $symbol === 'GOLD') return ['USD'];
        if ($symbol === 'XAGUSD' || $symbol === 'SILVER') return ['USD'];
        if ($symbol === 'USOIL' || $symbol === 'UKOIL') return ['USD', 'CAD'];
        if (in_array($symbol, ['US30', 'NAS100', 'SPX500', 'DJI'])) return ['USD'];
        if ($symbol === 'GER40') return ['EUR'];
        if ($symbol === 'UK100') return ['GBP'];

        // Standard 6-character forex pairs (e.g. EURUSD -> EUR, USD)
        if (strlen($symbol) === 6 && !str_starts_with($symbol, 'BTC') && !str_starts_with($symbol, 'ETH')) {
            return [substr($symbol, 0, 3), substr($symbol, 3, 3)];
        }

        // Crypto pairs (BTCUSD, ETHUSD)
        if (str_ends_with($symbol, 'USD') || str_ends_with($symbol, 'USDT')) {
            return ['USD'];
        }

        return ['USD'];
    }
}
