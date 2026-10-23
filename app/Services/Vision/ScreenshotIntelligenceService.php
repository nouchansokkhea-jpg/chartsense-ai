<?php

namespace App\Services\Vision;

use App\Models\HistoricalMarketCandle;
use App\Models\MarketFeature;
use App\Models\ScreenshotEvaluation;
use App\Services\AI\MultiAiAnalysisService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScreenshotIntelligenceService
{
    /**
     * Evaluate a chart screenshot against ground-truth historical market data and features.
     *
     * @param string $imagePath Absolute path to the screenshot image
     * @param array $visionData Parsed vision plan from chart_vision_reader.py
     * @param int|null $scanId
     * @return array Screenshot evaluation metrics and ground-truth comparison
     */
    public function evaluateScreenshot(string $imagePath, array $visionData, ?int $scanId = null): array
    {
        $detectedSymbol = strtoupper($visionData['detected_symbol'] ?? 'AUTO');
        $detectedTf = strtoupper($visionData['detected_timeframe'] ?? 'AUTO');
        $detectedPrice = isset($visionData['current_market_price']) ? (float)$visionData['current_market_price'] : null;

        $detectedStructures = [
            'trend' => $visionData['trend'] ?? null,
            'direction' => $visionData['direction'] ?? null,
            'patterns' => $visionData['visual_patterns'] ?? [],
            'structures' => $visionData['structures'] ?? [],
        ];

        // 1. Attempt Ground-Truth Match against historical candles
        $matched = false;
        $verifiedPrice = null;
        $priceErrorPct = null;
        $structureRecall = null;
        $notes = [];

        if ($detectedSymbol !== 'AUTO' && $detectedTf !== 'AUTO') {
            $latestCandle = HistoricalMarketCandle::forInstrument($detectedSymbol, $detectedTf)
                ->verifiedQuality(60)
                ->orderBy('candle_timestamp', 'desc')
                ->first();

            if ($latestCandle) {
                $matched = true;
                $verifiedPrice = (float)$latestCandle->close;

                if ($detectedPrice && $detectedPrice > 0 && $verifiedPrice > 0) {
                    $priceErrorPct = round(abs($detectedPrice - $verifiedPrice) / $verifiedPrice * 100, 3);
                    if ($priceErrorPct > 5.0) {
                        $notes[] = sprintf(
                            'Noticeable price axis discrepancy: Detected %.4f vs Verified Market %.4f (%.2f%% error)',
                            $detectedPrice,
                            $verifiedPrice,
                            $priceErrorPct
                        );
                    }
                }

                // Compare detected structures against zero-lookahead MarketFeature
                $latestFeature = MarketFeature::where('symbol', $detectedSymbol)
                    ->where('timeframe', $detectedTf)
                    ->orderBy('candle_timestamp', 'desc')
                    ->first();

                if ($latestFeature) {
                    $structureMatches = 0;
                    $totalChecks = 3;

                    // Check FVG
                    if ($latestFeature->fvg_candidate && !empty($visionData['visual_patterns'])) {
                        $structureMatches++;
                    }

                    // Check Order Block
                    if ($latestFeature->order_block_candidate && !empty($visionData['structures'])) {
                        $structureMatches++;
                    }

                    // Check Trend / Regime alignment
                    if ($latestFeature->market_regime && !empty($visionData['trend'])) {
                        $structureMatches++;
                    }

                    $structureRecall = round(($structureMatches / $totalChecks) * 100, 1);
                }
            } else {
                $notes[] = "No verified historical candle dataset found for {$detectedSymbol} ({$detectedTf}) to benchmark.";
            }
        } else {
            $notes[] = 'Screenshot symbol or timeframe could not be determined with high visual certainty.';
        }

        // Quality and Confidence Calibration
        $qualityScore = (float)($visionData['screenshot_quality']['quality_score'] ?? 80);
        $confidence = $qualityScore;

        if ($priceErrorPct !== null) {
            $confidence = max(10, min(99, $confidence - ($priceErrorPct * 4)));
        }

        $calibrationError = null;
        if ($priceErrorPct !== null) {
            $expectedConfidence = max(0, 100 - ($priceErrorPct * 5));
            $calibrationError = round(abs($confidence - $expectedConfidence), 2);
        }

        // Persist evaluation
        $evaluation = ScreenshotEvaluation::create([
            'scan_id' => $scanId,
            'screenshot_path' => $imagePath,
            'detected_symbol' => $detectedSymbol !== 'AUTO' ? $detectedSymbol : null,
            'detected_timeframe' => $detectedTf !== 'AUTO' ? $detectedTf : null,
            'detected_structures' => $detectedStructures,
            'verified_symbol' => $matched ? $detectedSymbol : null,
            'verified_timeframe' => $matched ? $detectedTf : null,
            'ground_truth_matched' => $matched,
            'price_extraction_error_pct' => $priceErrorPct,
            'structure_recall_score' => $structureRecall,
            'confidence_score' => round($confidence, 1),
            'calibration_error' => $calibrationError,
            'evaluation_notes' => !empty($notes) ? implode('; ', $notes) : 'Visual and market data aligned successfully.',
        ]);

        return [
            'evaluation_id' => $evaluation->id,
            'ground_truth_matched' => $matched,
            'detected_symbol' => $detectedSymbol,
            'detected_timeframe' => $detectedTf,
            'detected_price' => $detectedPrice,
            'verified_price' => $verifiedPrice,
            'price_error_pct' => $priceErrorPct,
            'structure_recall' => $structureRecall,
            'calibrated_confidence' => round($confidence, 1),
            'notes' => $notes,
        ];
    }
}
