<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenshotEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'screenshot_path',
        'detected_symbol',
        'detected_timeframe',
        'detected_structures',
        'verified_symbol',
        'verified_timeframe',
        'ground_truth_matched',
        'price_extraction_error_pct',
        'structure_recall_score',
        'confidence_score',
        'calibration_error',
        'reviewer_corrections',
        'evaluation_notes',
    ];

    protected function casts(): array
    {
        return [
            'detected_structures' => 'array',
            'ground_truth_matched' => 'boolean',
            'price_extraction_error_pct' => 'float',
            'structure_recall_score' => 'float',
            'confidence_score' => 'float',
            'calibration_error' => 'float',
            'reviewer_corrections' => 'array',
        ];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }
}
