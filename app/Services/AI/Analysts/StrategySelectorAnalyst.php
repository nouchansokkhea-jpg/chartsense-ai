<?php

namespace App\Services\AI\Analysts;

use App\Services\AI\DTOs\AnalysisContext;

class StrategySelectorAnalyst extends AbstractAnalyst
{
    public function getRole(): string
    {
        return 'strategy_selector';
    }

    public function getRoleTitle(): string
    {
        return 'Strategy Selector & Evaluator Analyst';
    }

    protected function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are the Strategy Selector & Evaluator Analyst for ChartSense AI.
Your objective: Review retrieved global trading strategies (from Price Action, ICT/SMC, Fibonacci, Wyckoff, Quantitative, BBMA, etc.) provided in context, evaluate their rule compliance against current market data, and select the primary matching strategy.
CRITICAL RULES:
1. The retrieved strategies are dynamic in-context reference specifications, NOT model training weights or fine-tuned parameters.
2. Check EVERY mandatory entry rule and invalidation condition of candidate strategies. If a rule is violated, disqualify that strategy.
3. If no retrieved strategy meets all mandatory criteria or if conditions are ambiguous, conclude "NO VALID SETUP" or "WAIT". Do not force a trade.
4. Provide clear rationales for why alternative strategies were rejected.
5. Return output strictly in valid JSON matching this schema:
{
  "conclusion": "BULLISH" | "BEARISH" | "WAIT" | "NO VALID SETUP",
  "confidence": "low" | "moderate" | "high",
  "summary": "1-2 sentence justification of strategy selection or setup rejection.",
  "findings": ["Rule verification finding", "Regime compatibility finding", "Disqualification detail of competitor strategy"],
  "supporting_points": ["Selection rationale 1", "Selection rationale 2"],
  "contradictory_points": ["Strategy vulnerability or execution risk"],
  "selected_strategy": {
    "name": "e.g. ICT Silver Bullet" | "NONE",
    "version": "1.0.0",
    "category": "ict_smc",
    "evidence_status": "e.g. Tier A: 68.5% WR | PF 2.15" | "INSUFFICIENT_DATA",
    "entry_trigger": "Specific price level or condition that must be triggered",
    "invalidation_level": "Specific price or condition invalidating the setup",
    "rejected_alternatives": [
      {"name": "Break and Retest", "reason": "No structural breakout confirmed yet"}
    ]
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
            'retrieved_strategies_context' => $context->retrievedStrategies['prompt_context'] ?? 'No strategies retrieved.',
            'technical_structure' => [
                'market_structure' => $context->marketStructure,
                'support_resistance' => $context->supportResistance,
                'smc' => $context->smc,
                'fibonacci' => $context->fibonacci,
                'indicators' => $context->indicators,
            ],
            'user_notes' => $context->notes,
        ];

        return $this->formatContextData($payload);
    }
}
