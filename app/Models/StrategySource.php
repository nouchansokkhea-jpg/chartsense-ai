<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategySource extends Model
{
    use HasFactory;

    protected $fillable = [
        'strategy_id',
        'title',
        'author',
        'publication_year',
        'source_url',
        'license_type',
        'attribution_text',
        'notes',
    ];

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class, 'strategy_id');
    }
}
