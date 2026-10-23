<?php

namespace App\Services\AI\Adapters;

use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\DTOs\AnalysisContext;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeProviderAdapter extends AbstractAiProviderAdapter
{
    public function getProviderId(): string
    {
        return 'claude';
    }

    public function supportsVision(): bool
    {
        return true;
    }

    public function analyze(AnalysisContext $context, string $role, string $systemPrompt = '', string $userPrompt = ''): AgentAnalysisResult
    {
        $start = hrtime(true);

        if (empty($this->apiKey)) {
            return AgentAnalysisResult::failure($role, 'claude', $this->modelId, 'Anthropic API key is not configured.', 0);
        }

        $url = 'https://api.anthropic.com/v1/messages';
        $content = [];

        // Attach image if vision is supported and image is provided
        if ($context->hasVision() && $this->supportsVision()) {
            $imagePath = $context->imagePath;
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
            $content[] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $mimeType,
                    'data' => $imageData,
                ],
            ];
        }

        $content[] = [
            'type' => 'text',
            'text' => "MARKET ANALYSIS DATA:\n{$userPrompt}\n\nRespond strictly in valid JSON matching the schema.",
        ];

        $payload = [
            'model' => $this->modelId ?: 'claude-3-7-sonnet-20250219',
            'max_tokens' => $this->maxTokens,
            'system' => "{$systemPrompt}\nYou must return ONLY valid, raw JSON without conversational pleasantries or markdown fencing outside of JSON.",
            'messages' => [
                ['role' => 'user', 'content' => $content],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout($this->timeoutSeconds)->post($url, $payload);

            $durationMs = (int) round((hrtime(true) - $start) / 1e6);

            if (!$response->successful()) {
                $err = "Claude API HTTP {$response->status()}: " . substr($response->body(), 0, 300);
                Log::warning("Claude adapter error: {$err}");
                return AgentAnalysisResult::failure($role, 'claude', $this->modelId, $err, $durationMs);
            }

            $rawText = $response->json('content.0.text');
            $parsed = $this->parseJsonOutput($rawText);

            return $this->transformToResult($role, $parsed, $durationMs, $rawText);
        } catch (Exception $e) {
            $durationMs = (int) round((hrtime(true) - $start) / 1e6);
            Log::warning("Claude adapter exception [{$role}]: " . $e->getMessage());
            return AgentAnalysisResult::failure($role, 'claude', $this->modelId, $e->getMessage(), $durationMs);
        }
    }
}
