<?php

namespace App\Console\Commands;

use App\Services\Notifications\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestTelegramBotCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'chartsense:test-telegram 
                            {--chat_id= : Telegram chat or channel ID (e.g. -100123456789 or 987654321)}
                            {--message= : Custom test alert message}';

    /**
     * The console command description.
     */
    protected $description = 'Verify Telegram bot credentials, fetch latest chat IDs, and send test alerts';

    public function handle(TelegramService $telegramService): int
    {
        $this->info("==================================================");
        $this->info("   ChartSense AI — Telegram Bot Diagnostic Tool  ");
        $this->info("==================================================");

        $token = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        $defaultChannel = config('services.telegram.channel_id', env('TELEGRAM_CHANNEL_ID'));

        if (empty($token)) {
            $this->error("TELEGRAM_BOT_TOKEN is not configured in .env or config/services.php.");
            $this->line("Please set TELEGRAM_BOT_TOKEN in your .env file.");
            return self::FAILURE;
        }

        // Mask token for security
        $masked = substr($token, 0, 8) . '...' . substr($token, -6);
        $this->line("Configured Token: <fg=cyan>{$masked}</>");

        // 1. Verify getMe
        $this->newLine();
        $this->line("--> Verifying bot credentials with Telegram API (getMe)...");

        try {
            $response = Http::timeout(8)->withOptions(['verify' => true])->get("https://api.telegram.org/bot{$token}/getMe");

            if (!$response->successful()) {
                $body = $response->json();
                $this->error("Failed to connect to Telegram Bot API (HTTP {$response->status()}):");
                $this->line("Telegram Response: " . json_encode($body, JSON_PRETTY_PRINT));
                $this->newLine();
                $this->warn("Troubleshooting Tips:");
                $this->line("1. Ensure there are no typographical errors in the token.");
                $this->line("2. Check if the token was refreshed or revoked in BotFather (/mybots -> Bot -> API Token).");
                $this->line("3. Ensure BotFather shows the bot is active.");
                return self::FAILURE;
            }

            $botData = $response->json('result', []);
            $this->info("Bot Verified Successfully!");
            $this->table(
                ['Property', 'Value'],
                [
                    ['Bot ID', $botData['id'] ?? 'N/A'],
                    ['Bot Name', $botData['first_name'] ?? 'N/A'],
                    ['Username', '@' . ($botData['username'] ?? 'N/A')],
                    ['Can Join Groups', ($botData['can_join_groups'] ?? false) ? 'YES' : 'NO'],
                    ['Can Read All Group Msgs', ($botData['can_read_all_group_messages'] ?? false) ? 'YES' : 'NO'],
                ]
            );

        } catch (\Throwable $e) {
            $this->error("Exception connecting to Telegram: " . $e->getMessage());
            return self::FAILURE;
        }

        // 2. Query getUpdates to discover recent users/channels
        $this->newLine();
        $this->line("--> Checking recent updates/messages (getUpdates)...");

        try {
            $updatesResp = Http::timeout(8)->withOptions(['verify' => true])->get("https://api.telegram.org/bot{$token}/getUpdates");
            if ($updatesResp->successful()) {
                $updates = $updatesResp->json('result', []);
                if (empty($updates)) {
                    $this->line("<fg=yellow>No recent messages found.</>");
                    $this->line("Tip: Open Telegram, search for <fg=cyan>@" . ($botData['username'] ?? 'ChartSenseAIScanner_bot') . "</>, press <fg=green>/start</>, and re-run this command to discover your Chat ID.");
                } else {
                    $this->info("Found " . count($updates) . " recent interaction(s):");
                    $rows = [];
                    foreach (array_slice($updates, -5) as $up) {
                        $msg = $up['message'] ?? $up['channel_post'] ?? [];
                        $chat = $msg['chat'] ?? [];
                        $from = $msg['from'] ?? [];
                        $rows[] = [
                            $chat['id'] ?? 'N/A',
                            $chat['type'] ?? 'private',
                            ($from['first_name'] ?? '') . ' (@' . ($from['username'] ?? 'N/A') . ')',
                            $msg['text'] ?? '[Media/Command]',
                        ];
                    }
                    $this->table(['Chat ID', 'Type', 'Sender', 'Last Message'], $rows);
                }
            }
        } catch (\Throwable $e) {
            $this->line("<fg=yellow>Could not fetch updates: " . $e->getMessage() . "</>");
        }

        // 3. Test Alert Dispatch if target chat ID specified
        $targetChat = $this->option('chat_id') ?: $defaultChannel;
        $testMessage = $this->option('message') ?: 'This is a test notification from ChartSense AI Risk & Scanner Intelligence.';

        if (!empty($targetChat)) {
            $this->newLine();
            $this->line("--> Sending test alert to: <fg=cyan>{$targetChat}</>...");

            $result = $telegramService->sendAlert(
                alertType: 'trade_cleared',
                symbol: 'XAUUSD',
                title: 'Telegram Integration Test',
                message: $testMessage,
                severity: 'LOW',
                chatId: $targetChat
            );

            if (($result['status'] ?? '') === 'sent') {
                $this->info("Test alert delivered successfully! Telegram Message ID: " . ($result['message_id'] ?? 'N/A'));
            } else {
                $this->warn("Alert dispatch returned status: " . ($result['status'] ?? 'unknown'));
            }
        } else {
            $this->newLine();
            $this->line("<fg=cyan>To send a test alert, run:</>");
            $this->line("  php artisan chartsense:test-telegram --chat_id=<YOUR_CHAT_ID>");
        }

        return self::SUCCESS;
    }
}
