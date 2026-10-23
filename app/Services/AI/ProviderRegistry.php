<?php

namespace App\Services\AI;

use App\Models\SystemSetting;
use App\Services\AI\Adapters\ClaudeProviderAdapter;
use App\Services\AI\Adapters\DeepSeekProviderAdapter;
use App\Services\AI\Adapters\DeterministicRuleAdapter;
use App\Services\AI\Adapters\GeminiProviderAdapter;
use App\Services\AI\Adapters\OpenAiProviderAdapter;
use App\Services\AI\Contracts\AiAnalysisProviderInterface;

class ProviderRegistry
{
    /**
     * @var array<string, AiAnalysisProviderInterface>
     */
    protected array $resolvedAdapters = [];

    /**
     * Get the AI Provider Adapter assigned to a specific analysis role.
     */
    public function getAdapterForRole(string $role): AiAnalysisProviderInterface
    {
        $roleConfig = config("chartsense.role_assignments.{$role}", [
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
        ]);

        $provider = SystemSetting::get("ai_role_{$role}_provider", $roleConfig['provider']);
        $model = SystemSetting::get("ai_role_{$role}_model", $roleConfig['model']);

        return $this->getAdapter($provider, $model);
    }

    /**
     * Get or instantiate an AI Provider Adapter.
     */
    public function getAdapter(string $provider, ?string $model = null): AiAnalysisProviderInterface
    {
        $provider = strtolower(trim($provider));
        $cacheKey = "{$provider}:" . ($model ?? 'default');

        if (isset($this->resolvedAdapters[$cacheKey])) {
            return $this->resolvedAdapters[$cacheKey];
        }

        $timeout = (int) config('chartsense.timeouts.per_model_seconds', 45);

        // Resolve API Key: First check SystemSetting, then config/env
        $apiKey = $this->resolveApiKey($provider);
        $modelId = $model ?: config("chartsense.providers.{$provider}.default_model", 'default');

        // If external API key is missing and provider is not deterministic, fallback safely
        if ($provider !== 'deterministic' && empty($apiKey)) {
            $adapter = new DeterministicRuleAdapter('internal', 'rule-engine-v2', $timeout);
            $this->resolvedAdapters[$cacheKey] = $adapter;
            return $adapter;
        }

        $adapter = match ($provider) {
            'gemini' => new GeminiProviderAdapter($apiKey, $modelId, $timeout),
            'claude' => new ClaudeProviderAdapter($apiKey, $modelId, $timeout),
            'openai' => new OpenAiProviderAdapter($apiKey, $modelId, $timeout),
            'deepseek' => new DeepSeekProviderAdapter($apiKey, $modelId, $timeout),
            default => new DeterministicRuleAdapter('internal', 'rule-engine-v2', $timeout),
        };

        $this->resolvedAdapters[$cacheKey] = $adapter;
        return $adapter;
    }

    /**
     * Resolve API key from database or environment.
     */
    protected function resolveApiKey(string $provider): string
    {
        $dbKey = SystemSetting::get("ai_{$provider}_api_key");
        if (!empty($dbKey)) {
            return $dbKey;
        }

        // Generic fallback key in system settings
        $genericDbKey = SystemSetting::get('ai_api_key');
        if (!empty($genericDbKey) && in_array($provider, ['gemini', 'openai'])) {
            return $genericDbKey;
        }

        return (string) config("chartsense.providers.{$provider}.api_key", '');
    }
}
