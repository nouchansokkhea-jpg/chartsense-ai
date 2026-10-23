<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\DTOs\AnalysisContext;

interface AiAnalysisProviderInterface
{
    /**
     * Get unique provider identifier (e.g., 'gemini', 'claude', 'openai', 'deepseek', 'deterministic').
     */
    public function getProviderId(): string;

    /**
     * Get model identifier (e.g., 'gemini-2.5-flash', 'claude-3-7-sonnet', 'gpt-4o').
     */
    public function getModelId(): string;

    /**
     * Determine if this model supports multimodal vision input (chart screenshots).
     */
    public function supportsVision(): bool;

    /**
     * Determine if this model supports structured text / reasoning.
     */
    public function supportsText(): bool;

    /**
     * Execute an analytical task for a specific trading role.
     *
     * @param AnalysisContext $context Shared validated market context
     * @param string $role Role name (e.g., 'price_action', 'market_structure', etc.)
     * @param string $systemPrompt System instructions for the role
     * @param string $userPrompt Structured market prompt
     * @return AgentAnalysisResult
     */
    public function analyze(AnalysisContext $context, string $role, string $systemPrompt = '', string $userPrompt = ''): AgentAnalysisResult;
}
