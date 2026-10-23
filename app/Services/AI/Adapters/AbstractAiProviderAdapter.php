<?php

namespace App\Services\AI\Adapters;

use App\Services\AI\Contracts\AiAnalysisProviderInterface;
use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\DTOs\AnalysisContext;
use Exception;
use Illuminate\Support\Facades\Log;

abstract class AbstractAiProviderAdapter implements AiAnalysisProviderInterface
{
    public function __construct(
        protected string $apiKey = '',
        protected string $modelId = '',
        protected int $timeoutSeconds = 45,
        protected int $maxTokens = 2000
    ) {}

    public function getModelId(): string
    {
        return $this->modelId;
    }

    public function supportsText(): bool
    {
        return true;
    }

    public function supportsVision(): bool
    {
        return false;
    }

    /**
     * Safely parse JSON from raw LLM output text.
     */
    protected function parseJsonOutput(?string $text): ?array
    {
        if (empty($text)) {
            return null;
        }

        $cleanText = trim($text);

        // Strip ```json ... ``` markdown fences if present
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $cleanText, $matches)) {
            $cleanText = trim($matches[1]);
        }

        // Search for outermost JSON object { ... }
        if (preg_match('/\{[\s\S]*\}/', $cleanText, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        $decoded = json_decode($cleanText, true);
        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : null;
    }

    /**
     * Transform parsed JSON into standardized AgentAnalysisResult.
     */
    protected function transformToResult(
        string $role,
        ?array $data,
        int $durationMs,
        ?string $rawText = null
    ): AgentAnalysisResult {
        if (!$data) {
            return AgentAnalysisResult::failure(
                role: $role,
                provider: $this->getProviderId(),
                model: $this->getModelId(),
                errorMessage: 'AI model returned non-parseable JSON response.',
                durationMs: $durationMs
            );
        }

        $conclusion = strtoupper(trim((string)($data['conclusion'] ?? ($data['bias'] ?? ($data['direction'] ?? 'WAIT')))));
        if (!in_array($conclusion, ['BULLISH', 'BEARISH', 'NEUTRAL', 'WAIT', 'REJECT', 'BUY', 'SELL'])) {
            $conclusion = 'WAIT';
        }
        if ($conclusion === 'BUY') $conclusion = 'BULLISH';
        if ($conclusion === 'SELL') $conclusion = 'BEARISH';

        $confidence = strtolower(trim((string)($data['confidence'] ?? 'moderate')));
        if (!in_array($confidence, ['low', 'moderate', 'high'])) {
            $confidence = 'moderate';
        }

        $summary = (string)($data['summary'] ?? ($data['technical_explanation'] ?? 'Analysis completed.'));
        $findings = is_array($data['findings'] ?? null) ? $data['findings'] : [];
        $supporting = is_array($data['supporting_points'] ?? ($data['reasoning'] ?? null)) ? ($data['supporting_points'] ?? $data['reasoning']) : [];
        $contradictory = is_array($data['contradictory_points'] ?? ($data['warnings'] ?? null)) ? ($data['contradictory_points'] ?? $data['warnings']) : [];

        $levels = null;
        if (isset($data['proposed_levels']) && is_array($data['proposed_levels'])) {
            $levels = $data['proposed_levels'];
        } elseif (isset($data['entry_target']) || isset($data['stop_loss'])) {
            $levels = [
                'entry' => $data['entry_target'] ?? null,
                'stop_loss' => $data['stop_loss'] ?? null,
                'take_profit_1' => $data['take_profit_1'] ?? null,
                'take_profit_2' => $data['take_profit_2'] ?? null,
                'take_profit_3' => $data['take_profit_3'] ?? null,
                'risk_reward' => $data['risk_reward_ratio'] ?? null,
            ];
        }

        $riskReview = isset($data['risk_review']) && is_array($data['risk_review']) ? $data['risk_review'] : null;

        return new AgentAnalysisResult(
            role: $role,
            provider: $this->getProviderId(),
            model: $this->getModelId(),
            status: 'success',
            conclusion: $conclusion,
            confidence: $confidence,
            summary: $summary,
            findings: $findings,
            supportingPoints: $supporting,
            contradictoryPoints: $contradictory,
            proposedLevels: $levels,
            riskReview: $riskReview,
            durationMs: $durationMs,
            rawResponse: $data,
            errorMessage: null
        );
    }
}
