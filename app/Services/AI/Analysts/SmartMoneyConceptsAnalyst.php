<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\DTOs\AnalysisContext;

class SmartMoneyConceptsAnalyst extends AbstractAnalyst
{
    public function getRole(): string
    {
        return 'smc';
    }

    public function getRoleTitle(): string
    {
        return 'Smart Money Concepts Analyst';
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are the institutional Smart Money Concepts (SMC) Analyst for ChartSense AI.
Your objective: Evaluate liquidity areas (buy-side / sell-side), liquidity sweeps, Fair Value Gaps (FVG), order-block candidates, and displacement.
CRITICAL RULES:
1. Do NOT treat every liquidity sweep, FVG, or order block as a guaranteed trade.
2. Require displacement confirmation before validating an order block.
3. Clearly distinguish rule-based detections from visual interpretations.
4. Return output strictly in valid JSON matching this schema:
{
  "conclusion": "BULLISH" | "BEARISH" | "WAIT",
  "confidence": "low" | "moderate" | "high",
  "summary": "Concise summary of institutional liquidity pools and order block mitigations.",
  "findings": ["Liquidity sweep status", "Order block candidate evaluation", "FVG status"],
  "supporting_points": ["SMC confluence 1", "SMC confluence 2"],
  "contradictory_points": ["Unmitigated risks or lack of displacement confirmation"]
}
PROMPT;
    }

    protected function buildUserPrompt(AnalysisContext $context): string
    {
        $payload = [
            'instrument' => $context->symbol,
            'timeframe' => $context->timeframe,
            'latest_price' => $context->getLatestPrice(),
            'deterministic_smc' => $context->smc,
            'deterministic_market_structure' => $context->marketStructure,
            'support_resistance' => $context->supportResistance,
            'user_notes' => $context->notes,
        ];

        return $this->formatContextData($payload);
    }
}
