<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'analysis_run_id',
        'event_type',
        'details',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class);
    }

    public static function log(int $runId, string $eventType, ?array $details = null): self
    {
        return self::create([
            'analysis_run_id' => $runId,
            'event_type' => $eventType,
            'details' => $details,
            'created_at' => now(),
        ]);
    }
}
