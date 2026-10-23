<?php

namespace App\Console\Commands;

use App\Services\News\GlobalNewsAggregatorService;
use Illuminate\Console\Command;

class SyncLiveNewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'chartsense:sync-news 
                            {--force : Force sync regardless of last sync time}
                            {--daemon : Keep running continuously in background, syncing every interval}
                            {--interval=45 : Sync interval in seconds when in daemon mode}';

    /**
     * The console command description.
     */
    protected $description = 'Synchronize live real-time financial and macro news from global providers across the world';

    public function handle(GlobalNewsAggregatorService $aggregator): int
    {
        $force = (bool)$this->option('force');
        $daemon = (bool)$this->option('daemon');
        $interval = max(15, (int)$this->option('interval'));

        $this->info("=== ChartSense AI Global Real-Time News Synchronizer ===");

        if ($daemon) {
            $this->line("<fg=cyan>Mode: CONTINUOUS LIVE DAEMON</> | Interval: {$interval}s");
            $this->line("Synchronizing live news from across the world continuously...");

            while (true) {
                $nowStr = now()->format('Y-m-d H:i:s');
                $this->line("[{$nowStr}] Starting sync across global wires...");

                try {
                    $result = $aggregator->syncAll(true);
                    $this->info("[{$nowStr}] Sync complete. Fetched: {$result['total_fetched']} | New Articles Inserted: <fg=green>{$result['new_articles_inserted']}</> | Total in DB: {$result['total_articles_in_db']} ({$result['duration_ms']}ms)");
                } catch (\Throwable $e) {
                    $this->error("[{$nowStr}] Error syncing news: " . $e->getMessage());
                }

                sleep($interval);
            }
        }

        $this->line("Fetching latest headlines from global providers...");
        $result = $aggregator->syncAll($force);

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Items Fetched', $result['total_fetched']],
                ['Brand New Articles Inserted', "<fg=green>{$result['new_articles_inserted']}</>"],
                ['Total Articles in Database', $result['total_articles_in_db']],
                ['Execution Duration', "{$result['duration_ms']} ms"],
                ['Synced Timestamp (UTC)', $result['synced_at']],
            ]
        );

        $this->newLine();
        $this->info("Provider Breakdown:");
        $providerRows = [];
        foreach ($result['providers'] as $code => $rep) {
            $status = $rep['status'] ?? 'unknown';
            $statusColor = $status === 'synced' ? 'green' : ($status === 'skipped_throttled' ? 'yellow' : 'red');
            $providerRows[] = [
                $code,
                "<fg={$statusColor}>{$status}</>",
                $rep['fetched'] ?? 0,
                isset($rep['latency_ms']) ? "{$rep['latency_ms']} ms" : ($rep['message'] ?? '—'),
            ];
        }
        $this->table(['Provider', 'Status', 'Fetched', 'Latency / Note'], $providerRows);

        $this->newLine();
        $this->info("Global real-time news synchronization completed successfully.");
        return 0;
    }
}
