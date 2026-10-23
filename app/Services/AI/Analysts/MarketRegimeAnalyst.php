<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\DTOs\AnalysisContext;

class MarketRegimeAnalyst extends AbstractAnalyst
{
    public function getRole(): string
    {
        return 'market_regime';
    }

    public function getRoleTitle(): string
    {
        return 'Market Regime & Volatility Analyst';
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are the Market Regime & Volatility Analyst for ChartSense AI.
Your objective: Classify the underlying macro-micro market regime (Trending Bullish, Trending Bearish, Tight Range Consolidation, Expanding Range, High Volatility, or Uncertain) and determine which trading strategy categories are suitable or hazardous in this state.
CRITICAL RULES:
1. Adhere strictly to the deterministic market regime metrics (ADX, ATR percentile, Bollinger Bandwidth, MA alignment).
2. Explicitly warn against strategies that conflict with the regime (e.g., trend-following during tight range squeeze, or mean-reversion during strong ADX > 25 trends).
3. If the market regime is UNCERTAIN or excessively VOLATILE without clear structure, conclude "WAIT" or "NO VALID SETUP".
4. Return output strictly in valid JSON matching this schema:
{
  "conclusion": "BULLISH" | "BEARISH" | "WAIT" | "NO VALID SETUP",
  "confidence": "low" | "moderate" | "high",
  "summary": "1-2 sentence regime diagnosis and strategy compatibility statement.",
  "findings": ["Regime classification finding", "Volatility compression/expansion finding", "Strategy hazard warning"],
  "supporting_points": ["Regime alignment factor 1", "Regime alignment factor 2"],
  "contradictory_points": ["Regime vulnerability or conflicting timeframe dynamics"],
  "regime_assessment": {
    "detected_regime": "e.g. TRENDING_BULLISH",
    "regime_confidence_pct": 85,
    "suitable_strategies": ["ICT Silver Bullet", "Break and Retest"],
    "discouraged_strategies": ["Mean Reversion Bollinger Fade"]
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
            'deterministic_market_regime' => $context->marketRegime,
            'volatility_indicators' => [
                'atr' => $context->indicators['atr_14'] ?? null,
                'bollinger' => $context->indicators['bollinger'] ?? null,
            ],
            'user_notes' => $context->notes,
        ];

        return $this->formatContextData($payload);
    }
}
