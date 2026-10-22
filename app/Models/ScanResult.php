<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'direction',
        'trend',
        'confidence',
        'entry_type',
        'entry_min',
        'entry_max',
        'entry_target',
        'stop_loss',
        'take_profit_1',
        'take_profit_2',
        'take_profit_3',
        'risk_reward_ratio',
        'risk_distance',
        'reward_distance_tp1',
        'technical_explanation',
        'reasoning',
        'confirmation_conditions',
        'invalidation_conditions',
        'warnings',
        'support_levels',
        'resistance_levels',
        'calculated_indicators',
        'market_structure',
        'strategy_confluences',
        'macro_news_context',
        'full_ai_response',
    ];

    protected function casts(): array
    {
        return [
            'entry_min' => 'float',
            'entry_max' => 'float',
            'entry_target' => 'float',
            'stop_loss' => 'float',
            'take_profit_1' => 'float',
            'take_profit_2' => 'float',
            'take_profit_3' => 'float',
            'risk_reward_ratio' => 'float',
            'risk_distance' => 'float',
            'reward_distance_tp1' => 'float',
            'reasoning' => 'array',
            'confirmation_conditions' => 'array',
            'invalidation_conditions' => 'array',
            'warnings' => 'array',
            'support_levels' => 'array',
            'resistance_levels' => 'array',
            'calculated_indicators' => 'array',
            'market_structure' => 'array',
            'strategy_confluences' => 'array',
            'macro_news_context' => 'array',
            'full_ai_response' => 'array',
        ];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }
}
