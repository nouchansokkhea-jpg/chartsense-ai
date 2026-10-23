<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisConsensusResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'analysis_run_id',
        'decision',
        'market_trend',
        'confidence',
        'entry_target',
        'entry_min',
        'entry_max',
        'stop_loss',
        'take_profit_1',
        'take_profit_2',
        'take_profit_3',
        'risk_reward_ratio',
        'supporting_evidence',
        'contradictory_evidence',
        'confirmation_conditions',
        'invalidation_conditions',
        'risk_review',
        'warnings',
    ];

    protected function casts(): array
    {
        return [
            'entry_target' => 'float',
            'entry_min' => 'float',
            'entry_max' => 'float',
            'stop_loss' => 'float',
            'take_profit_1' => 'float',
            'take_profit_2' => 'float',
            'take_profit_3' => 'float',
            'risk_reward_ratio' => 'float',
            'supporting_evidence' => 'array',
            'contradictory_evidence' => 'array',
            'confirmation_conditions' => 'array',
            'invalidation_conditions' => 'array',
            'risk_review' => 'array',
            'warnings' => 'array',
        ];
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class);
    }
}
