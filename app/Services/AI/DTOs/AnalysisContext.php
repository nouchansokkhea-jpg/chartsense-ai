<?php

namespace App\Services\AI\DTOs;

class AnalysisContext
{
    public function __construct(
        public readonly string $symbol,
        public readonly string $timeframe,
        public readonly string $strategy = 'comprehensive',
        public readonly ?string $imagePath = null,
        public readonly ?array $candles = null,
        public readonly ?array $indicators = null,
        public readonly ?array $marketStructure = null,
        public readonly ?array $supportResistance = null,
        public readonly ?array $smc = null,
        public readonly ?array $fibonacci = null,
        public readonly ?array $parameters = null,
        public readonly ?array $macroNews = null,
        public readonly ?array $marketRegime = null,
        public readonly ?array $retrievedStrategies = null,
        public readonly ?string $notes = null,
        public readonly string $dataSource = 'unknown',
        public readonly ?string $inputHash = null
    ) {}

    public function hasVision(): bool
    {
        return !empty($this->imagePath) && file_exists($this->imagePath);
    }

    public function hasCandles(): bool
    {
        return !empty($this->candles);
    }

    public function getLatestPrice(): ?float
    {
        if (!empty($this->candles)) {
            $lastKey = array_key_last($this->candles);
            $last = $this->candles[$lastKey] ?? null;
            return isset($last['close']) ? (float)$last['close'] : null;
        }
        return null;
    }

    /**
     * Compute a deterministic hash of the context for provenance and deduplication.
     */
    public function getOrGenerateInputHash(): string
    {
        if ($this->inputHash) {
            return $this->inputHash;
        }

        $imageHash = ($this->imagePath && file_exists($this->imagePath)) ? md5_file($this->imagePath) : '';
        $candleSample = !empty($this->candles) ? md5(json_encode(array_slice($this->candles, -20))) : '';

        return md5(implode('|', [
            $this->symbol,
            $this->timeframe,
            $this->strategy,
            $imageHash,
            $candleSample,
            $this->notes ?? '',
        ]));
    }

    /**
     * Convert to serializable array for prompt injection or logging.
     */
    public function toContextArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'timeframe' => $this->timeframe,
            'strategy' => $this->strategy,
            'data_source' => $this->dataSource,
            'has_candles' => $this->hasCandles(),
            'candles_count' => !empty($this->candles) ? count($this->candles) : 0,
            'latest_price' => $this->getLatestPrice(),
            'deterministic_indicators' => $this->indicators ?? [],
            'deterministic_market_structure' => $this->marketStructure ?? [],
            'deterministic_support_resistance' => $this->supportResistance ?? [],
            'deterministic_smc' => $this->smc ?? [],
            'deterministic_fibonacci' => $this->fibonacci ?? [],
            'indicator_parameters' => $this->parameters ?? [],
            'macro_news' => $this->macroNews ? [
                'sentiment_bias' => $this->macroNews['sentiment_bias'] ?? 'NEUTRAL',
                'macro_narrative' => $this->macroNews['macro_narrative'] ?? '',
                'high_impact_warning' => $this->macroNews['high_impact_warning'] ?? false,
            ] : null,
            'market_regime' => $this->marketRegime ?? [],
            'retrieved_strategies' => $this->retrievedStrategies ?? [],
            'notes' => $this->notes,
        ];
    }
}
