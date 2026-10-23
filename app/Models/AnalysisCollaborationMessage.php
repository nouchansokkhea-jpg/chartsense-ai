<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisCollaborationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'analysis_run_id',
        'session_id',
        'round_number',
        'stage',
        'sender_role',
        'sender_provider',
        'sender_model',
        'target_role',
        'message_type',
        'claim',
        'evidence_references',
        'related_claim_ids',
        'objection_response',
        'validation_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'round_number' => 'integer',
            'evidence_references' => 'array',
            'related_claim_ids' => 'array',
            'metadata' => 'array',
        ];
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class);
    }
}
