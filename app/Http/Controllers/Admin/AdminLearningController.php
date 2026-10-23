<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModelRegistry;
use App\Models\HistoricalMarketCandle;
use App\Models\LearningRun;
use App\Models\MarketDataSyncJob;
use App\Models\MarketFeature;
use App\Models\ResearchSource;
use App\Models\ScreenshotEvaluation;
use App\Models\Strategy;
use App\Models\StrategyResearchDocument;
use App\Models\TradeOutcome;
use App\Services\AI\AiModelRegistryService;
use App\Services\Historical\AutonomousLearningOrchestrator;
use App\Services\Historical\HistoricalMarketDataService;
use App\Services\Strategy\ResearchDiscoveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLearningController extends Controller
{
    public function index(
        AiModelRegistryService $registryService,
        ResearchDiscoveryService $researchService
    ) {
        $registryService->ensureDefaultRegistry();
        $researchService->ensureDefaultSources();

        // 1. Historical Data Stats
        $syncJobs = MarketDataSyncJob::orderBy('updated_at', 'desc')->get();
        $totalCandles = HistoricalMarketCandle::count();
        $instruments = HistoricalMarketCandle::select('symbol', 'timeframe', DB::raw('count(*) as count'), DB::raw('avg(quality_score) as avg_quality'))
            ->groupBy('symbol', 'timeframe')
            ->get();

        // 2. AI Model Registry
        $models = AiModelRegistry::orderBy('provider')->get();

        // 3. Screenshot Intelligence Lab
        $screenshotEvaluations = ScreenshotEvaluation::orderBy('created_at', 'desc')->limit(15)->get();
        $avgPriceError = ScreenshotEvaluation::whereNotNull('price_extraction_error_pct')->avg('price_extraction_error_pct') ?? 0.0;
        $avgRecall = ScreenshotEvaluation::whereNotNull('structure_recall_score')->avg('structure_recall_score') ?? 0.0;
        $matchedCount = ScreenshotEvaluation::where('ground_truth_matched', true)->count();
        $totalScreenshots = ScreenshotEvaluation::count();
        $matchRate = $totalScreenshots > 0 ? round(($matchedCount / $totalScreenshots) * 100, 1) : 100.0;

        // 4. Strategy Research Lab
        $strategies = Strategy::with('versions')->get();
        $researchDocuments = StrategyResearchDocument::orderBy('created_at', 'desc')->limit(10)->get();
        $researchSources = ResearchSource::all();

        // 5. Autonomous Learning Monitor
        $learningRuns = LearningRun::orderBy('created_at', 'desc')->limit(15)->get();

        // 6. Trade Outcomes & Realized Feedback
        $tradeOutcomes = TradeOutcome::orderBy('predicted_at', 'desc')->limit(15)->get();
        $totalTrades = TradeOutcome::count();
        $winCount = TradeOutcome::where('outcome', 'WIN')->count();
        $overallWinRate = $totalTrades > 0 ? round(($winCount / $totalTrades) * 100, 1) : 0.0;

        return view('admin.learning.index', compact(
            'syncJobs',
            'totalCandles',
            'instruments',
            'models',
            'screenshotEvaluations',
            'avgPriceError',
            'avgRecall',
            'matchRate',
            'strategies',
            'researchDocuments',
            'researchSources',
            'learningRuns',
            'tradeOutcomes',
            'totalTrades',
            'overallWinRate'
        ));
    }

    public function syncHistorical(Request $request, HistoricalMarketDataService $service)
    {
        $validated = $request->validate([
            'symbol' => 'required|string|max:20',
            'timeframe' => 'required|string|max:10',
            'days' => 'nullable|integer|min:7|max:1150',
            'provider' => 'nullable|string|in:yahoo_finance,deterministic_reference',
        ]);

        $symbol = strtoupper($validated['symbol']);
        $timeframe = strtoupper($validated['timeframe']);
        $days = (int)($validated['days'] ?? 365);
        $provider = $validated['provider'] ?? 'yahoo_finance';

        $result = $service->syncHistoricalData($symbol, $timeframe, $days, $provider);

        if ($result['status'] === 'completed') {
            return redirect()->route('admin.learning.index')
                ->with('success', "Historical market data synced successfully for {$symbol} ({$timeframe}): {$result['candles_synced']} candles ingested, {$result['gaps_detected']} gaps detected.");
        }

        return redirect()->route('admin.learning.index')
            ->with('error', "Historical sync failed: " . ($result['error'] ?? 'Unknown error'));
    }

    public function runCycle(Request $request, AutonomousLearningOrchestrator $orchestrator)
    {
        $validated = $request->validate([
            'symbol' => 'nullable|string|max:20',
            'timeframe' => 'nullable|string|max:10',
            'dry_run' => 'nullable|boolean',
        ]);

        $symbol = strtoupper($validated['symbol'] ?? 'EURUSD');
        $timeframe = strtoupper($validated['timeframe'] ?? 'D1');
        $dryRun = (bool)($validated['dry_run'] ?? false);

        $result = $orchestrator->runDailyCycle($symbol, $timeframe, $dryRun);

        if ($result['status'] === 'completed') {
            return redirect()->route('admin.learning.index')
                ->with('success', "11-Stage Daily Autonomous Learning Cycle completed successfully in {$result['execution_seconds']}s!");
        }

        return redirect()->route('admin.learning.index')
            ->with('error', "Learning cycle encountered an error: " . ($result['error'] ?? 'Unknown error'));
    }
}
