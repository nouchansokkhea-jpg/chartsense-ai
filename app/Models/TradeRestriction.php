<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeRestriction extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'economic_event_id',
        'status',
        'severity',
        'reason',
        'activated_at',
        'expires_at',
        'cleared_at',
        'override_prohibited',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'cleared_at' => 'datetime',
        'override_prohibited' => 'boolean',
    ];

    public function economicEvent(): BelongsTo
    {
        return $this->belongsTo(EconomicCalendarEvent::class, 'economic_event_id');
    }

    public function isBlocked(): bool
    {
        return $this->status === 'BLOCKED' && now()->lt($this->expires_at);
    }
}
