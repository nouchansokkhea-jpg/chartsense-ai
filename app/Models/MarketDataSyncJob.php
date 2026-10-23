<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketDataSyncJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'timeframe',
        'data_source',
        'window_start',
        'window_end',
        'last_synced_candle_at',
        'candles_synced',
        'gaps_detected',
        'status',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'window_start' => 'datetime',
            'window_end' => 'datetime',
            'last_synced_candle_at' => 'datetime',
            'candles_synced' => 'integer',
            'gaps_detected' => 'integer',
            'metadata' => 'array',
        ];
    }
}
