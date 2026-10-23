@extends('layouts.admin')

@section('title', "{$strategy->name} Evaluations - ChartSense AI Admin")

@section('content')
<div class="space-y-8 max-w-5xl">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.strategies.index') }}" class="hover:text-cyan-400">&larr; Back to Strategy Studio</a>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-3">
                <span>{{ $strategy->name }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-indigo-950 text-indigo-300 border border-indigo-800">
                    v{{ $strategy->currentVersion?->version_number ?? '1.0.0' }}
                </span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                School: <span class="text-indigo-400 font-medium">{{ $strategy->category_name }}</span> &bull; Feasibility: <span class="text-white capitalize">{{ str_replace('_', ' ', $strategy->implementation_status) }}</span>
            </p>
        </div>

        <div>
            <form method="POST" action="{{ route('admin.strategies.toggle-active', $strategy) }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $strategy->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-800 hover:bg-emerald-900' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700' }}">
                    Status: {{ $strategy->is_active ? 'Active in RAG' : 'Deactivated' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Strategy Scorecard -->
    @php
        $latestEval = $strategy->evaluations->sortByDesc('sample_size_trades')->first();
        $isRankable = ($latestEval && $latestEval->sample_size_trades >= 20);
    @endphp

    <div class="p-6 sm:p-8 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-6 shadow-2xl">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400 block">Empirical Scorecard &amp; Ranking</span>
                <h2 class="text-lg font-bold text-white">Statistical Validation Overview</h2>
            </div>
            <div>
                @if($isRankable)
                    <span class="px-3 py-1.5 rounded-xl font-mono text-xs font-bold bg-emerald-950 text-emerald-300 border border-emerald-800 uppercase">
                        {{ str_replace('_', ' ', $latestEval->ranking_tier) }} &bull; VALIDATED
                    </span>
                @else
                    <span class="px-3 py-1.5 rounded-xl font-mono text-xs font-bold bg-amber-950 text-amber-300 border border-amber-800">
                        NOT ENOUGH VALIDATED DATA (&lt; 20 TRADES) TO RANK
                    </span>
                @endif
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-center">
                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Sample Trades (N)</span>
                <span class="text-xl font-extrabold font-mono text-white">{{ $latestEval?->sample_size_trades ?? 0 }}</span>
                <span class="text-[9px] text-slate-500 block">Min required: 20</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-center">
                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Win Rate %</span>
                <span class="text-xl font-extrabold font-mono text-emerald-400">{{ $latestEval?->win_rate_pct ?? '0.0' }}%</span>
                <span class="text-[9px] text-slate-500 block">{{ $latestEval?->wins ?? 0 }}W / {{ $latestEval?->losses ?? 0 }}L</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-center">
                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Profit Factor</span>
                <span class="text-xl font-extrabold font-mono text-cyan-400">{{ $latestEval?->profit_factor ?? '0.00' }}</span>
                <span class="text-[9px] text-slate-500 block">Gross W / Gross L</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-center">
                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Expectancy (R)</span>
                <span class="text-xl font-extrabold font-mono {{ ($latestEval?->expectancy_r ?? 0) >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ ($latestEval?->expectancy_r ?? 0) > 0 ? '+' : '' }}{{ $latestEval?->expectancy_r ?? '0.00' }}R
                </span>
                <span class="text-[9px] text-slate-500 block">Per Trade Edge</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-center">
                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Max Drawdown</span>
                <span class="text-xl font-extrabold font-mono text-rose-400">{{ $latestEval?->max_drawdown_r ?? '0.0' }}R</span>
                <span class="text-[9px] text-slate-500 block">Peak to Trough</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-center">
                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Net Profit</span>
                <span class="text-xl font-extrabold font-mono text-white">
                    {{ ($latestEval?->net_profit_r ?? 0) > 0 ? '+' : '' }}{{ $latestEval?->net_profit_r ?? '0.0' }}R
                </span>
                <span class="text-[9px] text-slate-500 block">Total Realized</span>
            </div>
        </div>

        @if(!$isRankable)
            <div class="p-4 rounded-xl bg-amber-950/20 border border-amber-900/40 text-xs text-amber-200 leading-relaxed">
                <strong>Sample Size Guardrail:</strong> This strategy currently has {{ $latestEval?->sample_size_trades ?? 0 }} validated trade executions. Under ChartSense quantitative policies, at least 20 trades are mandatory before an empirical ranking tier is assigned.
            </div>
        @endif
    </div>

    <!-- Strategy Rule Specification -->
    @php $ver = $strategy->currentVersion; @endphp
    @if($ver)
        <div class="p-6 sm:p-8 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-6 shadow-2xl">
            <h3 class="text-base font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-3">
                Version {{ $ver->version_number }} Rule Specification
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                <!-- Entry Rules -->
                <div class="space-y-2 p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="font-bold text-emerald-400 uppercase tracking-wider block text-[11px]">Mandatory Entry Rules</span>
                    <ul class="space-y-1.5 text-slate-300">
                        @foreach($ver->entry_rules ?? [] as $rule)
                            <li class="flex items-start space-x-2">
                                <span class="text-emerald-400">&bull;</span>
                                <span>{{ is_string($rule) ? $rule : json_encode($rule) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Stop Loss Rules -->
                <div class="space-y-2 p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="font-bold text-rose-400 uppercase tracking-wider block text-[11px]">Stop Loss &amp; Invalidation Rules</span>
                    <ul class="space-y-1.5 text-slate-300">
                        @foreach($ver->stop_loss_rules ?? [] as $rule)
                            <li class="flex items-start space-x-2">
                                <span class="text-rose-400">&bull;</span>
                                <span>{{ is_string($rule) ? $rule : json_encode($rule) }}</span>
                            </li>
                        @endforeach
                        @foreach($ver->invalidation_rules ?? [] as $rule)
                            <li class="flex items-start space-x-2 text-amber-300">
                                <span>!</span>
                                <span>{{ is_string($rule) ? $rule : json_encode($rule) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Take Profit Rules -->
                <div class="space-y-2 p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="font-bold text-cyan-400 uppercase tracking-wider block text-[11px]">Take Profit Rules</span>
                    <ul class="space-y-1.5 text-slate-300">
                        @foreach($ver->take_profit_rules ?? [] as $rule)
                            <li class="flex items-start space-x-2">
                                <span class="text-cyan-400">&bull;</span>
                                <span>{{ is_string($rule) ? $rule : json_encode($rule) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Known Pitfalls -->
                <div class="space-y-2 p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="font-bold text-amber-400 uppercase tracking-wider block text-[11px]">Known Hazards &amp; Incompatible Regimes</span>
                    <ul class="space-y-1.5 text-slate-400">
                        @foreach($strategy->known_weaknesses ?? ['Avoid during tight consolidation without volume expansion'] as $weakness)
                            <li class="flex items-start space-x-2">
                                <span class="text-amber-400">!</span>
                                <span>{{ $weakness }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Academic Sources / Citations -->
    @if($strategy->sources->isNotEmpty())
        <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3 text-xs">
            <h4 class="font-bold text-white uppercase tracking-wider text-[11px]">Educational Citations &amp; Authors</h4>
            <div class="space-y-2">
                @foreach($strategy->sources as $source)
                    <div class="p-3 rounded-xl bg-slate-950/80 border border-slate-800 flex items-center justify-between text-slate-300">
                        <div>
                            <span class="font-bold text-white">{{ $source->title }}</span>
                            <span class="text-slate-400">by {{ $source->author }}</span>
                        </div>
                        @if($source->url)
                            <a href="{{ $source->url }}" target="_blank" rel="noopener noreferrer" class="text-cyan-400 hover:underline">
                                Reference &rarr;
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
