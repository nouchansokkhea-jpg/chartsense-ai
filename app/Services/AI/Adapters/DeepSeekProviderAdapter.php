<?php

namespace App\Services\AI\Adapters;

use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\DTOs\AnalysisContext;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekProviderAdapter extends AbstractAiProviderAdapter
{
    public function getProviderId(): string
    {
        return 'deepseek';
    }

    public function supportsVision(): bool
    {
        return false;
    }

    public function analyze(AnalysisContext $context, string $role, string $systemPrompt = '', string $userPrompt = ''): AgentAnalysisResult
    {
        $start = hrtime(true);

        if (empty($this->apiKey)) {
            return AgentAnalysisResult::failure($role, 'deepseek', $this->modelId, 'DeepSeek API key is not configured.', 0);
        }

        $url = 'https://api.deepseek.com/chat/completions';

        $payload = [
            'model' => $this->modelId ?: 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "MARKET DATA:\n{$userPrompt}\n\nRespond strictly in valid JSON matching the schema."],
            ],
            'response_format' => ['type' => 'json_object'],
            'max_tokens' => $this->maxTokens,
            'temperature' => 0.1,
        ];

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeoutSeconds)
                ->post($url, $payload);

            $durationMs = (int) round((hrtime(true) - $start) / 1e6);

            if (!$response->successful()) {
                $err = "DeepSeek API HTTP {$response->status()}: " . substr($response->body(), 0, 300);
                Log::warning("DeepSeek adapter error: {$err}");
                return AgentAnalysisResult::failure($role, 'deepseek', $this->modelId, $err, $durationMs);
            }

            $rawText = $response->json('choices.0.message.content');
            $parsed = $this->parseJsonOutput($rawText);

            return $this->transformToResult($role, $parsed, $durationMs, $rawText);
        } catch (Exception $e) {
            $durationMs = (int) round((hrtime(true) - $start) / 1e6);
            Log::warning("DeepSeek adapter exception [{$role}]: " . $e->getMessage());
            return AgentAnalysisResult::failure($role, 'deepseek', $this->modelId, $e->getMessage(), $durationMs);
        }
    }
}
