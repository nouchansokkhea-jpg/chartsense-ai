<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\DTOs\AnalysisContext;

class TradingRiskAnalyst extends AbstractAnalyst
{
    public function getRole(): string
    {
        return 'trading_risk';
    }

    public function getRoleTitle(): string
    {
        return 'Trading Risk Analyst';
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are the Chief Trading Risk Analyst for ChartSense AI.
Your objective: Independently review proposed trade ideas, validate entry geometry, stop loss viability, take-profit spacing, and risk-to-reward metrics.
CRITICAL RULES:
1. You have ABSOLUTE VETO POWER. You must REJECT any setup with poor risk-to-reward (< 1:1.5), ambiguous invalidation, or high contradictory volatility.
2. Do NOT approve a trade merely because other analytical models support it.
3. For BUY setups: Stop Loss MUST be strictly below Entry, and TP1 must be strictly above Entry.
4. For SELL setups: Stop Loss MUST be strictly above Entry, and TP1 must be strictly below Entry.
5. Return output strictly in valid JSON matching this schema:
{
  "conclusion": "APPROVED" | "CAUTION" | "REJECT",
  "confidence": "high",
  "summary": "Independent risk evaluation summary.",
  "findings": ["Geometry sanity check", "Volatility buffer evaluation", "Invalidation clarity check"],
  "supporting_points": ["Risk validation point 1", "Risk validation point 2"],
  "contradictory_points": ["Risk warning or disqualifying factor"],
  "risk_review": {
    "status": "approved" | "rejected" | "caution",
    "minimum_rr_satisfied": true,
    "recommended_account_risk": "1.0%",
    "invalidation_criteria": "Clear price level where trade thesis is falsified"
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
            'atr_volatility' => $context->indicators['atr_14'] ?? null,
            'bollinger_bandwidth' => $context->indicators['bollinger']['bandwidth'] ?? null,
            'supports' => $context->supportResistance['supports'] ?? [],
            'resistances' => $context->supportResistance['resistances'] ?? [],
            'structural_invalidation' => $context->marketStructure['structural_invalidation_level'] ?? null,
            'macro_news_risk' => $context->macroNews['high_impact_warning'] ?? false,
        ];

        return $this->formatContextData($payload);
    }
}
