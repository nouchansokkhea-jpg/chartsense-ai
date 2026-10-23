<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'run_date',
        'stages_executed',
        'candles_ingested',
        'features_calculated',
        'discoveries_made',
        'strategies_evaluated',
        'candidate_proposals_generated',
        'proposals_promoted',
        'proposals_rejected',
        'data_quality_issues',
        'status',
        'execution_seconds',
        'summary_report',
    ];

    protected function casts(): array
    {
        return [
            'run_date' => 'date',
            'stages_executed' => 'array',
            'candles_ingested' => 'integer',
            'features_calculated' => 'integer',
            'discoveries_made' => 'integer',
            'strategies_evaluated' => 'integer',
            'candidate_proposals_generated' => 'integer',
            'proposals_promoted' => 'integer',
            'proposals_rejected' => 'integer',
            'data_quality_issues' => 'array',
            'execution_seconds' => 'integer',
        ];
    }
}
