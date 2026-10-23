<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramAlertLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'alert_type',
        'dedup_key',
        'symbol',
        'chat_id',
        'message_text',
        'delivery_status',
        'telegram_message_id',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
