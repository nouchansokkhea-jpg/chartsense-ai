<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\DTOs\AnalysisContext;

class TechnicalIndicatorAnalyst extends AbstractAnalyst
{
    public function getRole(): string
    {
        return 'technical_indicators';
    }

    public function getRoleTitle(): string
    {
        return 'Technical Indicator Analyst';
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are the quantitative Technical Indicator Analyst for ChartSense AI.
Your objective: Interpret mathematical indicator values calculated deterministically by the system (Moving Averages, EMA, RSI, MACD, ATR, Bollinger Bands, Fibonacci retracements, Volume).
CRITICAL RULES:
1. Interpret the calculated values provided. NEVER invent indicator values or override verified numbers.
2. Cross-reference RSI momentum and MACD histogram with Bollinger Band width.
3. Note parameters used for every indicator.
4. Return output strictly in valid JSON matching this schema:
{
  "conclusion": "BULLISH" | "BEARISH" | "WAIT",
  "confidence": "low" | "moderate" | "high",
  "summary": "Concise indicator alignment summary.",
  "findings": ["Moving average alignment finding", "Oscillator momentum finding", "Volatility & Bandwidth finding"],
  "supporting_points": ["Indicator confirmation 1", "Indicator confirmation 2"],
  "contradictory_points": ["Indicator divergence or conflicting momentum reading"]
}
PROMPT;
    }

    protected function buildUserPrompt(AnalysisContext $context): string
    {
        $payload = [
            'instrument' => $context->symbol,
            'timeframe' => $context->timeframe,
            'latest_price' => $context->getLatestPrice(),
            'calculated_indicators' => $context->indicators,
            'fibonacci_levels' => $context->fibonacci,
            'indicator_parameters' => $context->parameters,
        ];

        return $this->formatContextData($payload);
    }
}
