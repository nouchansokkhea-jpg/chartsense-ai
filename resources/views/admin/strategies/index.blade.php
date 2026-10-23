@extends('layouts.admin')

@section('title', 'Strategy Studio - ChartSense AI Admin')

@section('content')
<div class="space-y-8">
    <!-- Header with Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-3">
                <span>Global Trading Strategy Studio</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-950 text-indigo-300 border border-indigo-800">
                    7 Global Schools
                </span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Centralized repository of compiled trading methodologies across Price Action, ICT/SMC, Fibonacci, Wyckoff, Quantitative, BBMA, and Indicator-based systems.
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.strategies.research') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-900/40 transition">
                <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                Research Lab &amp; Compiler
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Registered Strategies</span>
            <div class="flex items-baseline justify-between pt-1">
                <span class="text-3xl font-extrabold text-white font-mono">{{ $totalStrategies }}</span>
                <span class="text-xs text-indigo-400 font-semibold">{{ $categories->count() }} Categories</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active In Production</span>
            <div class="flex items-baseline justify-between pt-1">
                <span class="text-3xl font-extrabold text-emerald-400 font-mono">{{ $activeStrategies }}</span>
                <span class="text-xs text-emerald-400 font-semibold">Available for RAG</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Historical Evaluations</span>
            <div class="flex items-baseline justify-between pt-1">
                <span class="text-3xl font-extrabold text-cyan-400 font-mono">{{ $totalEvaluations }}</span>
                <span class="text-xs text-slate-400 font-semibold">Tracked Datasets</span>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Statistically Ranked</span>
            <div class="flex items-baseline justify-between pt-1">
                <span class="text-3xl font-extrabold text-amber-400 font-mono">{{ $rankedCount }}</span>
                <span class="text-xs text-amber-400 font-semibold">&ge; 20 Trade Sample</span>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
        <form method="GET" action="{{ route('admin.strategies.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex items-center space-x-2">
                <label for="category" class="text-slate-400 font-semibold">Category:</label>
                <select name="category" id="category" onchange="this.form.submit()" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-slate-200 text-xs focus:ring-1 focus:ring-cyan-500">
                    <option value="">All Categories ({{ $totalStrategies }})</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->slug }}" {{ $categorySlug === $cat->slug ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <label for="status" class="text-slate-400 font-semibold">Status:</label>
                <select name="status" id="status" onchange="this.form.submit()" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-slate-200 text-xs focus:ring-1 focus:ring-cyan-500">
                    <option value="">All Statuses</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>
            </div>

            @if($categorySlug || $status)
                <a href="{{ route('admin.strategies.index') }}" class="text-xs text-slate-400 hover:text-white underline">
                    Clear Filters
                </a>
            @endif
        </form>

        <span class="text-slate-500 text-[11px] font-mono">
            Showing {{ $strategies->firstItem() ?? 0 }} - {{ $strategies->lastItem() ?? 0 }} of {{ $strategies->total() }} Strategies
        </span>
    </div>

    <!-- Strategies Table -->
    <div class="rounded-3xl bg-slate-900/80 border border-slate-800 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-950/60 text-slate-400 uppercase tracking-wider text-[10px] font-bold">
                        <th class="py-3.5 px-4">Strategy &amp; School</th>
                        <th class="py-3.5 px-4">Feasibility</th>
                        <th class="py-3.5 px-4">Regime Suitability</th>
                        <th class="py-3.5 px-4">Empirical Performance</th>
                        <th class="py-3.5 px-4 text-center">Active Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($strategies as $strat)
                        @php
                            $eval = $strat->evaluations->sortByDesc('sample_size_trades')->first();
                            $ver = $strat->currentVersion;
                            $feasibility = $strat->implementation_status;
                            $feasibilityBadge = match($feasibility) {
                                'fully_deterministic' => 'bg-emerald-950 text-emerald-300 border-emerald-800',
                                'partially_rule_based' => 'bg-cyan-950 text-cyan-300 border-cyan-800',
                                'discretionary' => 'bg-amber-950 text-amber-300 border-amber-800',
                                default => 'bg-slate-800 text-slate-400 border-slate-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition">
                            <!-- Name & School -->
                            <td class="py-4 px-4 space-y-1">
                                <div class="flex items-center space-x-2">
                                    <span class="font-extrabold text-white text-sm">{{ $strat->name }}</span>
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-mono font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                        v{{ $ver?->version_number ?? '1.0.0' }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2 text-[11px] text-slate-400">
                                    <span class="text-indigo-400 font-medium">{{ $strat->category_name }}</span>
                                    <span>&bull;</span>
                                    <span class="truncate max-w-xs">{{ Str::limit($strat->description, 55) }}</span>
                                </div>
                            </td>

                            <!-- Feasibility -->
                            <td class="py-4 px-4">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $feasibilityBadge }} capitalize">
                                    {{ str_replace('_', ' ', $feasibility) }}
                                </span>
                            </td>

                            <!-- Regime Suitability -->
                            <td class="py-4 px-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @foreach(array_slice($strat->market_regime_suitability ?? [], 0, 2) as $reg)
                                        <span class="px-2 py-0.5 rounded text-[10px] bg-slate-950 text-slate-300 border border-slate-800 font-mono">
                                            {{ strtoupper(str_replace('_', ' ', $reg)) }}
                                        </span>
                                    @endforeach
                                    @if(count($strat->market_regime_suitability ?? []) > 2)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] text-slate-500 font-mono">
                                            +{{ count($strat->market_regime_suitability) - 2 }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Empirical Performance -->
                            <td class="py-4 px-4">
                                @if($eval && $eval->sample_size_trades >= 20)
                                    <div class="space-y-0.5">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono uppercase bg-emerald-950 text-emerald-300 border border-emerald-800">
                                                {{ str_replace('_', ' ', $eval->ranking_tier) }}
                                            </span>
                                            <span class="text-white font-mono font-bold">{{ $eval->win_rate_pct }}% WR</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono">
                                            PF: {{ $eval->profit_factor }} | Exp: +{{ $eval->expectancy_r }}R (N={{ $eval->sample_size_trades }})
                                        </div>
                                    </div>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-950/60 text-amber-300 border border-amber-800/80 font-mono">
                                        N={{ $eval?->sample_size_trades ?? 0 }} &bull; INSUFFICIENT DATA
                                    </span>
                                @endif
                            </td>

                            <!-- Active Toggle -->
                            <td class="py-4 px-4 text-center">
                                <form method="POST" action="{{ route('admin.strategies.toggle-active', $strat) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 rounded-full text-[10px] font-bold transition {{ $strat->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-800 hover:bg-emerald-900' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700' }}">
                                        {{ $strat->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-4 text-right space-x-2">
                                <a href="{{ route('admin.strategies.evaluations', $strat) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition">
                                    Evaluations
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 px-4 text-center text-slate-500">
                                No strategies match the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($strategies->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                {{ $strategies->links() }}
            </div>
        @endif
    </div>

    <!-- Architectural Integrity Notice -->
    <div class="p-5 rounded-2xl bg-slate-900/40 border border-slate-800 text-xs text-slate-400 space-y-2">
        <h3 class="font-bold text-white text-xs uppercase tracking-wider flex items-center space-x-2">
            <svg class="w-4 h-4 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <span>Architectural Integrity &amp; Sample Size Policy</span>
        </h3>
        <p class="leading-relaxed">
            In compliance with quantitative research principles, ChartSense AI strictly distinguishes dynamic in-context retrieval from model fine-tuning. Strategies with fewer than 20 validated trade executions are flagged as <em>INSUFFICIENT DATA</em> and cannot be ranked as superior to other methodologies.
        </p>
    </div>
</div>
@endsection
