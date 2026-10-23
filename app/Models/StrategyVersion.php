<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrategyVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'strategy_id',
        'version_number',
        'changelog',
        'entry_rules',
        'stop_loss_rules',
        'take_profit_rules',
        'invalidation_rules',
        'exit_rules',
        'compiled_specification',
        'is_current',
    ];

    protected $casts = [
        'entry_rules' => 'array',
        'stop_loss_rules' => 'array',
        'take_profit_rules' => 'array',
        'invalidation_rules' => 'array',
        'exit_rules' => 'array',
        'compiled_specification' => 'array',
        'is_current' => 'boolean',
    ];

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class, 'strategy_id');
    }
}
