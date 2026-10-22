<?php

namespace App\Services\TechnicalAnalysis;

class TradingPlanValidator
{
    /**
     * Validate and sanitize incoming AI or numerical trading plan.
     * Enforces strict financial price-action geometry rules.
     *
     * @param array $plan
     * @return array
     */
    public function validateAndSanitize(array $plan): array
    {
        $direction = strtoupper(trim($plan['direction'] ?? 'WAIT'));
        if (!in_array($direction, ['BUY', 'SELL', 'WAIT', 'NO VALID SETUP'])) {
            $direction = 'WAIT';
        }

        // For WAIT or NO VALID SETUP, ensure values are nullified cleanly
        if ($direction === 'WAIT' || $direction === 'NO VALID SETUP') {
            return [
                'direction' => $direction,
                'trend' => $plan['trend'] ?? 'Consolidation',
                'confidence' => $plan['confidence'] ?? 'low',
                'entry_type' => null,
                'entry_min' => null,
                'entry_max' => null,
                'entry_target' => null,
                'stop_loss' => null,
                'take_profit_1' => null,
                'take_profit_2' => null,
                'take_profit_3' => null,
                'risk_reward_ratio' => null,
                'risk_distance' => null,
                'reward_distance_tp1' => null,
                'technical_explanation' => $plan['technical_explanation'] ?? 'Market structure is indecisive or consolidating without a verified high-probability setup.',
                'reasoning' => is_array($plan['reasoning'] ?? null) ? $plan['reasoning'] : ['No clear edge found in current price action.'],
                'confirmation_conditions' => is_array($plan['confirmation_conditions'] ?? null) ? $plan['confirmation_conditions'] : ['Await breakout and retest of key boundary.'],
                'invalidation_conditions' => is_array($plan['invalidation_conditions'] ?? null) ? $plan['invalidation_conditions'] : ['Do not trade inside range.'],
                'warnings' => is_array($plan['warnings'] ?? null) ? $plan['warnings'] : ['Trading without confirmation carries elevated drawdown risk.'],
                'support_levels' => $plan['support_levels'] ?? [],
                'resistance_levels' => $plan['resistance_levels'] ?? [],
                'calculated_indicators' => $plan['calculated_indicators'] ?? [],
                'market_structure' => $plan['market_structure'] ?? [],
                'strategy_confluences' => $plan['strategy_confluences'] ?? null,
                'macro_news_context' => $plan['macro_news_context'] ?? null,
                'detected_symbol' => $plan['detected_symbol'] ?? null,
                'detected_timeframe' => $plan['detected_timeframe'] ?? null,
                'data_provenance' => $plan['data_provenance'] ?? null,
            ];
        }

        $entry = isset($plan['entry_target']) && is_numeric($plan['entry_target']) 
            ? (float)$plan['entry_target'] 
            : (isset($plan['entry_price']) && is_numeric($plan['entry_price']) ? (float)$plan['entry_price'] : null);
        $sl = isset($plan['stop_loss']) && is_numeric($plan['stop_loss']) ? (float)$plan['stop_loss'] : null;
        $tp1 = isset($plan['take_profit_1']) && is_numeric($plan['take_profit_1']) ? (float)$plan['take_profit_1'] : null;
        $tp2 = isset($plan['take_profit_2']) && is_numeric($plan['take_profit_2']) ? (float)$plan['take_profit_2'] : null;
        $tp3 = isset($plan['take_profit_3']) && is_numeric($plan['take_profit_3']) ? (float)$plan['take_profit_3'] : null;

        // If mandatory prices are missing, downgrade to WAIT
        if ($entry === null || $sl === null || $tp1 === null) {
            return $this->validateAndSanitize(['direction' => 'NO VALID SETUP', 'reasoning' => ['Incomplete or unreadable entry, SL, or TP levels.']]);
        }

        // PRICE RELATIONSHIP VALIDATION
        if ($direction === 'BUY') {
            // For BUY: SL must be strictly below Entry, and TP1 must be strictly above Entry
            if ($sl >= $entry || $tp1 <= $entry) {
                return $this->validateAndSanitize([
                    'direction' => 'NO VALID SETUP',
                    'reasoning' => ["Contradictory BUY price relationships detected: SL ({$sl}) must be below Entry ({$entry}), and TP1 ({$tp1}) must be above."],
                ]);
            }

            // Ensure ascending take profits
            if ($tp2 !== null && $tp2 <= $tp1) $tp2 = round($entry + (($tp1 - $entry) * 1.8), 2);
            if ($tp3 !== null && $tp2 !== null && $tp3 <= $tp2) $tp3 = round($entry + (($tp2 - $entry) * 1.6), 2);

            $risk = $entry - $sl;
            $reward = $tp1 - $entry;
            $rr = $risk > 0 ? round($reward / $risk, 2) : 1.0;
        } else {
            // For SELL: SL must be strictly above Entry, and TP1 must be strictly below Entry
            if ($sl <= $entry || $tp1 >= $entry) {
                return $this->validateAndSanitize([
                    'direction' => 'NO VALID SETUP',
                    'reasoning' => ["Contradictory SELL price relationships detected: SL ({$sl}) must be above Entry ({$entry}), and TP1 ({$tp1}) must be below."],
                ]);
            }

            // Ensure descending take profits
            if ($tp2 !== null && $tp2 >= $tp1) $tp2 = round($entry - (($entry - $tp1) * 1.8), 2);
            if ($tp3 !== null && $tp2 !== null && $tp3 >= $tp2) $tp3 = round($entry - (($entry - $tp2) * 1.6), 2);

            $risk = $sl - $entry;
            $reward = $entry - $tp1;
            $rr = $risk > 0 ? round($reward / $risk, 2) : 1.0;
        }

        return [
            'direction' => $direction,
            'trend' => $plan['trend'] ?? ($direction === 'BUY' ? 'Bullish' : 'Bearish'),
            'confidence' => $plan['confidence'] ?? 'moderate',
            'entry_type' => $plan['entry_type'] ?? 'limit_zone',
            'entry_min' => (float)($plan['entry_min'] ?? $entry),
            'entry_max' => (float)($plan['entry_max'] ?? $entry),
            'entry_target' => $entry,
            'stop_loss' => $sl,
            'take_profit_1' => $tp1,
            'take_profit_2' => $tp2,
            'take_profit_3' => $tp3,
            'risk_reward_ratio' => $rr,
            'risk_distance' => round($risk, 2),
            'reward_distance_tp1' => round($reward, 2),
            'technical_explanation' => $plan['technical_explanation'] ?? "A {$direction} setup identified with confirmed structural invalidation level.",
            'reasoning' => is_array($plan['reasoning'] ?? null) ? $plan['reasoning'] : ["Technical alignment supports a {$direction} opportunity."],
            'confirmation_conditions' => is_array($plan['confirmation_conditions'] ?? null) ? $plan['confirmation_conditions'] : ['Observe lower timeframe reaction before entry execution.'],
            'invalidation_conditions' => is_array($plan['invalidation_conditions'] ?? null) ? $plan['invalidation_conditions'] : ["Candle close beyond {$sl} completely invalidates the setup."],
            'warnings' => is_array($plan['warnings'] ?? null) ? $plan['warnings'] : ['Trading financial assets carries substantial risk of loss.'],
            'support_levels' => $plan['support_levels'] ?? [],
            'resistance_levels' => $plan['resistance_levels'] ?? [],
            'calculated_indicators' => $plan['calculated_indicators'] ?? [],
            'market_structure' => $plan['market_structure'] ?? [],
            'strategy_confluences' => $plan['strategy_confluences'] ?? null,
            'macro_news_context' => $plan['macro_news_context'] ?? null,
            'detected_symbol' => $plan['detected_symbol'] ?? null,
            'detected_timeframe' => $plan['detected_timeframe'] ?? null,
            'data_provenance' => $plan['data_provenance'] ?? null,
        ];
    }
}
