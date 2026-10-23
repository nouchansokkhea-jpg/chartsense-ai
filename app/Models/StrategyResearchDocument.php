<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyResearchDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'source_url',
        'raw_text',
        'extracted_summary',
        'extracted_rules',
        'suggested_strategy_id',
        'review_status',
        'reviewer_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'extracted_rules' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function suggestedStrategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class, 'suggested_strategy_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
