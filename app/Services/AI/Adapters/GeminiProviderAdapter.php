<?php

namespace App\Services\AI\Adapters;

use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\DTOs\AnalysisContext;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProviderAdapter extends AbstractAiProviderAdapter
{
    public function getProviderId(): string
    {
        return 'gemini';
    }

    public function supportsVision(): bool
    {
        return true;
    }

    public function analyze(AnalysisContext $context, string $role, string $systemPrompt = '', string $userPrompt = ''): AgentAnalysisResult
    {
        $start = hrtime(true);

        if (empty($this->apiKey)) {
            return AgentAnalysisResult::failure($role, 'gemini', $this->modelId, 'Gemini API key is not configured.', 0);
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->modelId}:generateContent?key={$this->apiKey}";

        $parts = [];
        // Inject system prompt + user instructions
        $combinedText = "{$systemPrompt}\n\n---\nMARKET ANALYSIS DATA:\n{$userPrompt}\n\nRespond strictly in valid JSON matching the requested schema.";
        $parts[] = ['text' => $combinedText];

        // Attach image if vision is supported and image is provided
        if ($context->hasVision() && $this->supportsVision()) {
            $imagePath = $context->imagePath;
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $imageData,
                ],
            ];
        }

        $payload = [
            'contents' => [
                ['parts' => $parts]
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'temperature' => 0.1,
                'maxOutputTokens' => $this->maxTokens,
            ],
        ];

        try {
            $response = Http::timeout($this->timeoutSeconds)->post($url, $payload);
            $durationMs = (int) round((hrtime(true) - $start) / 1e6);

            if (!$response->successful()) {
                $err = "Gemini API HTTP {$response->status()}: " . substr($response->body(), 0, 300);
                Log::warning("Gemini adapter error: {$err}");
                return AgentAnalysisResult::failure($role, 'gemini', $this->modelId, $err, $durationMs);
            }

            $json = $response->json();
            $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
            $parsed = $this->parseJsonOutput($rawText);

            return $this->transformToResult($role, $parsed, $durationMs, $rawText);
        } catch (Exception $e) {
            $durationMs = (int) round((hrtime(true) - $start) / 1e6);
            Log::warning("Gemini adapter exception [{$role}]: " . $e->getMessage());
            return AgentAnalysisResult::failure($role, 'gemini', $this->modelId, $e->getMessage(), $durationMs);
        }
    }
}
