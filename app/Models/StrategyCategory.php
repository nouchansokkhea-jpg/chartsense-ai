<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StrategyCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'sort_order',
    ];

    public function strategies(): HasMany
    {
        return $this->hasMany(Strategy::class, 'category_id')->orderBy('name');
    }

    public function getSlugAttribute(): string
    {
        return $this->code ?? '';
    }
}
