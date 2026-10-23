<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\DTOs\AnalysisContext;

class MultiTimeframeAnalyst extends AbstractAnalyst
{
    public function getRole(): string
    {
        return 'multi_timeframe';
    }

    public function getRoleTitle(): string
    {
        return 'Multi-Timeframe Analyst';
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are the institutional Multi-Timeframe Analyst for ChartSense AI.
Your objective: Compare market conditions across available timeframes and macroeconomic background.
CRITICAL RULES:
1. Identify agreements vs conflicts between intermediate and higher-timeframe trends.
2. NEVER pretend that a timeframe was analyzed if relevant data was not supplied.
3. If lower timeframe signals contradict higher timeframe conditions, clearly explain the danger of counter-trend trading.
4. Return output strictly in valid JSON matching this schema:
{
  "conclusion": "BULLISH" | "BEARISH" | "WAIT",
  "confidence": "low" | "moderate" | "high",
  "summary": "Concise summary of timeframe alignment and macro bias.",
  "findings": ["Primary timeframe status", "Macroeconomic backdrop alignment", "Higher timeframe conflict status"],
  "supporting_points": ["Alignment factor 1", "Alignment factor 2"],
  "contradictory_points": ["Timeframe conflict or missing higher timeframe data"]
}
PROMPT;
    }

    protected function buildUserPrompt(AnalysisContext $context): string
    {
        $payload = [
            'instrument' => $context->symbol,
            'primary_timeframe' => $context->timeframe,
            'market_structure_trend' => $context->marketStructure['trend'] ?? 'Consolidation',
            'macroeconomic_news' => $context->macroNews,
            'data_provenance' => $context->dataSource,
            'user_notes' => $context->notes,
        ];

        return $this->formatContextData($payload);
    }
}
