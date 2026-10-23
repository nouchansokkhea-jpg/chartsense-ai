<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'strategy_id',
        'symbol',
        'timeframe',
        'market_regime',
        'sample_size_trades',
        'wins',
        'losses',
        'win_rate_pct',
        'profit_factor',
        'expectancy_r',
        'max_drawdown_r',
        'net_profit_r',
        'avg_rr_ratio',
        'slippage_sensitivity_score',
        'out_of_sample_tested',
        'ranking_tier',
        'evaluation_notes',
        'evaluated_at',
    ];

    protected $casts = [
        'win_rate_pct' => 'float',
        'profit_factor' => 'float',
        'expectancy_r' => 'float',
        'max_drawdown_r' => 'float',
        'net_profit_r' => 'float',
        'avg_rr_ratio' => 'float',
        'slippage_sensitivity_score' => 'float',
        'out_of_sample_tested' => 'boolean',
        'evaluated_at' => 'datetime',
    ];

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class, 'strategy_id');
    }
}
