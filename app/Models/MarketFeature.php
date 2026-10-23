<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'timeframe',
        'candle_timestamp',
        'feature_version',
        'log_return',
        'body_wick_ratio',
        'atr_14',
        'trend_strength_adx',
        'rsi_14',
        'macd_line',
        'macd_signal',
        'ema_20',
        'ema_50',
        'ema_200',
        'swing_high',
        'swing_low',
        'order_block_candidate',
        'fvg_candidate',
        'liquidity_sweep_candidate',
        'market_regime',
        'features_json',
    ];

    protected function casts(): array
    {
        return [
            'candle_timestamp' => 'datetime',
            'log_return' => 'float',
            'body_wick_ratio' => 'float',
            'atr_14' => 'float',
            'trend_strength_adx' => 'float',
            'rsi_14' => 'float',
            'macd_line' => 'float',
            'macd_signal' => 'float',
            'ema_20' => 'float',
            'ema_50' => 'float',
            'ema_200' => 'float',
            'swing_high' => 'float',
            'swing_low' => 'float',
            'order_block_candidate' => 'boolean',
            'fvg_candidate' => 'boolean',
            'liquidity_sweep_candidate' => 'boolean',
            'features_json' => 'array',
        ];
    }
}
