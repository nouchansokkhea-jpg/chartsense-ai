<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AnalysisRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'user_id',
        'status',
        'input_hash',
        'total_duration_ms',
        'models_count',
        'successful_models_count',
        'consensus_decision',
        'input_metadata',
    ];

    protected function casts(): array
    {
        return [
            'total_duration_ms' => 'integer',
            'models_count' => 'integer',
            'successful_models_count' => 'integer',
            'input_metadata' => 'array',
        ];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agentResults(): HasMany
    {
        return $this->hasMany(AnalysisAgentResult::class);
    }

    public function consensusResult(): HasOne
    {
        return $this->hasOne(AnalysisConsensusResult::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnalysisEvent::class);
    }

    public function collaborationMessages(): HasMany
    {
        return $this->hasMany(AnalysisCollaborationMessage::class)->orderBy('id');
    }
}
