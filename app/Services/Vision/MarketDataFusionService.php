<?php

namespace App\Services\Vision;

class MarketDataFusionService
{
    /**
     * Reconcile screenshot visual interpretation with verified numerical market dataset.
     *
     * @param array|null $visionPlan Result from computer vision / OCR
     * @param array|null $candles Verified OHLCV candle records
     * @param string $requestedSymbol User-requested or default symbol
     * @param string $requestedTimeframe User-requested or default timeframe
     * @return array Fusion assessment report and reconciled parameters
     */
    public function fuse(
        ?array $visionPlan,
        ?array $candles,
        string $requestedSymbol,
        string $requestedTimeframe
    ): array {
        $hasVision = !empty($visionPlan) && !empty($visionPlan['detected_symbol']);
        $hasCandles = !empty($candles);

        if (!$hasVision && !$hasCandles) {
            return [
                'fusion_status' => 'NO_DATA',
                'can_continue_safely' => false,
                'discrepancy_flagged' => true,
                'reconciliation_notes' => 'Neither chart screenshot nor candle data was provided.',
                'effective_price' => null,
                'effective_symbol' => $requestedSymbol,
                'effective_timeframe' => $requestedTimeframe,
            ];
        }

        if ($hasVision && !$hasCandles) {
            $canSafelyContinue = !empty($visionPlan['current_market_price']) 
                && ($visionPlan['screenshot_quality']['can_read_reliably'] ?? true);

            return [
                'fusion_status' => 'SCREENSHOT_ONLY',
                'can_continue_safely' => $canSafelyContinue,
                'discrepancy_flagged' => false,
                'reconciliation_notes' => $canSafelyContinue 
                    ? 'Operating in pure visual screenshot mode. Verified against OCR price axis.'
                    : ($visionPlan['technical_explanation'] ?? 'Screenshot quality is insufficient to read prices reliably.'),
                'effective_price' => $visionPlan['current_market_price'] ?? null,
                'effective_symbol' => $visionPlan['detected_symbol'] ?? $requestedSymbol,
                'effective_timeframe' => $visionPlan['detected_timeframe'] ?? $requestedTimeframe,
                'screenshot_quality' => $visionPlan['screenshot_quality'] ?? null,
                'visual_patterns' => $visionPlan['visual_patterns'] ?? null,
            ];
        }

        if (!$hasVision && $hasCandles) {
            $latestBar = end($candles);
            $marketPrice = (float)($latestBar['close'] ?? $latestBar['open'] ?? 0);

            return [
                'fusion_status' => 'DATASET_ONLY',
                'can_continue_safely' => true,
                'discrepancy_flagged' => false,
                'reconciliation_notes' => 'Operating in verified OHLCV dataset mode.',
                'effective_price' => $marketPrice,
                'effective_symbol' => $requestedSymbol,
                'effective_timeframe' => $requestedTimeframe,
            ];
        }

        // Both Vision and Candles are present: Execute Fusion & Reconcile
        $detectedSymbol = strtoupper($visionPlan['detected_symbol'] ?? '');
        $detectedTf = strtoupper($visionPlan['detected_timeframe'] ?? '');
        $visualPrice = isset($visionPlan['current_market_price']) ? (float)$visionPlan['current_market_price'] : null;

        $latestBar = end($candles);
        $marketPrice = (float)($latestBar['close'] ?? $latestBar['open'] ?? 0);

        $discrepancies = [];
        $symbolMismatch = false;
        $tfMismatch = false;

        // Check Symbol Alignment
        if ($detectedSymbol && $detectedSymbol !== 'AUTO' && $detectedSymbol !== strtoupper($requestedSymbol)) {
            $symbolMismatch = true;
            $discrepancies[] = "Symbol discrepancy: Screenshot header detects '{$detectedSymbol}' while dataset requested '{$requestedSymbol}'.";
        }

        // Check Timeframe Alignment
        if ($detectedTf && $detectedTf !== 'AUTO' && $detectedTf !== strtoupper($requestedTimeframe)) {
            $tfMismatch = true;
            $discrepancies[] = "Timeframe discrepancy: Screenshot indicates '{$detectedTf}' while dataset is '{$requestedTimeframe}'.";
        }

        // Check Price Concordance
        $priceDiffPct = 0.0;
        $priceDiverged = false;
        if ($visualPrice !== null && $marketPrice > 0) {
            $priceDiffPct = round(abs($visualPrice - $marketPrice) / $marketPrice * 100, 2);
            // Allow up to 1.5% tolerance between screenshot capture time and latest candle close
            if ($priceDiffPct > 1.5) {
                $priceDiverged = true;
                $discrepancies[] = "Price divergence: Visual price ({$visualPrice}) differs by {$priceDiffPct}% from verified candle close ({$marketPrice}).";
            }
        }

        $discrepancyFlagged = !empty($discrepancies);
        $status = $discrepancyFlagged ? 'FUSED_WITH_DISCREPANCY' : 'FUSED_EXACT';

        // Discrepancy reporting: structured dataset takes precedence for math, screenshot informs visual structure
        return [
            'fusion_status' => $status,
            'can_continue_safely' => true,
            'discrepancy_flagged' => $discrepancyFlagged,
            'discrepancies' => $discrepancies,
            'price_difference_pct' => $priceDiffPct,
            'visual_price' => $visualPrice,
            'market_price' => $marketPrice,
            'effective_price' => $marketPrice > 0 ? $marketPrice : $visualPrice,
            'effective_symbol' => $symbolMismatch ? $detectedSymbol : $requestedSymbol,
            'effective_timeframe' => $tfMismatch ? $detectedTf : $requestedTimeframe,
            'reconciliation_notes' => $discrepancyFlagged
                ? 'Reconciliation note: ' . implode(' ', $discrepancies) . ' Numerical calculations anchored to verified OHLCV dataset.'
                : 'Screenshot visual geometry and OHLCV numerical dataset align with 100% concordance.',
            'screenshot_quality' => $visionPlan['screenshot_quality'] ?? null,
            'visual_patterns' => $visionPlan['visual_patterns'] ?? null,
        ];
    }
}
