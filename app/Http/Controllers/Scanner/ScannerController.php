<?php

namespace App\Http\Controllers\Scanner;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MarketData;
use App\Models\Scan;
use App\Models\ScanInput;
use App\Models\ScanResult;
use App\Services\AI\AiAnalysisService;
use App\Services\MarketData\MarketDataParser;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ScannerController extends Controller
{
    public function __construct(
        protected AiAnalysisService $aiService,
        protected MarketDataParser $dataParser
    ) {}

    /**
     * Display the Chart Scanner interface.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        return view('scanner.index', compact('user'));
    }

    /**
     * Process a chart scan request (Screenshot or CSV).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'symbol' => ['nullable', 'string', 'max:20'],
            'timeframe' => ['nullable', 'string', 'in:AUTO,M1,M5,M15,M30,H1,H4,D1,W1'],
            'strategy' => ['required', 'string', 'in:comprehensive,smc,price_action,trend_following,support_resistance'],
            'input_type' => ['required', 'string', 'in:screenshot,csv,raw_text'],
            'chart_image' => ['required_if:input_type,screenshot', 'nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:15360'],
            'csv_file' => ['required_if:input_type,csv', 'nullable', 'file', 'mimes:csv,txt', 'max:20480'],
            'raw_text' => ['required_if:input_type,raw_text', 'nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'chart_image.required_if' => 'Please select a chart screenshot image (PNG, JPG, or WebP).',
            'csv_file.required_if' => 'Please upload a CSV market data file.',
        ]);

        $user = $request->user();
        $symbolInput = trim((string)$request->input('symbol'));
        $symbol = !empty($symbolInput) ? strtoupper($symbolInput) : 'AUTO';
        $timeframeInput = trim((string)$request->input('timeframe'));
        $timeframe = !empty($timeframeInput) ? strtoupper($timeframeInput) : 'AUTO';
        $strategy = $request->input('strategy');
        $inputType = $request->input('input_type');

        $notes = \App\Services\Security\PromptSanitizationService::sanitizeUserInput($request->input('notes'));

        $imagePath = null;
        $candles = null;
        $candlesCount = 0;
        $firstTimestamp = null;
        $lastTimestamp = null;
        $originalFilename = null;

        try {
            // Handle Screenshot via Zero-Trust SecureUploadService
            if ($inputType === 'screenshot' && $request->hasFile('chart_image')) {
                $processed = \App\Services\Security\SecureUploadService::processAndStoreScreenshot(
                    $request->file('chart_image'),
                    'scans/screenshots'
                );
                $originalFilename = $processed['original_name'];
                $imagePath = $processed['absolute_path'];
            }

            // Handle CSV
            if ($inputType === 'csv' && $request->hasFile('csv_file')) {
                $file = $request->file('csv_file');
                $originalFilename = $file->getClientOriginalName();
                $parsed = $this->dataParser->parseCsv($file);
                $candles = $parsed['candles'];
                $candlesCount = $parsed['count'];
                $firstTimestamp = $parsed['start'];
                $lastTimestamp = $parsed['end'];
            }

            // Handle Raw Text
            if ($inputType === 'raw_text' && $request->filled('raw_text')) {
                $parsed = $this->dataParser->parseRawText($request->input('raw_text'));
                $candles = $parsed['candles'];
                $candlesCount = $parsed['count'];
                $firstTimestamp = $parsed['start'];
                $lastTimestamp = $parsed['end'];
            }

            // Execute scan in transaction
            $scan = DB::transaction(function () use (
                $user, $symbol, $timeframe, $inputType, $strategy, $notes, $imagePath,
                $originalFilename, $candles, $candlesCount, $firstTimestamp, $lastTimestamp
            ) {
                $initialSymbol = ($symbol !== 'AUTO') ? $symbol : 'XAUUSD';
                $initialTimeframe = ($timeframe !== 'AUTO') ? $timeframe : 'M15';

                $scan = Scan::create([
                    'user_id' => $user->id,
                    'symbol' => $initialSymbol,
                    'timeframe' => $initialTimeframe,
                    'input_type' => $inputType,
                    'data_source' => $inputType === 'screenshot' ? 'screenshot_upload' : 'user_csv',
                    'strategy' => $strategy,
                    'status' => 'processing',
                    'started_at' => now(),
                ]);

                // Store Scan Input
                ScanInput::create([
                    'scan_id' => $scan->id,
                    'screenshot_path' => $imagePath,
                    'file_original_name' => $originalFilename,
                    'raw_data_json' => $candles ? array_slice($candles, 0, 100) : null, // Cache preview
                    'candles_count' => $candlesCount,
                    'first_candle_timestamp' => $firstTimestamp,
                    'last_candle_timestamp' => $lastTimestamp,
                    'file_metadata' => [
                        'uploaded_at' => now()->toIso8601String(),
                        'client_filename' => $originalFilename,
                    ],
                ]);

                // Run AI & Technical Analysis Engine
                $plan = $this->aiService->analyze($imagePath, $candles, [
                    'symbol' => $symbol,
                    'timeframe' => $timeframe,
                    'strategy' => $strategy,
                    'notes' => $notes,
                ], $scan->id, $user->id);

                // Update Scan record with auto-detected symbol and timeframe
                $finalSymbol = !empty($plan['detected_symbol']) && $plan['detected_symbol'] !== 'AUTO'
                    ? $plan['detected_symbol']
                    : $initialSymbol;

                $finalTimeframe = !empty($plan['detected_timeframe']) && $plan['detected_timeframe'] !== 'AUTO'
                    ? $plan['detected_timeframe']
                    : $initialTimeframe;

                $scan->update([
                    'symbol' => $finalSymbol,
                    'timeframe' => $finalTimeframe,
                ]);

                // Store Scan Result with strategy_confluences & macro_news_context
                ScanResult::create([
                    'scan_id' => $scan->id,
                    'direction' => $plan['direction'],
                    'trend' => $plan['trend'] ?? null,
                    'confidence' => $plan['confidence'] ?? 'moderate',
                    'entry_type' => $plan['entry_type'] ?? null,
                    'entry_min' => $plan['entry_min'] ?? null,
                    'entry_max' => $plan['entry_max'] ?? null,
                    'entry_target' => $plan['entry_target'] ?? null,
                    'stop_loss' => $plan['stop_loss'] ?? null,
                    'take_profit_1' => $plan['take_profit_1'] ?? null,
                    'take_profit_2' => $plan['take_profit_2'] ?? null,
                    'take_profit_3' => $plan['take_profit_3'] ?? null,
                    'risk_reward_ratio' => $plan['risk_reward_ratio'] ?? null,
                    'risk_distance' => $plan['risk_distance'] ?? null,
                    'reward_distance_tp1' => $plan['reward_distance_tp1'] ?? null,
                    'technical_explanation' => $plan['technical_explanation'] ?? null,
                    'reasoning' => $plan['reasoning'] ?? [],
                    'confirmation_conditions' => $plan['confirmation_conditions'] ?? [],
                    'invalidation_conditions' => $plan['invalidation_conditions'] ?? [],
                    'warnings' => $plan['warnings'] ?? [],
                    'support_levels' => $plan['support_levels'] ?? [],
                    'resistance_levels' => $plan['resistance_levels'] ?? [],
                    'calculated_indicators' => $plan['calculated_indicators'] ?? [],
                    'market_structure' => $plan['market_structure'] ?? [],
                    'strategy_confluences' => $plan['strategy_confluences'] ?? null,
                    'macro_news_context' => $plan['macro_news_context'] ?? null,
                    'full_ai_response' => $plan,
                ]);

                // If candles provided, store recent bars in market_data table for chart display
                if (!empty($candles)) {
                    $recentCandles = array_slice($candles, -150);
                    $records = [];
                    foreach ($recentCandles as $c) {
                        $records[] = [
                            'scan_id' => $scan->id,
                            'symbol' => $finalSymbol,
                            'timeframe' => $finalTimeframe,
                            'candle_timestamp' => $c['timestamp'],
                            'open' => $c['open'],
                            'high' => $c['high'],
                            'low' => $c['low'],
                            'close' => $c['close'],
                            'volume' => $c['volume'],
                            'source' => 'user_csv',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    MarketData::insert($records);
                }

                $scan->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                AuditLog::log('scan_completed', 'Scan', (string)$scan->id, [
                    'symbol' => $finalSymbol,
                    'timeframe' => $finalTimeframe,
                    'direction' => $plan['direction'],
                ], $user->id);

                return $scan;
            });

            return redirect()->route('scans.show', $scan)->with('success', "AI analysis completed for {$scan->symbol} ({$scan->timeframe})!");
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('SCAN_FAILED_EXCEPTION: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            AuditLog::log('scan_failed', 'Scan', null, [
                'error' => $e->getMessage(),
                'symbol' => $symbol,
            ], $user->id);

            return redirect()->back()->withInput()->with('error', 'Analysis failed: ' . $e->getMessage());
        }
    }

    /**
     * Display paginated user scan history.
     */
    public function history(Request $request): View
    {
        $user = $request->user();
        $query = Scan::where('user_id', $user->id)->with(['result', 'input'])->latest('id');

        if ($request->filled('symbol')) {
            $query->where('symbol', 'ilike', '%' . trim($request->symbol) . '%');
        }

        if ($request->filled('timeframe')) {
            $query->where('timeframe', $request->timeframe);
        }

        if ($request->filled('direction')) {
            $query->whereHas('result', fn($q) => $q->where('direction', $request->direction));
        }

        $scans = $query->paginate(12)->withQueryString();

        return view('scans.index', compact('scans'));
    }

    /**
     * Display a specific scan analysis report.
     */
    public function show(Request $request, Scan $scan): View
    {
        // Enforce user isolation: User can only view their own scans unless admin
        if ($scan->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Unauthorized access to private scan report.');
        }

        $scan->load(['result', 'input', 'analysisRun.agentResults', 'analysisRun.consensusResult', 'analysisRun.collaborationMessages']);
        $marketData = MarketData::where('scan_id', $scan->id)->orderBy('candle_timestamp')->get();

        return view('scans.show', compact('scan', 'marketData'));
    }

    /**
     * Delete a scan record.
     */
    public function destroy(Request $request, Scan $scan): RedirectResponse
    {
        if ($scan->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Unauthorized to delete this scan.');
        }

        $scan->delete();

        return redirect()->route('scans.index')->with('success', 'Scan analysis deleted successfully.');
    }

    /**
     * Export printable HTML/PDF report.
     */
    public function exportPdf(Request $request, Scan $scan): View
    {
        if ($scan->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $scan->load(['result', 'input']);
        return view('scans.report-print', compact('scan'));
    }

    /**
     * Export CSV data of the scan's candles and calculated indicators.
     */
    public function exportCsv(Request $request, Scan $scan): Response
    {
        if ($scan->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $candles = MarketData::where('scan_id', $scan->id)->orderBy('candle_timestamp')->get();

        $csvHeader = ['Timestamp', 'Symbol', 'Timeframe', 'Open', 'High', 'Low', 'Close', 'Volume'];
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $csvHeader);

        foreach ($candles as $c) {
            fputcsv($output, [
                $c->candle_timestamp->toIso8601String(),
                $c->symbol,
                $c->timeframe,
                $c->open,
                $c->high,
                $c->low,
                $c->close,
                $c->volume,
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"ChartSense_{$scan->symbol}_{$scan->timeframe}_Data.csv\"",
        ]);
    }

    /**
     * Safely serve the uploaded chart screenshot for this scan.
     */
    public function screenshot(Request $request, Scan $scan): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if ($scan->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $input = $scan->input;
        if (!$input || !$input->screenshot_path || !file_exists($input->screenshot_path)) {
            abort(404, 'Chart screenshot not found.');
        }

        // Hardened path-traversal check: Ensure file resides strictly within authorized storage
        $realPath = realpath($input->screenshot_path);
        $allowedBase = realpath(storage_path('app'));
        if (!$realPath || !str_starts_with($realPath, $allowedBase)) {
            abort(403, 'Unauthorized file location.');
        }

        return response()->file($realPath);
    }
}
