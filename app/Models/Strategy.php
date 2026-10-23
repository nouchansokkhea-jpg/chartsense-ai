<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Strategy extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'slug',
        'name',
        'category_name',
        'description',
        'intended_market_conditions',
        'market_regime_suitability',
        'timeframes',
        'supported_instruments',
        'trading_sessions',
        'required_indicators',
        'required_market_data',
        'risk_constraints',
        'known_weaknesses',
        'implementation_status',
        'backtesting_status',
        'evidence_confidence',
        'is_active',
    ];

    protected $casts = [
        'market_regime_suitability' => 'array',
        'timeframes' => 'array',
        'supported_instruments' => 'array',
        'trading_sessions' => 'array',
        'required_indicators' => 'array',
        'required_market_data' => 'array',
        'risk_constraints' => 'array',
        'known_weaknesses' => 'array',
        'is_active' => 'boolean',
        'evidence_confidence' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(StrategyCategory::class, 'category_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(StrategyVersion::class, 'strategy_id');
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(StrategyVersion::class, 'strategy_id')->where('is_current', true);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(StrategySource::class, 'strategy_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(StrategyEvaluation::class, 'strategy_id');
    }

    /**
     * Determine if strategy is suitable for a given regime.
     */
    public function isSuitableForRegime(string $regime): bool
    {
        $regimes = $this->market_regime_suitability ?? [];
        if (empty($regimes) || in_array('all', $regimes) || in_array('any', $regimes)) {
            return true;
        }

        $regimeClean = strtolower(str_replace(' ', '_', $regime));
        foreach ($regimes as $r) {
            if (strtolower(str_replace(' ', '_', $r)) === $regimeClean) {
                return true;
            }
        }

        return false;
    }
}
