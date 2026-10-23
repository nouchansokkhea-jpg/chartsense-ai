<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\DTOs\AnalysisContext;

class IndependentReviewerAnalyst extends AbstractAnalyst
{
    public function getRole(): string
    {
        return 'independent_reviewer';
    }

    public function getRoleTitle(): string
    {
        return 'Independent Reviewer & Red Team Challenger';
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are the Independent Reviewer & Red Team Challenger for ChartSense AI.
Your objective: Act as the critical skeptic and devil's advocate. Challenge consensus bias, expose hidden liquidity traps, identify fakeouts, scrutinize stop loss vulnerability, and challenge overly optimistic profit targets.
CRITICAL RULES:
1. Actively look for reasons NOT to take the trade.
2. If the proposed setup is vulnerable to liquidity runs, false breakouts, or conflicting macro headwinds, issue an explicit RED TEAM CHALLENGE.
3. If risk factors undermine the trade's statistical edge, conclude "WAIT" or "NO VALID SETUP".
4. Return output strictly in valid JSON matching this schema:
{
  "conclusion": "BULLISH" | "BEARISH" | "WAIT" | "NO VALID SETUP",
  "confidence": "low" | "moderate" | "high",
  "summary": "1-2 sentence skeptic assessment of setup vulnerabilities.",
  "findings": ["Primary vulnerability finding", "Liquidity sweep/trap finding", "Stop loss placement hazard"],
  "supporting_points": ["Valid setup point if any"],
  "contradictory_points": ["Severe counter-argument 1", "Severe counter-argument 2"],
  "red_team_audit": {
    "fakeout_probability": "low" | "moderate" | "high",
    "stop_hunt_risk": "low" | "moderate" | "high",
    "consensus_blindspot": "Overlooked structural or news conflict",
    "recommendation": "PROCEED_WITH_CAUTION" | "REVISE_LEVELS" | "ABSTAIN_WAIT"
  }
}
PROMPT;
    }

    protected function buildUserPrompt(AnalysisContext $context): string
    {
        $payload = [
            'instrument' => $context->symbol,
            'timeframe' => $context->timeframe,
            'latest_price' => $context->getLatestPrice(),
            'market_regime' => $context->marketRegime['regime'] ?? 'UNCERTAIN',
            'support_resistance' => $context->supportResistance,
            'smc_liquidity' => $context->smc,
            'macro_news_risk' => $context->macroNews,
            'user_notes' => $context->notes,
        ];

        return $this->formatContextData($payload);
    }
}
