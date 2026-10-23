<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\DTOs\AnalysisContext;

class QuantitativeAnalyst extends AbstractAnalyst
{
    public function getRole(): string
    {
        return 'quantitative_analyst';
    }

    public function getRoleTitle(): string
    {
        return 'Quantitative & Statistical Indicator Analyst';
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are the Quantitative & Statistical Indicator Analyst for ChartSense AI.
Your objective: Rigorously evaluate statistical momentum, volatility distribution (ATR percentile), moving average slope dynamics, volume anomalies, and probability distributions.
CRITICAL RULES:
1. Ground all conclusions in verifiable numerical metrics (RSI, ATR, Bollinger Bandwidth, Moving Average slopes).
2. If indicators show high divergence or conflicting statistical signals, conclude "WAIT".
3. Conclude "NO VALID SETUP" if statistical edge is absent or risk-reward expectancy is negative under current volatility conditions.
4. Return output strictly in valid JSON matching this schema:
{
  "conclusion": "BULLISH" | "BEARISH" | "WAIT" | "NO VALID SETUP",
  "confidence": "low" | "moderate" | "high",
  "summary": "1-2 sentence statistical and momentum evaluation.",
  "findings": ["Statistical volatility finding", "Moving average slope finding", "Volume/Momentum distribution finding"],
  "supporting_points": ["Quant confluence 1", "Quant confluence 2"],
  "contradictory_points": ["Statistical risk factor or indicator divergence"],
  "quantitative_metrics": {
    "volatility_state": "compressed" | "normal" | "expanded",
    "momentum_bias": "positive" | "negative" | "neutral",
    "statistical_edge": "favorable" | "neutral" | "unfavorable"
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
            'indicators' => $context->indicators,
            'market_regime_metrics' => $context->marketRegime['metrics'] ?? null,
            'indicator_parameters' => $context->parameters,
            'user_notes' => $context->notes,
        ];

        return $this->formatContextData($payload);
    }
}
