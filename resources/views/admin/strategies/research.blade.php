@extends('layouts.admin')

@section('title', 'Strategy Research Lab & Compiler - ChartSense AI Admin')

@section('content')
<div class="space-y-8 max-w-5xl">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.strategies.index') }}" class="hover:text-cyan-400">&larr; Back to Strategy Studio</a>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-3">
                <span>Strategy Research Lab &amp; Compiler</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-950 text-indigo-300 border border-indigo-800">
                    Rule Ingestion Engine
                </span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Translate qualitative trading concepts from books, mentorships, and quantitative papers into structured, testable strategy specifications.
            </p>
        </div>
    </div>

    <!-- Feasibility Classification Reference -->
    <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3 text-xs">
        <h3 class="font-bold text-white text-xs uppercase tracking-wider flex items-center space-x-2">
            <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <span>Automated Implementation Feasibility Standard</span>
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="p-3 rounded-xl bg-slate-950/80 border border-emerald-900/40 space-y-1">
                <span class="font-bold text-emerald-400 block">1. Fully Deterministic</span>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                    Rules expressed exclusively in mathematical formulas, numerical thresholds, indicator crossovers, or fixed percentage/pip stops.
                </p>
            </div>
            <div class="p-3 rounded-xl bg-slate-950/80 border border-cyan-900/40 space-y-1">
                <span class="font-bold text-cyan-400 block">2. Partially Rule-Based</span>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                    Combines deterministic metrics with algorithmic pattern detection (e.g. FVG recognition, swing pivot BOS/CHOCH, BBMA bands).
                </p>
            </div>
            <div class="p-3 rounded-xl bg-slate-950/80 border border-amber-900/40 space-y-1">
                <span class="font-bold text-amber-400 block">3. Discretionary</span>
                <p class="text-slate-400 text-[11px] leading-relaxed">
                    Contains non-quantifiable language ("feel of the tape", "intuition") or requires unavailable private Level-2 data feeds.
                </p>
            </div>
        </div>
    </div>

    <!-- Strategy Ingestion Form -->
    <form method="POST" action="{{ route('admin.strategies.store') }}" class="p-6 sm:p-8 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-6 shadow-2xl">
        @csrf

        <!-- Category & Name -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="category_id" class="text-xs font-semibold text-slate-300 block mb-1.5">
                    Trading School / Category <span class="text-rose-400">*</span>
                </label>
                <select name="category_id" id="category_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white text-xs focus:ring-2 focus:ring-indigo-500">
                    <option value="">Select Trading School...</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="name" class="text-xs font-semibold text-slate-300 block mb-1.5">
                    Strategy Name <span class="text-rose-400">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="e.g. ICT Silver Bullet, Break and Retest" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-white text-xs focus:ring-2 focus:ring-indigo-500">
                @error('name') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="text-xs font-semibold text-slate-300 block mb-1.5">
                Strategy Concept &amp; Core Thesis <span class="text-rose-400">*</span>
            </label>
            <textarea name="description" id="description" rows="2" required placeholder="Describe the market inefficiency, behavioral dynamic, or statistical edge this strategy targets..." class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3.5 text-white text-xs focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
            @error('description') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <!-- Market Regime Suitability -->
        <div>
            <span class="text-xs font-semibold text-slate-300 block mb-2">
                Intended Market Regimes <span class="text-rose-400">*</span>
            </span>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
                @php
                    $regimes = [
                        'TRENDING_BULLISH' => 'Trending Bullish',
                        'TRENDING_BEARISH' => 'Trending Bearish',
                        'RANGING_TIGHT' => 'Ranging Tight (Squeeze)',
                        'RANGING_EXPANDING' => 'Ranging Expanding (Swing)',
                        'VOLATILE' => 'High Volatility Breakout',
                        'all' => 'All Market Regimes',
                    ];
                @endphp
                @foreach($regimes as $key => $label)
                    <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-950/60 border border-slate-800 hover:border-slate-700 cursor-pointer transition">
                        <input type="checkbox" name="market_regimes[]" value="{{ $key }}" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-slate-300 text-xs">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('market_regimes') <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <!-- Rules Section (One per line) -->
        <div class="space-y-4 pt-4 border-t border-slate-800/80">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Algorithmic &amp; Operational Rule Definitions</h3>
            <p class="text-xs text-slate-400">Enter each condition on a new line. The compiler normalizes these into the executable JSON schema.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Entry Rules -->
                <div>
                    <label for="entry_rules_raw" class="text-xs font-semibold text-emerald-400 block mb-1.5">
                        Mandatory Entry Trigger Rules <span class="text-rose-400">*</span>
                    </label>
                    <textarea name="entry_rules_raw" id="entry_rules_raw" rows="4" required placeholder="Price reaches key liquidity level&#10;Displacement creates verified Fair Value Gap&#10;First re-test of FVG within session window" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-xs font-mono focus:ring-2 focus:ring-emerald-500">{{ old('entry_rules_raw') }}</textarea>
                    <span class="text-[10px] text-slate-500 block mt-1">One rule per line</span>
                </div>

                <!-- Stop Loss Rules -->
                <div>
                    <label for="stop_loss_rules_raw" class="text-xs font-semibold text-rose-400 block mb-1.5">
                        Stop Loss &amp; Risk Rules <span class="text-rose-400">*</span>
                    </label>
                    <textarea name="stop_loss_rules_raw" id="stop_loss_rules_raw" rows="4" required placeholder="Stop placed behind displacement swing anchor&#10;Maximum risk: 1.5% of total capital&#10;Buffer minimum 5 pips beyond swing pivot" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-xs font-mono focus:ring-2 focus:ring-rose-500">{{ old('stop_loss_rules_raw') }}</textarea>
                    <span class="text-[10px] text-slate-500 block mt-1">One rule per line</span>
                </div>

                <!-- Take Profit Rules -->
                <div>
                    <label for="take_profit_rules_raw" class="text-xs font-semibold text-cyan-400 block mb-1.5">
                        Take Profit &amp; Target Rules <span class="text-rose-400">*</span>
                    </label>
                    <textarea name="take_profit_rules_raw" id="take_profit_rules_raw" rows="4" required placeholder="TP1 at nearest external swing liquidity pool (min 1:2 R:R)&#10;TP2 at higher timeframe structural resistance&#10;TP3 trailing runner with swing structure" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-xs font-mono focus:ring-2 focus:ring-cyan-500">{{ old('take_profit_rules_raw') }}</textarea>
                    <span class="text-[10px] text-slate-500 block mt-1">One rule per line</span>
                </div>

                <!-- Invalidation Rules -->
                <div>
                    <label for="invalidation_rules_raw" class="text-xs font-semibold text-amber-400 block mb-1.5">
                        Setup Invalidation Rules
                    </label>
                    <textarea name="invalidation_rules_raw" id="invalidation_rules_raw" rows="4" placeholder="Candle closes through opposite swing pivot&#10;High-impact news release occurs before entry trigger&#10;FVG is fully closed without reaction" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white text-xs font-mono focus:ring-2 focus:ring-amber-500">{{ old('invalidation_rules_raw') }}</textarea>
                    <span class="text-[10px] text-slate-500 block mt-1">One rule per line</span>
                </div>
            </div>
        </div>

        <!-- Academic & Educational Citation -->
        <div class="space-y-4 pt-4 border-t border-slate-800/80">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Educational Source &amp; Research Provenance</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="source_author" class="text-xs font-semibold text-slate-400 block mb-1">Author / Creator</label>
                    <input type="text" name="source_author" id="source_author" value="{{ old('source_author') }}" placeholder="e.g. Michael J. Huddleston, Oma Ally, John Murphy" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-xs focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="source_title" class="text-xs font-semibold text-slate-400 block mb-1">Book / Mentorship / Paper</label>
                    <input type="text" name="source_title" id="source_title" value="{{ old('source_title') }}" placeholder="e.g. ICT 2022 Mentorship, Technical Analysis of the Financial Markets" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-xs focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="source_url" class="text-xs font-semibold text-slate-400 block mb-1">Source URL / DOI</label>
                    <input type="url" name="source_url" id="source_url" value="{{ old('source_url') }}" placeholder="https://..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-xs focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
            <span class="text-[11px] text-slate-500 font-mono">
                Will be compiled as Version 1.0.0 and registered for dynamic multi-AI retrieval.
            </span>
            <button type="submit" class="px-6 py-3 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-xl shadow-indigo-950/60 transition">
                Compile &amp; Register Strategy &rarr;
            </button>
        </div>
    </form>
</div>
@endsection
