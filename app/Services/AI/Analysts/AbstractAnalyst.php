<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\Contracts\AiAnalysisProviderInterface;
use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\DTOs\AnalysisContext;

abstract class AbstractAnalyst
{
    /**
     * Unique role identifier (e.g., 'price_action', 'market_structure', etc.).
     */
    abstract public function getRole(): string;

    /**
     * Human-readable role title.
     */
    abstract public function getRoleTitle(): string;

    /**
     * System instructions defining domain responsibilities and constraints.
     */
    abstract protected function buildSystemPrompt(): string;

    /**
     * Build structured prompt providing standardized market context.
     */
    abstract protected function buildUserPrompt(AnalysisContext $context): string;

    /**
     * Execute role analysis using the given provider adapter.
     */
    public function analyze(AnalysisContext $context, AiAnalysisProviderInterface $adapter): AgentAnalysisResult
    {
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($context);

        return $adapter->analyze($context, $this->getRole(), $systemPrompt, $userPrompt);
    }

    /**
     * Shared helper to serialize context into readable JSON string.
     */
    protected function formatContextData(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
