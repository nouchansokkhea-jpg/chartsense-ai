<?php

namespace App\Services\Historical;

use App\Models\HistoricalMarketCandle;
use App\Models\MarketDataSyncJob;
use App\Services\Historical\Contracts\HistoricalDataProviderInterface;
use App\Services\Historical\Providers\DeterministicHistoricalProvider;
use App\Services\Historical\Providers\YahooFinanceHistoricalProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HistoricalMarketDataService
{
    protected array $providers = [];
    protected DataQualityValidator $validator;

    public function __construct(DataQualityValidator $validator)
    {
        $this->validator = $validator;
        $this->providers = [
            'yahoo_finance' => new YahooFinanceHistoricalProvider(),
            'deterministic_reference' => new DeterministicHistoricalProvider(),
        ];
    }

    /**
     * Synchronize rolling historical window for a symbol and timeframe.
     *
     * @param string $symbol
     * @param string $timeframe
     * @param int $daysBack Default 1095 days (approx 3 years)
     * @param string $preferredProvider
     * @return array Sync summary
     */
    public function syncHistoricalData(
        string $symbol,
        string $timeframe = 'D1',
        int $daysBack = 1095,
        string $preferredProvider = 'yahoo_finance'
    ): array {
        $symbol = strtoupper($symbol);
        $timeframe = strtoupper($timeframe);
        $windowEnd = Carbon::now('UTC');
        $windowStart = $windowEnd->copy()->subDays($daysBack);

        // Track or resume sync job
        $syncJob = MarketDataSyncJob::firstOrCreate(
            [
                'symbol' => $symbol,
                'timeframe' => $timeframe,
            ],
            [
                'data_source' => $preferredProvider,
                'window_start' => $windowStart,
                'window_end' => $windowEnd,
                'status' => 'pending',
                'candles_synced' => 0,
                'gaps_detected' => 0,
            ]
        );

        $syncJob->update([
            'status' => 'syncing',
            'window_end' => $windowEnd,
            'window_start' => $windowStart,
        ]);

        $provider = $this->providers[$preferredProvider] ?? $this->providers['yahoo_finance'];

        // If resuming from last synced candle
        $fetchStart = $syncJob->last_synced_candle_at
            ? Carbon::parse($syncJob->last_synced_candle_at)->subDays(2)
            : $windowStart;

        $candles = [];
        try {
            $candles = $provider->fetchCandles($symbol, $timeframe, $fetchStart, $windowEnd);
        } catch (\Throwable $e) {
            Log::warning("Provider {$preferredProvider} failed for {$symbol}: {$e->getMessage()}");
        }

        // Fallback to deterministic reference provider if external provider yielded no bars
        $dataSource = $provider->getIdentifier();
        if (empty($candles)) {
            Log::info("Falling back to deterministic reference provider for {$symbol} ({$timeframe})");
            $fallback = $this->providers['deterministic_reference'];
            $candles = $fallback->fetchCandles($symbol, $timeframe, $fetchStart, $windowEnd);
            $dataSource = 'deterministic_reference';
        }

        if (empty($candles)) {
            $syncJob->update([
                'status' => 'failed',
                'error_message' => 'No candle data could be acquired from any provider.',
            ]);

            return [
                'status' => 'failed',
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'candles_synced' => 0,
                'gaps_detected' => 0,
                'error' => 'No candle data available',
            ];
        }

        // Ingest and validate candles
        $syncedCount = 0;
        $gapCount = 0;
        $previousCandle = null;
        $lastSyncedAt = null;

        DB::beginTransaction();
        try {
            foreach ($candles as $candle) {
                $validation = $this->validator->validateCandle($candle, $previousCandle);
                $isGap = false;

                $currTimestamp = Carbon::parse($candle['timestamp']);
                if ($previousCandle) {
                    $prevTimestamp = Carbon::parse($previousCandle['timestamp']);
                    if ($this->validator->isGap($prevTimestamp, $currTimestamp, $timeframe)) {
                        $isGap = true;
                        $gapCount++;
                    }
                }

                HistoricalMarketCandle::updateOrCreate(
                    [
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'candle_timestamp' => $currTimestamp,
                    ],
                    [
                        'open' => $candle['open'],
                        'high' => $candle['high'],
                        'low' => $candle['low'],
                        'close' => $candle['close'],
                        'volume' => $candle['volume'] ?? null,
                        'volume_type' => $candle['volume_type'] ?? 'tick',
                        'trading_session' => $validation['trading_session'],
                        'data_source' => $dataSource,
                        'quality_score' => $validation['quality_score'],
                        'is_gap' => $isGap,
                        'quality_notes' => !empty($validation['issues']) ? implode('; ', $validation['issues']) : null,
                    ]
                );

                $previousCandle = $candle;
                $lastSyncedAt = $currTimestamp;
                $syncedCount++;
            }

            DB::commit();

            $syncJob->update([
                'status' => 'completed',
                'data_source' => $dataSource,
                'last_synced_candle_at' => $lastSyncedAt,
                'candles_synced' => $syncJob->candles_synced + $syncedCount,
                'gaps_detected' => $syncJob->gaps_detected + $gapCount,
                'error_message' => null,
            ]);

            return [
                'status' => 'completed',
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'data_source' => $dataSource,
                'candles_synced' => $syncedCount,
                'gaps_detected' => $gapCount,
                'last_synced_at' => $lastSyncedAt?->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Historical sync transaction failed for {$symbol}: " . $e->getMessage());

            $syncJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'candles_synced' => 0,
                'gaps_detected' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get historical candles for analysis or feature engineering.
     */
    public function getCandles(string $symbol, string $timeframe, int $limit = 300): array
    {
        return HistoricalMarketCandle::forInstrument($symbol, $timeframe)
            ->verifiedQuality(60)
            ->orderBy('candle_timestamp', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
