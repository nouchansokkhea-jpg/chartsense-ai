<?php

namespace App\Services\News;

class EconomicSurpriseCalculator
{
    /**
     * Calculate event-appropriate numerical surprise and currency transmission direction.
     *
     * @param string $eventName e.g. "US Consumer Price Index (CPI) YoY"
     * @param string|null $actual e.g. "2.6%" or "160K"
     * @param string|null $forecast e.g. "2.4%" or "150K"
     * @param string|null $previous e.g. "2.5%"
     * @return array{
     *     surprise_score: ?float,
     *     surprise_direction: string,
     *     currency_bias: string,
     *     gold_bias: string,
     *     interpretation: string
     * }
     */
    public function calculateSurprise(
        string $eventName,
        ?string $actual,
        ?string $forecast,
        ?string $previous = null
    ): array {
        if ($actual === null || $forecast === null) {
            return [
                'surprise_score' => null,
                'surprise_direction' => 'PENDING_RELEASE',
                'currency_bias' => 'NEUTRAL',
                'gold_bias' => 'NEUTRAL',
                'interpretation' => 'Official actual figure has not been released yet.',
            ];
        }

        $actVal = $this->parseNumericalValue($actual);
        $fcVal = $this->parseNumericalValue($forecast);

        if ($actVal === null || $fcVal === null) {
            return [
                'surprise_score' => null,
                'surprise_direction' => 'NON_NUMERICAL',
                'currency_bias' => 'UNCERTAIN',
                'gold_bias' => 'UNCERTAIN',
                'interpretation' => "Qualitative event outcome ({$actual} vs expected {$forecast}).",
            ];
        }

        $diff = round($actVal - $fcVal, 4);
        $denominator = abs($fcVal) > 0.001 ? abs($fcVal) : 1.0;
        $surprisePct = round(($diff / $denominator) * 100, 2);

        $eventUpper = strtoupper($eventName);
        $isInflation = str_contains($eventUpper, 'CPI') || str_contains($eventUpper, 'PPI') || str_contains($eventUpper, 'PCE') || str_contains($eventUpper, 'INFLATION');
        $isLaborNegative = str_contains($eventUpper, 'UNEMPLOYMENT') || str_contains($eventUpper, 'JOBLESS');
        $isGrowth = str_contains($eventUpper, 'PAYROLL') || str_contains($eventUpper, 'NFP') || str_contains($eventUpper, 'GDP') || str_contains($eventUpper, 'RETAIL') || str_contains($eventUpper, 'PMI');

        if (abs($diff) < 0.01) {
            return [
                'surprise_score' => 0.0,
                'surprise_direction' => 'AS_EXPECTED',
                'currency_bias' => 'NEUTRAL',
                'gold_bias' => 'NEUTRAL',
                'interpretation' => "Released exactly in line with consensus forecast ({$actual}). Minimal volatility surprise.",
            ];
        }

        // Higher is Hawkish/Better
        if ($isInflation) {
            if ($diff > 0) {
                return [
                    'surprise_score' => $surprisePct,
                    'surprise_direction' => 'BETTER_THAN_EXPECTED', // Higher inflation print
                    'currency_bias' => 'BULLISH', // Hawkish for currency
                    'gold_bias' => 'BEARISH',     // Higher yields weigh on gold
                    'interpretation' => "Inflation print surpassed forecast ({$actual} vs {$forecast} exp). Increases expectations of sustained central bank restrictive policy.",
                ];
            } else {
                return [
                    'surprise_score' => $surprisePct,
                    'surprise_direction' => 'WORSE_THAN_EXPECTED', // Disinflation print
                    'currency_bias' => 'BEARISH',
                    'gold_bias' => 'BULLISH',
                    'interpretation' => "Inflation cooled faster than forecast ({$actual} vs {$forecast} exp). Eases monetary tightening expectations.",
                ];
            }
        }

        // Higher is Worse for Currency/Economy (e.g. Unemployment rate)
        if ($isLaborNegative) {
            if ($diff > 0) {
                return [
                    'surprise_score' => $surprisePct,
                    'surprise_direction' => 'WORSE_THAN_EXPECTED',
                    'currency_bias' => 'BEARISH',
                    'gold_bias' => 'BULLISH',
                    'interpretation' => "Unemployment printed higher than expected ({$actual} vs {$forecast} exp). Signals labor market softening.",
                ];
            } else {
                return [
                    'surprise_score' => $surprisePct,
                    'surprise_direction' => 'BETTER_THAN_EXPECTED',
                    'currency_bias' => 'BULLISH',
                    'gold_bias' => 'BEARISH',
                    'interpretation' => "Unemployment printed lower than anticipated ({$actual} vs {$forecast} exp). Signals robust employment conditions.",
                ];
            }
        }

        // General economic growth (Payrolls, GDP, PMI)
        if ($diff > 0) {
            return [
                'surprise_score' => $surprisePct,
                'surprise_direction' => 'BETTER_THAN_EXPECTED',
                'currency_bias' => 'BULLISH',
                'gold_bias' => 'BEARISH',
                'interpretation' => "Surpassed consensus expectations ({$actual} vs {$forecast} exp). Reflects economic resilience.",
            ];
        } else {
            return [
                'surprise_score' => $surprisePct,
                'surprise_direction' => 'WORSE_THAN_EXPECTED',
                'currency_bias' => 'BEARISH',
                'gold_bias' => 'BULLISH',
                'interpretation' => "Fell short of consensus expectations ({$actual} vs {$forecast} exp). Indicates contractionary pressure.",
            ];
        }
    }

    protected function parseNumericalValue(?string $val): ?float
    {
        if ($val === null) return null;
        $clean = preg_replace('/[^0-9\.\-]/', '', $val);
        return is_numeric($clean) ? (float)$clean : null;
    }
}
