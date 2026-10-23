<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeOutcome extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'strategy_id',
        'symbol',
        'timeframe',
        'market_regime',
        'trade_type',
        'direction',
        'predicted_at',
        'entry_price',
        'stop_loss',
        'take_profit',
        'exit_price',
        'exit_timestamp',
        'outcome',
        'realized_r_multiple',
        'max_favorable_excursion_r',
        'max_adverse_excursion_r',
        'slippage_deduction_r',
        'evaluation_horizon_candles',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'predicted_at' => 'datetime',
            'entry_price' => 'float',
            'stop_loss' => 'float',
            'take_profit' => 'float',
            'exit_price' => 'float',
            'exit_timestamp' => 'datetime',
            'realized_r_multiple' => 'float',
            'max_favorable_excursion_r' => 'float',
            'max_adverse_excursion_r' => 'float',
            'slippage_deduction_r' => 'float',
            'evaluation_horizon_candles' => 'integer',
        ];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('outcome', ['WIN', 'LOSS', 'BREAKEVEN']);
    }

    public function scopeRealTrades($query)
    {
        return $query->where('trade_type', 'LIVE_EXECUTED');
    }

    public function scopeSimulatedTrades($query)
    {
        return $query->whereIn('trade_type', ['SIMULATED_BACKTEST', 'PAPER_TRADE']);
    }
}
