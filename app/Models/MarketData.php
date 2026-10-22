<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketData extends Model
{
    use HasFactory;

    protected $table = 'market_data';

    protected $fillable = [
        'scan_id',
        'symbol',
        'timeframe',
        'candle_timestamp',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'source',
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
        ];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }
}
