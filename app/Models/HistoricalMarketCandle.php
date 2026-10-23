<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricalMarketCandle extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'timeframe',
        'candle_timestamp',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'volume_type',
        'spread',
        'trading_session',
        'data_source',
        'quality_score',
        'is_gap',
        'quality_notes',
    ];

    protected function casts(): array
    {
        return [
            'candle_timestamp' => 'datetime',
            'open' => 'float',
            'high' => 'float',
            'low' => 'float',
            'close' => 'float',
            'volume' => 'float',
            'spread' => 'float',
            'quality_score' => 'integer',
            'is_gap' => 'boolean',
        ];
    }

    /**
     * Scope to retrieve verified, high-quality candles for backtesting and AI modeling.
     */
    public function scopeVerifiedQuality($query, int $minScore = 80)
    {
        return $query->where('quality_score', '>=', $minScore)->where('is_gap', false);
    }

    /**
     * Scope for specific symbol and timeframe.
     */
    public function scopeForInstrument($query, string $symbol, string $timeframe)
    {
        return $query->where('symbol', strtoupper($symbol))->where('timeframe', strtoupper($timeframe));
    }
}
