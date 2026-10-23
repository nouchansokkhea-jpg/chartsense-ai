<?php

namespace App\Services\Historical\Providers;

use App\Services\Historical\Contracts\HistoricalDataProviderInterface;
use App\Services\Security\SsrfProtectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YahooFinanceHistoricalProvider implements HistoricalDataProviderInterface
{
    protected array $symbolMap = [
        'EURUSD' => 'EURUSD=X',
        'GBPUSD' => 'GBPUSD=X',
        'USDJPY' => 'USDJPY=X',
        'AUDUSD' => 'AUDUSD=X',
        'USDCAD' => 'USDCAD=X',
        'USDCHF' => 'USDCHF=X',
        'NZDUSD' => 'NZDUSD=X',
        'XAUUSD' => 'GC=F',
        'BTCUSD' => 'BTC-USD',
        'SPX500' => '^GSPC',
    ];

    protected array $intervalMap = [
        'M1'  => '1m',
        'M5'  => '5m',
        'M15' => '15m',
        'M30' => '30m',
        'H1'  => '1h',
        'H4'  => '1h', // Will aggregate to 4H if needed
        'D1'  => '1d',
        'W1'  => '1wk',
    ];

    public function getIdentifier(): string
    {
        return 'yahoo_finance';
    }

    public function supports(string $symbol, string $timeframe): bool
    {
        return isset($this->symbolMap[strtoupper($symbol)]) && isset($this->intervalMap[strtoupper($timeframe)]);
    }

    public function fetchCandles(string $symbol, string $timeframe, Carbon $startDate, Carbon $endDate): array
    {
        $sym = strtoupper($symbol);
        $tf = strtoupper($timeframe);

        $yahooSymbol = $this->symbolMap[$sym] ?? $sym;
        $interval = $this->intervalMap[$tf] ?? '1d';

        $p1 = $startDate->timestamp;
        $p2 = $endDate->timestamp;

        $targetUrl = "https://query1.finance.yahoo.com/v8/finance/chart/{$yahooSymbol}?period1={$p1}&period2={$p2}&interval={$interval}";

        try {
            $validatedUrl = SsrfProtectionService::validateUrl($targetUrl);

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
            ])->timeout(15)->get($validatedUrl);

            if (!$response->successful()) {
                Log::warning("Yahoo Finance request unsuccessful for {$symbol}: HTTP {$response->status()}");
                return [];
            }

            $data = $response->json();
            $result = $data['chart']['result'][0] ?? null;

            if (!$result || empty($result['timestamp'])) {
                Log::info("No candle data returned from Yahoo Finance for {$symbol} ({$timeframe})");
                return [];
            }

            $timestamps = $result['timestamp'];
            $quote = $result['indicators']['quote'][0] ?? [];
            $opens = $quote['open'] ?? [];
            $highs = $quote['high'] ?? [];
            $lows = $quote['low'] ?? [];
            $closes = $quote['close'] ?? [];
            $volumes = $quote['volume'] ?? [];

            $candles = [];
            $count = count($timestamps);

            for ($i = 0; $i < $count; $i++) {
                $o = $opens[$i] ?? null;
                $h = $highs[$i] ?? null;
                $l = $lows[$i] ?? null;
                $c = $closes[$i] ?? null;

                if ($o === null || $h === null || $l === null || $c === null) {
                    continue; // Skip incomplete bars
                }

                $candles[] = [
                    'timestamp' => Carbon::createFromTimestamp($timestamps[$i], 'UTC')->toIso8601String(),
                    'open' => round((float)$o, 5),
                    'high' => round((float)$h, 5),
                    'low' => round((float)$l, 5),
                    'close' => round((float)$c, 5),
                    'volume' => isset($volumes[$i]) ? (float)$volumes[$i] : 1000.0,
                    'volume_type' => 'tick',
                    'data_source' => 'yahoo_finance',
                ];
            }

            return $candles;
        } catch (\Throwable $e) {
            Log::error("Yahoo Finance fetch exception for {$symbol}: " . $e->getMessage());
            return [];
        }
    }
}
