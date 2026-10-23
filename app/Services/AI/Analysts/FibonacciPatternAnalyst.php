<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\DTOs\AnalysisContext;

class FibonacciPatternAnalyst extends AbstractAnalyst
{
    public function getRole(): string
    {
        return 'fibonacci_patterns';
    }

    public function getRoleTitle(): string
    {
        return 'Fibonacci & Harmonic Pattern Analyst';
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are the institutional Fibonacci & Harmonic Pattern Analyst for ChartSense AI.
Your objective: Evaluate price action relative to mathematical Fibonacci retracements (38.2%, 50.0%, 61.8% Golden Pocket, 78.6%), Fibonacci extensions (1.272, 1.618), and structural harmonic structures.
CRITICAL RULES:
1. Base all retracement levels on the verified swing pivots and mathematical Fibonacci levels provided in context. Do not invent arbitrary levels.
2. If price is in "no-man's-land" between key Fibonacci levels without reaction or confluence, conclude "WAIT".
3. Conclude "NO VALID SETUP" if the current leg has fully retraced (>100%) or structure has invalidated harmonic symmetry.
4. Return output strictly in valid JSON matching this schema:
{
  "conclusion": "BULLISH" | "BEARISH" | "WAIT" | "NO VALID SETUP",
  "confidence": "low" | "moderate" | "high",
  "summary": "1-2 sentence assessment of Fibonacci retracement/extension reaction.",
  "findings": ["Golden zone reaction detail", "Swing pivot anchor verification", "Harmonic confluence finding"],
  "supporting_points": ["Fibonacci confluence point 1", "Fibonacci confluence point 2"],
  "contradictory_points": ["Risk of level breach or missing confluence"],
  "fibonacci_levels_observed": {
    "retracement_tested": "e.g. 61.8% (Golden Zone)",
    "golden_pocket_status": "holding" | "broken" | "not_reached",
    "extension_target": "e.g. 1.618 @ 2674.50"
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
            'mathematical_fibonacci' => $context->fibonacci,
            'swing_pivots' => $context->marketStructure['swings'] ?? null,
            'support_resistance' => $context->supportResistance,
            'user_notes' => $context->notes,
        ];

        return $this->formatContextData($payload);
    }
}
