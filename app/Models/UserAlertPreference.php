<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAlertPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'telegram_chat_id',
        'notify_approaching_events',
        'notify_trade_blocks',
        'notify_economic_releases',
        'notify_model_disagreements',
        'is_telegram_connected',
    ];

    protected $casts = [
        'notify_approaching_events' => 'boolean',
        'notify_trade_blocks' => 'boolean',
        'notify_economic_releases' => 'boolean',
        'notify_model_disagreements' => 'boolean',
        'is_telegram_connected' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
