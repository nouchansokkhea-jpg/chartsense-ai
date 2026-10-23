<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiModelConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_slug',
        'model_id',
        'role',
        'is_enabled',
        'supports_vision',
        'max_tokens',
        'timeout_seconds',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'supports_vision' => 'boolean',
            'max_tokens' => 'integer',
            'timeout_seconds' => 'integer',
            'priority' => 'integer',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_slug', 'slug');
    }
}
