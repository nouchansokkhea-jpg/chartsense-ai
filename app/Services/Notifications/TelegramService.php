<?php

namespace App\Services\Notifications;

use App\Models\TelegramAlertLog;
use App\Models\UserAlertPreference;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected ?string $botToken;
    protected ?string $defaultChannelId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        $this->defaultChannelId = config('services.telegram.channel_id', env('TELEGRAM_CHANNEL_ID'));
    }

    /**
     * Dispatch structured Telegram alert with duplicate prevention and audit logging.
     *
     * @param string $alertType 'event_approaching' | 'trade_blocked' | 'trade_cleared' | 'release_published' | 'model_disagreement'
     * @param string $symbol e.g. 'XAUUSD', 'EURUSD'
     * @param string $title Short alert title
     * @param string $message Detailed explanation
     * @param string $severity 'LOW' | 'MODERATE' | 'HIGH' | 'CRITICAL'
     * @param int|null $userId Optional specific user
     * @param string|null $chatId Optional specific Telegram chat ID (defaults to channel)
     * @param int|null $scanId Optional associated scan report ID
     * @return array{status: string, message_id: ?string, dedup_key: string}
     */
    public function sendAlert(
        string $alertType,
        string $symbol,
        string $title,
        string $message,
        string $severity = 'HIGH',
        ?int $userId = null,
        ?string $chatId = null,
        ?int $scanId = null
    ): array {
        $targetChatId = $chatId ?: $this->defaultChannelId;

        // If specific user provided, check their alert preferences
        if ($userId && !$chatId) {
            $pref = UserAlertPreference::where('user_id', $userId)->first();
            if ($pref && $pref->telegram_chat_id) {
                $targetChatId = $pref->telegram_chat_id;
                // Check user preferences
                if ($alertType === 'event_approaching' && !$pref->notify_approaching_events) {
                    return ['status' => 'user_opted_out', 'message_id' => null, 'dedup_key' => ''];
                }
                if ($alertType === 'trade_blocked' && !$pref->notify_trade_blocks) {
                    return ['status' => 'user_opted_out', 'message_id' => null, 'dedup_key' => ''];
                }
            }
        }

        if (empty($targetChatId)) {
            $targetChatId = '@chartsense_alerts_mock';
        }

        // Duplicate prevention: Generate hourly deduplication hash
        $hourKey = gmdate('YmdH');
        $dedupKey = hash('sha256', "{$alertType}_{$symbol}_{$title}_{$hourKey}_{$targetChatId}");

        $existingLog = TelegramAlertLog::where('dedup_key', $dedupKey)->first();
        if ($existingLog) {
            return [
                'status' => 'duplicate_suppressed',
                'message_id' => $existingLog->telegram_message_id,
                'dedup_key' => $dedupKey,
            ];
        }

        // Format message
        $formattedText = $this->formatMessageText($alertType, $symbol, $title, $message, $severity, $scanId);

        // Attempt live Telegram HTTP dispatch if token configured
        $telegramMessageId = null;
        $deliveryStatus = 'mock_sent';
        $errorMessage = null;

        if (!empty($this->botToken) && !str_starts_with($targetChatId, '@chartsense_alerts_mock')) {
            try {
                $targetUrl = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
                \App\Services\Security\SsrfProtectionService::validateUrl("https://api.telegram.org");

                $response = Http::timeout(6)->withOptions(['verify' => true])->post($targetUrl, [
                    'chat_id' => $targetChatId,
                    'text' => $formattedText,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => false,
                ]);

                if ($response->successful()) {
                    $body = $response->json();
                    $telegramMessageId = (string)($body['result']['message_id'] ?? null);
                    $deliveryStatus = 'sent';
                } else {
                    $deliveryStatus = 'failed';
                    $rawBody = $response->body();
                    $errorMessage = 'Telegram API Error: ' . str_replace($this->botToken, '[REDACTED_BOT_TOKEN]', $rawBody);
                    Log::warning($errorMessage);
                }
            } catch (\Throwable $e) {
                $deliveryStatus = 'failed';
                $errorMessage = str_replace($this->botToken, '[REDACTED_BOT_TOKEN]', $e->getMessage());
                Log::warning('Telegram exception: ' . $errorMessage);
            }
        }

        // Audit Log Persistence
        TelegramAlertLog::create([
            'user_id' => $userId,
            'alert_type' => $alertType,
            'dedup_key' => $dedupKey,
            'symbol' => $symbol,
            'chat_id' => $targetChatId,
            'message_text' => $formattedText,
            'delivery_status' => $deliveryStatus,
            'telegram_message_id' => $telegramMessageId,
            'error_message' => $errorMessage,
            'sent_at' => now(),
        ]);

        return [
            'status' => $deliveryStatus,
            'message_id' => $telegramMessageId,
            'dedup_key' => $dedupKey,
        ];
    }

    protected function formatMessageText(
        string $alertType,
        string $symbol,
        string $title,
        string $message,
        string $severity,
        ?int $scanId
    ): string {
        $icon = match ($alertType) {
            'trade_blocked' => '🚨',
            'trade_cleared' => '✅',
            'event_approaching' => '⏳',
            'release_published' => '📊',
            'model_disagreement' => '⚠️',
            default => '📢',
        };

        $headerTitle = match ($alertType) {
            'trade_blocked' => 'NEWS RISK FIREWALL — TRADE BLOCKED',
            'trade_cleared' => 'NEWS RISK FIREWALL — RESTRICTION LIFTED',
            'event_approaching' => 'UPCOMING HIGH-IMPACT MACRO EVENT',
            'release_published' => 'OFFICIAL ECONOMIC DATA RELEASED',
            'model_disagreement' => 'MULTI-AI CONSENSUS CONFLICT',
            default => 'CHARTSENSE AI MARKET ALERT',
        };

        $utcTime = gmdate('Y-m-d H:i:s') . ' UTC';
        $scanUrl = $scanId ? url("/scans/{$scanId}") : url("/news?symbol={$symbol}");

        return <<<HTML
{$icon} <b>{$headerTitle}</b>
━━━━━━━━━━━━━━━━━━━━
<b>Asset:</b> #{$symbol}
<b>Severity:</b> {$severity}
<b>Time:</b> {$utcTime}

<b>{$title}</b>
{$message}

<b>Status:</b> <i>Never trade through unverified spreads or high-volatility news windows.</i>
<a href="{$scanUrl}">Inspect Analysis on ChartSense AI</a>
HTML;
    }
}
