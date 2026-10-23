<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EconomicCalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_code',
        'event_name',
        'country_code',
        'currency',
        'scheduled_at',
        'importance',
        'previous_value',
        'forecast_value',
        'actual_value',
        'revised_value',
        'unit',
        'surprise_score',
        'surprise_direction',
        'status',
        'official_source',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'surprise_score' => 'float',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(NewsProvider::class, 'provider_code', 'code');
    }

    public function tradeRestrictions(): HasMany
    {
        return $this->hasMany(TradeRestriction::class, 'economic_event_id');
    }

    /**
     * Determine if event is proximate (within X minutes).
     */
    public function isWithinMinutes(int $minutes): bool
    {
        $now = now();
        $diff = $now->diffInMinutes($this->scheduled_at, false);
        return $diff >= 0 && $diff <= $minutes;
    }
}
