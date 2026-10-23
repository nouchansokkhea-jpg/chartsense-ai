<?php

namespace App\Services\AI\Adapters;

use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\DTOs\AnalysisContext;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiProviderAdapter extends AbstractAiProviderAdapter
{
    public function getProviderId(): string
    {
        return 'openai';
    }

    public function supportsVision(): bool
    {
        return true;
    }

    public function analyze(AnalysisContext $context, string $role, string $systemPrompt = '', string $userPrompt = ''): AgentAnalysisResult
    {
        $start = hrtime(true);

        if (empty($this->apiKey)) {
            return AgentAnalysisResult::failure($role, 'openai', $this->modelId, 'OpenAI API key is not configured.', 0);
        }

        $url = 'https://api.openai.com/v1/chat/completions';
        $userParts = [];

        // Attach image if vision is supported and image is provided
        if ($context->hasVision() && $this->supportsVision()) {
            $imagePath = $context->imagePath;
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
            $userParts[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => "data:{$mimeType};base64,{$imageData}",
                    'detail' => 'high',
                ],
            ];
        }

        $userParts[] = [
            'type' => 'text',
            'text' => "MARKET ANALYSIS DATA:\n{$userPrompt}\n\nRespond strictly in valid JSON matching the schema.",
        ];

        $payload = [
            'model' => $this->modelId ?: 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userParts],
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
                $err = "OpenAI API HTTP {$response->status()}: " . substr($response->body(), 0, 300);
                Log::warning("OpenAI adapter error: {$err}");
                return AgentAnalysisResult::failure($role, 'openai', $this->modelId, $err, $durationMs);
            }

            $rawText = $response->json('choices.0.message.content');
            $parsed = $this->parseJsonOutput($rawText);

            return $this->transformToResult($role, $parsed, $durationMs, $rawText);
        } catch (Exception $e) {
            $durationMs = (int) round((hrtime(true) - $start) / 1e6);
            Log::warning("OpenAI adapter exception [{$role}]: " . $e->getMessage());
            return AgentAnalysisResult::failure($role, 'openai', $this->modelId, $e->getMessage(), $durationMs);
        }
    }
}
