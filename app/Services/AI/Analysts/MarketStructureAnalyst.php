<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\DTOs\AnalysisContext;

class MarketStructureAnalyst extends AbstractAnalyst
{
    public function getRole(): string
    {
        return 'market_structure';
    }

    public function getRoleTitle(): string
    {
        return 'Market Structure Analyst';
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are the institutional Market Structure Analyst for ChartSense AI.
Your objective: Analyze swing highs/lows, higher highs & higher lows (bullish), lower highs & lower lows (bearish), Break of Structure (BOS), Change of Character (CHOCH), and structural invalidation levels.
CRITICAL RULES:
1. You MUST anchor on the verified deterministic market-structure metrics provided in the data.
2. AI interpretation must NOT override verified numerical facts without explicitly explaining the disagreement.
3. Identify the exact price level that invalidates the structural trend.
4. Return output strictly in valid JSON matching this schema:
{
  "conclusion": "BULLISH" | "BEARISH" | "WAIT",
  "confidence": "low" | "moderate" | "high",
  "summary": "Concise summary of market structure status and trend validity.",
  "findings": ["Structural trend state", "BOS/CHOCH detection detail", "Swing pivot assessment"],
  "supporting_points": ["Structural evidence 1", "Structural evidence 2"],
  "contradictory_points": ["Structural conflict or invalidation danger"],
  "structural_invalidation_level": 0.00
}
PROMPT;
    }

    protected function buildUserPrompt(AnalysisContext $context): string
    {
        $payload = [
            'instrument' => $context->symbol,
            'timeframe' => $context->timeframe,
            'latest_price' => $context->getLatestPrice(),
            'deterministic_market_structure' => $context->marketStructure,
            'support_resistance' => $context->supportResistance,
            'user_notes' => $context->notes,
        ];

        return $this->formatContextData($payload);
    }
}
