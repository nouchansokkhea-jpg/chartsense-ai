<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisAgentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'analysis_run_id',
        'role',
        'provider',
        'model',
        'status',
        'execution_duration_ms',
        'conclusion',
        'confidence',
        'summary',
        'findings',
        'supporting_points',
        'contradictory_points',
        'proposed_levels',
        'risk_review',
        'raw_response',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'execution_duration_ms' => 'integer',
            'findings' => 'array',
            'supporting_points' => 'array',
            'contradictory_points' => 'array',
            'proposed_levels' => 'array',
            'risk_review' => 'array',
            'raw_response' => 'array',
        ];
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class);
    }
}
