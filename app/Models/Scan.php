<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Scan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'symbol',
        'timeframe',
        'input_type',
        'data_source',
        'strategy',
        'status',
        'started_at',
        'completed_at',
        'error_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function input(): HasOne
    {
        return $this->hasOne(ScanInput::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(ScanResult::class);
    }

    public function marketData(): HasMany
    {
        return $this->hasMany(MarketData::class);
    }

    public function analysisRun(): HasOne
    {
        return $this->hasOne(AnalysisRun::class)->latestOfMany();
    }
}
