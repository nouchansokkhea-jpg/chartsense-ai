<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanInput extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'screenshot_path',
        'file_original_name',
        'raw_data_json',
        'candles_count',
        'first_candle_timestamp',
        'last_candle_timestamp',
        'file_metadata',
    ];

    protected function casts(): array
    {
        return [
            'raw_data_json' => 'array',
            'file_metadata' => 'array',
            'first_candle_timestamp' => 'datetime',
            'last_candle_timestamp' => 'datetime',
        ];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }
}
