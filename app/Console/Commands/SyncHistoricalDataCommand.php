<?php

namespace App\Console\Commands;

use App\Services\Historical\HistoricalMarketDataService;
use Illuminate\Console\Command;

class SyncHistoricalDataCommand extends Command
{
    protected $signature = 'chartsense:sync-historical-data
                            {symbol=EURUSD : Currency pair or asset symbol (EURUSD, XAUUSD, BTCUSD, etc.)}
                            {--timeframe=D1 : Timeframe (M15, H1, H4, D1, W1)}
                            {--days=1095 : Number of historical days to sync (default: 1095 = 3 years)}
                            {--provider=yahoo_finance : Preferred historical data provider}';

    protected $description = 'Ingest and validate 3-year rolling historical market data without look-ahead bias';

    public function handle(HistoricalMarketDataService $service): int
    {
        $symbol = strtoupper($this->argument('symbol'));
        $timeframe = strtoupper($this->option('timeframe'));
        $days = (int)$this->option('days');
        $provider = (string)$this->option('provider');

        $this->info("=== ChartSense AI: Historical Market Data Sync ===");
        $this->line("Symbol:     {$symbol}");
        $this->line("Timeframe:  {$timeframe}");
        $this->line("Window:     Last {$days} days (~" . round($days / 365, 1) . " years)");
        $this->line("Provider:   {$provider}");

        $result = $service->syncHistoricalData($symbol, $timeframe, $days, $provider);

        if ($result['status'] === 'completed') {
            $this->info("✓ Ingestion Completed Successfully!");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Symbol', $result['symbol']],
                    ['Timeframe', $result['timeframe']],
                    ['Data Source', $result['data_source']],
                    ['Candles Ingested', $result['candles_synced']],
                    ['Gaps Detected', $result['gaps_detected']],
                    ['Last Synced Candle', $result['last_synced_at'] ?? 'N/A'],
                ]
            );
            return Command::SUCCESS;
        }

        $this->error("Ingestion failed: " . ($result['error'] ?? 'Unknown error'));
        return Command::FAILURE;
    }
}
