<?php

namespace App\Services\AI\DTOs;

class AgentAnalysisResult
{
    public function __construct(
        public readonly string $role,
        public readonly string $provider,
        public readonly string $model,
        public readonly string $status, // 'success', 'failed', 'timeout'
        public readonly string $conclusion, // 'BULLISH', 'BEARISH', 'NEUTRAL', 'WAIT', 'REJECT'
        public readonly string $confidence, // 'low', 'moderate', 'high'
        public readonly string $summary,
        public readonly array $findings = [],
        public readonly array $supportingPoints = [],
        public readonly array $contradictoryPoints = [],
        public readonly ?array $proposedLevels = null,
        public readonly ?array $riskReview = null,
        public readonly int $durationMs = 0,
        public readonly ?array $rawResponse = null,
        public readonly ?string $errorMessage = null
    ) {}

    public static function failure(string $role, string $provider, string $model, string $errorMessage, int $durationMs = 0): self
    {
        return new self(
            role: $role,
            provider: $provider,
            model: $model,
            status: 'failed',
            conclusion: 'WAIT',
            confidence: 'low',
            summary: "Analysis failed: {$errorMessage}",
            findings: [],
            supportingPoints: [],
            contradictoryPoints: ["Model execution error: {$errorMessage}"],
            proposedLevels: null,
            riskReview: null,
            durationMs: $durationMs,
            rawResponse: null,
            errorMessage: $errorMessage
        );
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isBullish(): bool
    {
        return $this->conclusion === 'BULLISH';
    }

    public function isBearish(): bool
    {
        return $this->conclusion === 'BEARISH';
    }

    public function isReject(): bool
    {
        return $this->conclusion === 'REJECT';
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'provider' => $this->provider,
            'model' => $this->model,
            'status' => $this->status,
            'conclusion' => $this->conclusion,
            'confidence' => $this->confidence,
            'summary' => $this->summary,
            'findings' => $this->findings,
            'supporting_points' => $this->supportingPoints,
            'contradictory_points' => $this->contradictoryPoints,
            'proposed_levels' => $this->proposedLevels,
            'risk_review' => $this->riskReview,
            'duration_ms' => $this->durationMs,
            'error_message' => $this->errorMessage,
        ];
    }
}
