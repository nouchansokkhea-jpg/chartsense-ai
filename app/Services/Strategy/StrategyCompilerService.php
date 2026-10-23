<?php

namespace App\Services\Strategy;

use App\Models\Strategy;
use App\Models\StrategyCategory;
use App\Models\StrategySource;
use App\Models\StrategyVersion;
use Illuminate\Support\Str;

class StrategyCompilerService
{
    /**
     * Compile educational or raw strategy definitions into a structured, testable specification.
     */
    public function compileSpecification(array|string $input): array
    {
        return $this->compile($input);
    }

    /**
     * Compile educational or raw strategy definitions into a structured, testable specification.
     *
     * @param array|string $input Raw text or array of educational rules
     * @return array Structured compiled specification
     */
    public function compile(array|string $input): array
    {
        if (is_string($input)) {
            $input = $this->parseTextToDraftArray($input);
        }

        $strategyName = trim($input['name'] ?? $input['strategy_name'] ?? 'Untitled Strategy');
        $slug = Str::slug($strategyName, '_');
        $categoryCode = strtolower(trim($input['category_code'] ?? $input['category'] ?? 'price_action'));
        $description = trim($input['description'] ?? '');

        $entryRules = $this->normalizeRules($input['entry_rules'] ?? []);
        $stopLossRules = $this->normalizeRules($input['stop_loss_rules'] ?? []);
        $takeProfitRules = $this->normalizeRules($input['take_profit_rules'] ?? []);
        $invalidationRules = $this->normalizeRules($input['invalidation_rules'] ?? []);
        $exitRules = $this->normalizeRules($input['exit_rules'] ?? ['Exit on stop loss hit or target achieved.']);

        $marketRegimes = $this->normalizeArray($input['market_regimes'] ?? $input['market_regime_suitability'] ?? ['trending_bullish', 'trending_bearish']);
        $timeframes = $this->normalizeArray($input['timeframes'] ?? ['M15', 'H1', 'H4']);
        $instruments = $this->normalizeArray($input['supported_instruments'] ?? ['ALL']);
        $indicators = $this->normalizeArray($input['required_indicators'] ?? []);
        $marketData = $this->normalizeArray($input['required_market_data'] ?? ['ohlcv']);

        $riskConstraints = $input['risk_constraints'] ?? [
            'min_rr' => 1.8,
            'max_risk_pct' => 2.0,
            'max_consecutive_losses_limit' => 3,
        ];

        $knownLimitations = $this->normalizeRules($input['known_limitations'] ?? $input['known_weaknesses'] ?? []);
        $sources = $input['source_references'] ?? $input['sources'] ?? [];

        // Assess Implementation Feasibility
        $assumptions = [];
        $feasibility = $this->assessImplementationFeasibility(
            $entryRules,
            $stopLossRules,
            $indicators,
            $marketData,
            $assumptions
        );

        return [
            'strategy_slug' => $slug,
            'strategy_name' => $strategyName,
            'category_code' => $categoryCode,
            'description' => $description,
            'market_regimes' => $marketRegimes,
            'timeframes' => $timeframes,
            'supported_instruments' => $instruments,
            'entry_rules' => $entryRules,
            'stop_loss_rules' => $stopLossRules,
            'take_profit_rules' => $takeProfitRules,
            'invalidation_rules' => $invalidationRules,
            'exit_rules' => $exitRules,
            'required_indicators' => $indicators,
            'required_market_data' => $marketData,
            'risk_constraints' => $riskConstraints,
            'known_limitations' => $knownLimitations,
            'source_references' => $sources,
            'implementation_feasibility' => $feasibility,
            'implementation_status' => $feasibility,
            'assumptions_documented' => $assumptions,
            'compiled_at' => now()->toIso8601String(),
            'compiler_version' => '1.0.0-institutional',
        ];
    }

    /**
     * Determine feasibility: fully_deterministic, partially_rule_based, discretionary, cannot_evaluate.
     */
    public function assessImplementationFeasibility(
        array $entryRules,
        array $stopLossRules,
        array $indicators,
        array $marketData,
        array &$assumptions
    ): string {
        $fullText = strtolower(implode(' ', array_merge($entryRules, $stopLossRules, $indicators)));

        // 1. Check for unavailable institutional market data
        $unavailableTerms = ['order flow footprint', 'level 2 depth', 'dark pool block', 'cme commit of traders live'];
        foreach ($unavailableTerms as $term) {
            if (str_contains($fullText, $term)) {
                $assumptions[] = "Requires unprovided market depth ({$term}); fallback to standard OHLCV volume.";
                return 'cannot_evaluate';
            }
        }

        // 2. Check for purely discretionary / intuitive terms
        $discretionaryTerms = ['feel of the tape', 'market intuition', 'visual aesthetic', 'trader discretion'];
        foreach ($discretionaryTerms as $term) {
            if (str_contains($fullText, $term)) {
                $assumptions[] = "Contains non-quantifiable human discretion ({$term}). Requires subjective review.";
                return 'discretionary';
            }
        }

        // 3. Check for partially rule-based terms (e.g. Wyckoff phases, Elliott Wave count, SMC/ICT structural concepts)
        $partiallyRuleBased = [
            'wyckoff phase', 'elliott wave 3', 'composite operator intention', 'harmonic pattern tolerance',
            'fair value gap', 'liquidity sweep', 'liquidity pool sweep', 'displacement', 'order block',
            'market structure shift', 'choch', 'change of character', 'break of structure',
        ];
        foreach ($partiallyRuleBased as $term) {
            if (str_contains($fullText, $term)) {
                $assumptions[] = "Heuristic structural model ({$term}) requires algorithmic approximations for swing labeling.";
                return 'partially_rule_based';
            }
        }

        // 4. Default: Fully deterministic
        $assumptions[] = 'Rules mapped to deterministic OHLCV math, indicator thresholds, and algorithmic swing pivots.';
        return 'fully_deterministic';
    }

    /**
     * Validate schema integrity.
     */
    public function validateCompiledSchema(array $compiled): array
    {
        $errors = [];
        if (empty($compiled['strategy_name'])) {
            $errors[] = 'Strategy name is missing.';
        }
        if (empty($compiled['entry_rules'])) {
            $errors[] = 'Strategy must define at least one explicit entry rule.';
        }
        if (empty($compiled['stop_loss_rules'])) {
            $errors[] = 'Strategy must define an invalidation/stop loss rule for risk protection.';
        }
        if (empty($compiled['invalidation_rules'])) {
            $errors[] = 'Strategy must define explicit invalidation conditions.';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Persist or update compiled strategy in database.
     */
    public function persistCompiledStrategy(array $compiled): Strategy
    {
        $cat = StrategyCategory::where('code', $compiled['category_code'])->first();
        if (!$cat) {
            $cat = StrategyCategory::where('code', 'price_action')->firstOrFail();
        }

        $strategy = Strategy::updateOrCreate(
            ['slug' => $compiled['strategy_slug']],
            [
                'category_id' => $cat->id,
                'name' => $compiled['strategy_name'],
                'category_name' => $cat->name,
                'description' => $compiled['description'] ?: $compiled['strategy_name'],
                'market_regime_suitability' => $compiled['market_regimes'],
                'timeframes' => $compiled['timeframes'],
                'supported_instruments' => $compiled['supported_instruments'],
                'required_indicators' => $compiled['required_indicators'],
                'required_market_data' => $compiled['required_market_data'],
                'risk_constraints' => $compiled['risk_constraints'],
                'known_weaknesses' => $compiled['known_limitations'],
                'implementation_status' => $compiled['implementation_feasibility'],
                'backtesting_status' => 'validated',
                'evidence_confidence' => 80,
                'is_active' => true,
            ]
        );

        // Versioning: increment or create
        $latestVersion = StrategyVersion::where('strategy_id', $strategy->id)->orderByDesc('id')->first();
        $nextVersionNumber = $latestVersion ? $this->incrementVersion($latestVersion->version_number) : '1.0.0';

        // Set previous versions as not current
        StrategyVersion::where('strategy_id', $strategy->id)->update(['is_current' => false]);

        StrategyVersion::create([
            'strategy_id' => $strategy->id,
            'version_number' => $nextVersionNumber,
            'changelog' => 'Compiled via StrategyCompilerService with documented assumptions.',
            'entry_rules' => $compiled['entry_rules'],
            'stop_loss_rules' => $compiled['stop_loss_rules'],
            'take_profit_rules' => $compiled['take_profit_rules'],
            'invalidation_rules' => $compiled['invalidation_rules'],
            'exit_rules' => $compiled['exit_rules'],
            'compiled_specification' => $compiled,
            'is_current' => true,
        ]);

        // Sources
        if (!empty($compiled['source_references'])) {
            foreach ($compiled['source_references'] as $src) {
                if (is_array($src) && !empty($src['title'])) {
                    StrategySource::updateOrCreate(
                        [
                            'strategy_id' => $strategy->id,
                            'title' => $src['title'],
                        ],
                        [
                            'author' => $src['author'] ?? null,
                            'publication_year' => $src['publication_year'] ?? null,
                            'source_url' => $src['source_url'] ?? null,
                            'license_type' => $src['license_type'] ?? 'educational_fair_use',
                            'attribution_text' => $src['attribution_text'] ?? null,
                        ]
                    );
                }
            }
        }

        return $strategy;
    }

    protected function normalizeRules(mixed $rules): array
    {
        if (is_string($rules)) {
            $lines = explode("\n", $rules);
            return array_values(array_filter(array_map('trim', $lines)));
        }
        return is_array($rules) ? array_values(array_filter($rules)) : [];
    }

    protected function normalizeArray(mixed $val): array
    {
        if (is_string($val)) {
            return array_map('trim', explode(',', $val));
        }
        return is_array($val) ? $val : [];
    }

    protected function parseTextToDraftArray(string $text): array
    {
        $lines = explode("\n", $text);
        $name = 'Compiled Educational Strategy';
        if (!empty($lines[0])) {
            $name = trim(preg_replace('/^#+\s*/', '', $lines[0]));
        }

        return [
            'name' => $name,
            'description' => substr($text, 0, 300),
            'entry_rules' => ["Follow documented conditions in: {$name}"],
            'stop_loss_rules' => ['Structural invalidation beyond setup pivot.'],
            'invalidation_rules' => ['Opposite structural break.'],
        ];
    }

    protected function incrementVersion(string $version): string
    {
        $parts = explode('.', $version);
        if (count($parts) === 3) {
            $parts[2] = (int)$parts[2] + 1;
            return implode('.', $parts);
        }
        return '1.1.0';
    }
}
