<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_code',
        'title',
        'canonical_url',
        'summary',
        'source_name',
        'published_at',
        'received_at',
        'country_code',
        'affected_assets',
        'event_category',
        'sentiment_classification',
        'sentiment_score',
        'importance',
        'cluster_hash',
        'verification_status',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'received_at' => 'datetime',
        'affected_assets' => 'array',
        'sentiment_score' => 'float',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(NewsProvider::class, 'provider_code', 'code');
    }
}
