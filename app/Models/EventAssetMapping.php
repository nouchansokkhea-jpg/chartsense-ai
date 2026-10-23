<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAssetMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_pattern',
        'currency',
        'affected_symbols',
        'pre_event_block_minutes',
        'post_event_stabilization_minutes',
        'is_active',
    ];

    protected $casts = [
        'affected_symbols' => 'array',
        'pre_event_block_minutes' => 'integer',
        'post_event_stabilization_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Match a given event name and currency to an asset mapping.
     */
    public static function findMatching(string $eventName, string $currency): ?self
    {
        return static::where('is_active', true)
            ->where('currency', strtoupper($currency))
            ->get()
            ->first(function ($mapping) use ($eventName) {
                return stripos($eventName, $mapping->event_pattern) !== false;
            });
    }
}
