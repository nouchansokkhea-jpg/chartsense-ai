<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiModelRegistry extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'model_id',
        'display_name',
        'assigned_roles',
        'supports_vision',
        'supports_structured_output',
        'supports_tool_calling',
        'supports_fine_tuning',
        'context_window_tokens',
        'cost_per_1k_input_tokens',
        'cost_per_1k_output_tokens',
        'avg_latency_ms',
        'evaluation_status',
        'configuration_version',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_roles' => 'array',
            'supports_vision' => 'boolean',
            'supports_structured_output' => 'boolean',
            'supports_tool_calling' => 'boolean',
            'supports_fine_tuning' => 'boolean',
            'context_window_tokens' => 'integer',
            'cost_per_1k_input_tokens' => 'float',
            'cost_per_1k_output_tokens' => 'float',
            'avg_latency_ms' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('evaluation_status', 'active');
    }

    public function scopeVisionCapable($query)
    {
        return $query->where('supports_vision', true);
    }

    public function scopeFineTuningCapable($query)
    {
        return $query->where('supports_fine_tuning', true);
    }
}
