<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResearchSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'base_url',
        'feed_url',
        'licensing_terms',
        'credibility_score',
        'is_active',
        'last_crawled_at',
    ];

    protected function casts(): array
    {
        return [
            'credibility_score' => 'integer',
            'is_active' => 'boolean',
            'last_crawled_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
