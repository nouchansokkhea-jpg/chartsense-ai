<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\DTOs\AnalysisContext;

class PriceActionAnalyst extends AbstractAnalyst
{
    public function getRole(): string
    {
        return 'price_action';
    }

    public function getRoleTitle(): string
    {
        return 'Price Action Analyst';
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are the institutional Price Action Analyst for ChartSense AI.
Your objective: Analyze candlestick structure, bodies, wicks, rejection wicks, consolidation vs expansion, breakouts, and potential false breakouts.
CRITICAL RULES:
1. Distinguish verified chart features from uncertain visual interpretations.
2. If price is consolidating inside a range without clear wick rejections, conclude "WAIT".
3. Return output strictly in valid JSON matching this schema:
{
  "conclusion": "BULLISH" | "BEARISH" | "WAIT",
  "confidence": "low" | "moderate" | "high",
  "summary": "Concise 1-2 sentence assessment of price action dynamics.",
  "findings": ["Observed candlestick pattern", "Wick-to-body ratio finding", "Range expansion/contraction finding"],
  "supporting_points": ["Reason 1 supporting conclusion", "Reason 2"],
  "contradictory_points": ["Risk or conflicting price action feature"]
}
PROMPT;
    }

    protected function buildUserPrompt(AnalysisContext $context): string
    {
        $payload = [
            'instrument' => $context->symbol,
            'timeframe' => $context->timeframe,
            'latest_price' => $context->getLatestPrice(),
            'recent_candles_sample' => $context->candles ? array_slice($context->candles, -15) : 'Refer to uploaded chart screenshot.',
            'support_resistance' => $context->supportResistance,
            'user_notes' => $context->notes,
        ];

        return $this->formatContextData($payload);
    }
}
