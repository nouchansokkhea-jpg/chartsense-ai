<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Strategy;
use App\Models\StrategyCategory;
use App\Models\StrategyEvaluation;
use App\Models\StrategySource;
use App\Models\StrategyVersion;
use App\Services\Strategy\StrategyCompilerService;
use App\Services\Strategy\StrategyEvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminStrategyController extends Controller
{
    public function __construct(
        protected StrategyCompilerService $compilerService,
        protected StrategyEvaluationService $evaluationService
    ) {}

    /**
     * Display the Global Trading Strategy Studio index.
     */
    public function index(Request $request): View
    {
        $categorySlug = $request->input('category');
        $regime = $request->input('regime');
        $status = $request->input('status');

        $query = Strategy::with(['category', 'currentVersion', 'evaluations', 'sources'])
            ->orderBy('category_id')
            ->orderBy('name');

        if ($categorySlug) {
            $query->whereHas('category', fn($q) => $q->where('code', $categorySlug));
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $strategies = $query->paginate(15)->withQueryString();
        $categories = StrategyCategory::orderBy('sort_order')->get();

        // Metrics for summary cards
        $totalStrategies = Strategy::count();
        $activeStrategies = Strategy::where('is_active', true)->count();
        $totalEvaluations = StrategyEvaluation::count();
        $rankedCount = StrategyEvaluation::where('sample_size_trades', '>=', 20)->count();

        return view('admin.strategies.index', compact(
            'strategies',
            'categories',
            'totalStrategies',
            'activeStrategies',
            'totalEvaluations',
            'rankedCount',
            'categorySlug',
            'regime',
            'status'
        ));
    }

    /**
     * Display the Strategy Research Lab & Compiler.
     */
    public function researchLab(Request $request): View
    {
        $categories = StrategyCategory::orderBy('sort_order')->get();
        $recentStrategies = Strategy::with('category')->latest()->take(5)->get();

        return view('admin.strategies.research', compact('categories', 'recentStrategies'));
    }

    /**
     * Compile and register a new strategy into the knowledge base.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:strategy_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'market_regimes' => ['required', 'array'],
            'timeframes' => ['nullable', 'array'],
            'supported_instruments' => ['nullable', 'array'],
            'entry_rules_raw' => ['required', 'string'],
            'stop_loss_rules_raw' => ['required', 'string'],
            'take_profit_rules_raw' => ['required', 'string'],
            'invalidation_rules_raw' => ['nullable', 'string'],
            'source_author' => ['nullable', 'string', 'max:255'],
            'source_title' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:500'],
        ]);

        $category = StrategyCategory::findOrFail($validated['category_id']);
        $slug = Str::slug($validated['name']);

        // Check uniqueness of slug
        if (Strategy::where('slug', $slug)->exists()) {
            $slug .= '-' . Str::random(4);
        }

        // Parse raw newline-delimited rules into clean arrays
        $entryRules = array_values(array_filter(array_map('trim', explode("\n", $validated['entry_rules_raw']))));
        $stopLossRules = array_values(array_filter(array_map('trim', explode("\n", $validated['stop_loss_rules_raw']))));
        $takeProfitRules = array_values(array_filter(array_map('trim', explode("\n", $validated['take_profit_rules_raw']))));
        $invalidationRules = !empty($validated['invalidation_rules_raw'])
            ? array_values(array_filter(array_map('trim', explode("\n", $validated['invalidation_rules_raw']))))
            : ['Opposite structural swing pivot breach'];

        // Compile strategy and classify feasibility
        $compiledSpec = $this->compilerService->compileSpecification([
            'name' => $validated['name'],
            'category_slug' => $category->slug,
            'description' => $validated['description'],
            'intended_market_conditions' => implode(', ', $validated['market_regimes']),
            'market_regime_suitability' => $validated['market_regimes'],
            'timeframes' => $validated['timeframes'] ?? ['15m', '1h', '4h'],
            'supported_instruments' => $validated['supported_instruments'] ?? ['all'],
            'entry_rules' => $entryRules,
            'stop_loss_rules' => $stopLossRules,
            'take_profit_rules' => $takeProfitRules,
            'invalidation_rules' => $invalidationRules,
        ]);

        $strategy = Strategy::create([
            'category_id' => $category->id,
            'slug' => $slug,
            'name' => $validated['name'],
            'category_name' => $category->name,
            'description' => $validated['description'],
            'intended_market_conditions' => implode(', ', $validated['market_regimes']),
            'market_regime_suitability' => $validated['market_regimes'],
            'timeframes' => $validated['timeframes'] ?? ['15m', '1h', '4h'],
            'supported_instruments' => $validated['supported_instruments'] ?? ['all'],
            'implementation_status' => $compiledSpec['implementation_status'] ?? 'partially_rule_based',
            'backtesting_status' => 'untested',
            'evidence_confidence' => 40,
            'is_active' => true,
        ]);

        // Create Version 1.0.0
        StrategyVersion::create([
            'strategy_id' => $strategy->id,
            'version_number' => '1.0.0',
            'changelog' => 'Initial compiled release from research lab.',
            'entry_rules' => $entryRules,
            'stop_loss_rules' => $stopLossRules,
            'take_profit_rules' => $takeProfitRules,
            'invalidation_rules' => $invalidationRules,
            'exit_rules' => ['Take-profit targets achieved or trailing stop hit'],
            'compiled_specification' => $compiledSpec,
            'is_current' => true,
        ]);

        // Record source if provided
        if (!empty($validated['source_title']) || !empty($validated['source_author'])) {
            StrategySource::create([
                'strategy_id' => $strategy->id,
                'source_type' => 'book',
                'title' => $validated['source_title'] ?? $validated['name'],
                'author' => $validated['source_author'] ?? 'Educational Source',
                'url' => $validated['source_url'] ?? null,
                'citation' => sprintf('%s by %s', $validated['source_title'] ?? $validated['name'], $validated['source_author'] ?? 'Research Archive'),
                'is_primary' => true,
            ]);
        }

        // Add initial unranked evaluation record with sample_size = 0 (< 20 trades -> insufficient_data)
        StrategyEvaluation::create([
            'strategy_id' => $strategy->id,
            'symbol' => 'ALL',
            'timeframe' => 'ALL',
            'market_regime' => 'ALL',
            'sample_size_trades' => 0,
            'wins' => 0,
            'losses' => 0,
            'win_rate_pct' => 0.0,
            'profit_factor' => 0.0,
            'expectancy_r' => 0.0,
            'max_drawdown_r' => 0.0,
            'net_profit_r' => 0.0,
            'avg_rr_ratio' => 1.5,
            'slippage_sensitivity_score' => 0.0,
            'out_of_sample_tested' => false,
            'ranking_tier' => 'insufficient_data',
            'evaluation_notes' => 'New strategy compiled. Pending empirical backtesting. NOT ENOUGH VALIDATED DATA (<20 trades) TO RANK.',
            'evaluated_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'strategy_compiled',
            'entity_type' => 'Strategy',
            'entity_id' => $strategy->id,
            'details' => [
                'name' => $strategy->name,
                'category' => $category->name,
                'feasibility' => $strategy->implementation_status,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.strategies.index')
            ->with('success', "Strategy '{$strategy->name}' compiled successfully as v1.0.0 ({$strategy->implementation_status})!");
    }

    /**
     * Toggle strategy active state.
     */
    public function toggleActive(Request $request, Strategy $strategy): RedirectResponse
    {
        $strategy->update([
            'is_active' => !$strategy->is_active,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'strategy_status_toggled',
            'entity_type' => 'Strategy',
            'entity_id' => $strategy->id,
            'details' => ['is_active' => $strategy->is_active],
            'ip_address' => $request->ip(),
        ]);

        $status = $strategy->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Strategy '{$strategy->name}' has been {$status}.");
    }

    /**
     * View empirical evaluation metrics and history for a strategy.
     */
    public function evaluations(Strategy $strategy): View
    {
        $strategy->load(['category', 'currentVersion', 'evaluations', 'sources']);
        return view('admin.strategies.evaluations', compact('strategy'));
    }
}
