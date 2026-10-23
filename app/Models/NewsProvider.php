<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'base_url',
        'is_active',
        'rate_limit_per_minute',
        'last_sync_at',
        'health_status',
        'health_message',
        'latency_ms',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
        'rate_limit_per_minute' => 'integer',
        'latency_ms' => 'integer',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(NewsArticle::class, 'provider_code', 'code');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EconomicCalendarEvent::class, 'provider_code', 'code');
    }
}
