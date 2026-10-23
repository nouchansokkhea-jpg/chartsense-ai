<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\DTOs\AgentAnalysisResult;
use App\Services\AI\DTOs\AnalysisContext;

class FinalDecisionAnalyst extends AbstractAnalyst
{
    public function getRole(): string
    {
        return 'final_decision';
    }

    public function getRoleTitle(): string
    {
        return 'Lead Synthesis & Final Execution Strategist';
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are the Lead Synthesis & Final Execution Strategist for ChartSense AI.
Your objective: Synthesize evidence from all 10 specialized domain analysts (Price Action, Market Structure, SMC & Liquidity, Fibonacci & Harmonics, Quantitative & Statistical, Market Regime, Multi-Timeframe, Global Fundamental & News Risk, Strategy Selector, and Independent Red Team Reviewer).
CRITICAL RULES:
1. Objectively weigh supporting and opposing evidence across all 10 domains.
2. Multiple models repeating the same point is NOT independent confirmation; detect correlated or redundant reasoning.
3. If the Trading Risk Analyst, Independent Reviewer, or Market Regime Analyst flags an unacceptable hazard or structural invalidation, your decision MUST be "WAIT" or "NO VALID SETUP".
4. If no clear strategy complies with mandatory rules or if the market is in an uncertain regime, recommend "WAIT" or "NO VALID SETUP".
5. Ground the selected strategy on empirical evidence and rule compliance.
6. Return output strictly in valid JSON matching this schema:
{
  "decision": "BUY" | "SELL" | "WAIT" | "NO VALID SETUP",
  "market_trend": "Bullish" | "Bearish" | "Sideways" | "Consolidating" | "Transitioning",
  "confidence": "low" | "moderate" | "high",
  "summary": "Clear, institutional synthesis of all 10 analyst domains and final strategy verdict.",
  "selected_strategy": {
    "name": "e.g. ICT Silver Bullet" | "NONE",
    "version": "1.0.0",
    "school": "ict_smc",
    "rationale": "Why this specific strategy best fits current price structure and regime"
  },
  "supporting_evidence": ["Key verified factor 1", "Key verified factor 2"],
  "contradictory_evidence": ["Risk or disagreement identified across analysts"],
  "confirmation_conditions": ["Specific market confirmation required before executing"],
  "invalidation_conditions": ["Exact boundary or event that invalidates setup"],
  "warnings": ["Operational, news, or regime warnings"]
}
PROMPT;
    }

    protected function buildUserPrompt(AnalysisContext $context): string
    {
        return $this->formatContextData($context->toContextArray());
    }

    /**
     * Synthesize with prior analyst outputs included in prompt.
     *
     * @param AnalysisContext $context
     * @param array<string, AgentAnalysisResult> $analystResults
     * @param \App\Services\AI\Contracts\AiAnalysisProviderInterface $adapter
     * @return AgentAnalysisResult
     */
    public function synthesize(
        AnalysisContext $context,
        array $analystResults,
        \App\Services\AI\Contracts\AiAnalysisProviderInterface $adapter
    ): AgentAnalysisResult {
        $systemPrompt = $this->buildSystemPrompt();

        $summarizedAnalysts = [];
        foreach ($analystResults as $role => $res) {
            $summarizedAnalysts[$role] = [
                'conclusion' => $res->conclusion,
                'confidence' => $res->confidence,
                'summary' => $res->summary,
                'supporting_points' => $res->supportingPoints,
                'contradictory_points' => $res->contradictoryPoints,
                'risk_review' => $res->riskReview,
            ];
        }

        $userPayload = [
            'market_context' => $context->toContextArray(),
            'analyst_reports' => $summarizedAnalysts,
        ];

        $userPrompt = $this->formatContextData($userPayload);

        return $adapter->analyze($context, $this->getRole(), $systemPrompt, $userPrompt);
    }
}
