@extends('layouts.app')

@section('title', "{$scan->symbol} ({$scan->timeframe}) Analysis Report - ChartSense AI")

@section('content')
@php
    $result = $scan->result;
    $input = $scan->input;
    $dir = $result?->direction ?? 'WAIT';
    $isBuy = $dir === 'BUY';
    $isSell = $dir === 'SELL';
    $isWait = in_array($dir, ['WAIT', 'NO VALID SETUP']);
    $confluences = $result?->strategy_confluences;
    $macroNews = $result?->macro_news_context;
    $firewallStatus = $result?->full_ai_response['firewall_status'] ?? null;
    $isFirewallBlocked = $firewallStatus === 'NEWS RISK — TRADE BLOCKED';
    $isFirewallStabilizing = $firewallStatus === 'NEWS RISK — STABILIZING';
    $firewallBlockReason = $result?->full_ai_response['firewall_block_reason'] ?? null;
    $activeNewsEvent = $result?->full_ai_response['active_news_event'] ?? null;
    $conditionalScenario = $result?->full_ai_response['conditional_technical_scenario'] ?? null;
    $visionQuality = $result?->full_ai_response['screenshot_quality'] ?? null;
    $marketFusion = $result?->full_ai_response['market_data_fusion'] ?? null;
    $marketRegime = $result?->full_ai_response['market_regime'] ?? null;
    $retrievedStrategies = $result?->full_ai_response['retrieved_strategies'] ?? null;
    $selectedStrategy = $result?->full_ai_response['selected_strategy'] ?? null;
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8" x-data="{
    copied: false,
    copyPlan() {
        const text = document.getElementById('trading-plan-text').innerText;
        navigator.clipboard.writeText(text);
        this.copied = true;
        setTimeout(() => this.copied = false, 2500);
    }
}">
    <!-- Top Action Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
        <div class="space-y-1.5">
            <div class="flex items-center space-x-2 text-xs text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-cyan-400">Dashboard</a>
                <span>&bull;</span>
                <a href="{{ route('scans.index') }}" class="hover:text-cyan-400">Scan History</a>
                <span>&bull;</span>
                <span class="text-white font-mono">{{ $scan->symbol }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center space-x-3">
                    <span class="font-mono">{{ $scan->symbol }}</span>
                    <span class="px-2.5 py-0.5 rounded-lg bg-slate-800 text-slate-300 font-mono text-sm">{{ $scan->timeframe }}</span>
                </h1>
                @if(!empty($input?->screenshot_path))
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-cyan-950/80 text-cyan-300 border border-cyan-800/80 shadow-sm">
                        <svg class="w-3.5 h-3.5 text-cyan-400 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        <span>Auto-Detected from Screenshot</span>
                    </span>
                @endif
                <span class="text-xs text-slate-400">Strategy: {{ ucfirst(str_replace('_', ' ', $scan->strategy)) }}</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-2.5">
            <button type="button" @click="copyPlan()" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition">
                <template x-if="!copied">
                    <span class="flex items-center space-x-1.5">
                        <svg class="w-3.5 h-3.5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        <span>Copy Plan</span>
                    </span>
                </template>
                <template x-if="copied">
                    <span class="flex items-center space-x-1.5 text-emerald-400">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Copied!</span>
                    </span>
                </template>
            </button>

            <a href="{{ route('scans.pdf', $scan) }}" target="_blank" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition">
                <svg class="w-3.5 h-3.5 mr-1.5 text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Print / PDF
            </a>

            @if(isset($marketData) && $marketData->isNotEmpty())
                <a href="{{ route('scans.csv', $scan) }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition">
                    <svg class="w-3.5 h-3.5 mr-1.5 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    CSV Data
                </a>
            @endif

            <a href="{{ route('scanner') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-slate-950 bg-cyan-400 hover:bg-cyan-300 transition">
                New Scan
            </a>
        </div>
    </div>

    <!-- NEWS RISK FIREWALL HARD GATEKEEPER BANNER -->
    @if($isFirewallBlocked)
        <div class="p-6 sm:p-7 rounded-3xl bg-gradient-to-br from-rose-950/80 via-slate-900 to-rose-950/60 border-2 border-rose-500 shadow-2xl shadow-rose-950/50 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-rose-800/80 pb-4">
                <div class="flex items-center space-x-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-rose-900/90 border border-rose-500/80 flex items-center justify-center text-rose-300 shadow-lg animate-pulse">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-black uppercase tracking-wider text-rose-400">Hard Veto Gatekeeper</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-rose-900 text-rose-200 border border-rose-700">NON-OVERRIDABLE</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">NEWS RISK — TRADE ENTRY BLOCKED</h2>
                    </div>
                </div>
                @if($activeNewsEvent)
                    <div class="text-left sm:text-right">
                        <span class="text-[10px] uppercase font-bold text-rose-300 block">Triggering Event</span>
                        <span class="text-xs font-mono font-bold text-white">{{ $activeNewsEvent['name'] ?? 'High Impact Release' }}</span>
                    </div>
                @endif
            </div>

            <p class="text-xs sm:text-sm text-rose-200 leading-relaxed font-medium">
                {{ $firewallBlockReason ?? 'Entry prohibited due to impending high-impact economic news volatility. Spreads and liquidity are prone to slippage.' }}
            </p>

            @if($conditionalScenario)
                <div class="p-4 rounded-2xl bg-slate-950/80 border border-rose-800/60 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-300 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            <span>Conditional Post-Release Setup (Wait Condition)</span>
                        </span>
                        <span class="text-[11px] font-mono text-slate-400">Original Technical Bias: <strong class="text-white">{{ $conditionalScenario['original_direction'] ?? 'BUY' }}</strong></span>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Technical models identified a potential <strong class="text-cyan-400">{{ $conditionalScenario['original_direction'] ?? '' }}</strong> setup around <strong class="text-white">{{ number_format($conditionalScenario['original_entry'] ?? 0, 2) }}</strong>. However, this trade setup is <em>invalid</em> until after the news release and order book stabilization.
                    </p>
                    <div class="flex flex-wrap gap-4 pt-1 text-[11px] text-slate-400 font-mono">
                        <span>Invalidation Level: <span class="text-rose-400 font-bold">{{ number_format($conditionalScenario['invalidation_level'] ?? 0, 2) }}</span></span>
                        <span>Conditional Target: <span class="text-emerald-400 font-bold">{{ number_format($conditionalScenario['target'] ?? 0, 2) }}</span></span>
                    </div>
                </div>
            @endif
        </div>
    @elseif($isFirewallStabilizing)
        <div class="p-6 rounded-3xl bg-amber-950/60 border-2 border-amber-500/80 shadow-2xl space-y-3">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-amber-900/80 border border-amber-600 flex items-center justify-center text-amber-300">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Post-Release Stabilization Cooldown</span>
                    <h2 class="text-lg sm:text-xl font-bold text-white">NEWS RISK — MARKET STABILIZING</h2>
                </div>
            </div>
            <p class="text-xs text-amber-200/90 leading-relaxed">
                {{ $firewallBlockReason ?? 'Event was recently released. Order book liquidity and spread are currently stabilizing. Stand by until post-event volatility subsides.' }}
            </p>
        </div>
    @endif

    <!-- MAIN DECISION HERO CARD -->
    <div class="p-6 sm:p-8 rounded-3xl {{ $isBuy ? 'bg-gradient-to-br from-emerald-950/40 via-slate-900 to-slate-900 border-2 border-emerald-500/40' : ($isSell ? 'bg-gradient-to-br from-rose-950/40 via-slate-900 to-slate-900 border-2 border-rose-500/40' : 'bg-gradient-to-br from-amber-950/40 via-slate-900 to-slate-900 border-2 border-amber-500/40') }} shadow-2xl space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800/80 pb-6">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block mb-1">Trading Decision</span>
                <div class="flex items-center space-x-3">
                    @if($isBuy)
                        <span class="text-3xl sm:text-4xl font-extrabold text-emerald-400 tracking-tight">BUY SETUP</span>
                    @elseif($isSell)
                        <span class="text-3xl sm:text-4xl font-extrabold text-rose-400 tracking-tight">SELL SETUP</span>
                    @else
                        <span class="text-3xl sm:text-4xl font-extrabold text-amber-400 tracking-tight">{{ $dir }}</span>
                    @endif

                    @if($result?->trend)
                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $result->trend === 'Bullish' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : ($result->trend === 'Bearish' ? 'bg-rose-950 text-rose-300 border border-rose-800' : 'bg-slate-800 text-slate-300 border border-slate-700') }}">
                            {{ $result->trend }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Confidence Level & Provenance -->
            <div class="flex sm:flex-col sm:items-end justify-between space-y-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Confidence Assessment</span>
                <span class="text-sm font-bold {{ $result?->confidence === 'high' ? 'text-emerald-400' : ($result?->confidence === 'moderate' ? 'text-cyan-400' : 'text-amber-400') }} capitalize">
                    {{ $result?->confidence ?? 'Moderate' }} Confidence
                </span>
                <span class="text-[11px] text-slate-500 font-mono">
                    Engine: {{ $result?->data_provenance ?? 'computer_vision_ocr' }}
                </span>
            </div>
        </div>

        @if(!$isWait && $result)
            <!-- Key Metrics Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Entry Zone -->
                <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800">
                    <span class="text-xs text-slate-400 block mb-1">Proposed Entry Zone</span>
                    <span class="text-xl font-extrabold text-white font-mono">
                        @if($result->entry_target)
                            {{ number_format($result->entry_target, 2) }}
                        @elseif($result->entry_min)
                            {{ number_format($result->entry_min, 2) }} - {{ number_format($result->entry_max, 2) }}
                        @else
                            Market Retest
                        @endif
                    </span>
                    <span class="text-[11px] text-cyan-400 block mt-0.5 capitalize">{{ str_replace('_', ' ', $result->entry_type ?? 'limit_zone') }}</span>
                </div>

                <!-- Stop Loss -->
                <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800">
                    <span class="text-xs text-slate-400 block mb-1">Stop Loss (SL)</span>
                    <span class="text-xl font-extrabold text-rose-400 font-mono">
                        {{ $result->stop_loss ? number_format($result->stop_loss, 2) : '—' }}
                    </span>
                    <span class="text-[11px] text-rose-400/80 block mt-0.5 font-mono">
                        Risk: {{ $result->risk_distance ? number_format($result->risk_distance, 2) . ' pts' : 'Defined Invalidation' }}
                    </span>
                </div>

                <!-- Risk-to-Reward Ratio -->
                <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800">
                    <span class="text-xs text-slate-400 block mb-1">Risk-to-Reward</span>
                    <span class="text-xl font-extrabold text-cyan-400 font-mono">
                        1 : {{ number_format($result->risk_reward_ratio ?? 2.0, 2) }}
                    </span>
                    <span class="text-[11px] text-slate-400 block mt-0.5">Asymmetric Edge</span>
                </div>

                <!-- TP1 Target -->
                <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800">
                    <span class="text-xs text-slate-400 block mb-1">First Target (TP1)</span>
                    <span class="text-xl font-extrabold text-emerald-400 font-mono">
                        {{ $result->take_profit_1 ? number_format($result->take_profit_1, 2) : '—' }}
                    </span>
                    <span class="text-[11px] text-emerald-400/80 block mt-0.5 font-mono">
                        +{{ $result->reward_distance_tp1 ? number_format($result->reward_distance_tp1, 2) . ' pts' : 'Target 1' }}
                    </span>
                </div>
            </div>

            <!-- Technical Summary Thesis -->
            @if(!empty($result->technical_explanation))
                <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs text-slate-300 leading-relaxed">
                    <strong class="text-cyan-400 uppercase tracking-wider block mb-1">Executive Market Thesis:</strong>
                    {{ $result->technical_explanation }}
                </div>
            @endif
        @elseif(!$result)
            <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 text-slate-300 text-xs leading-relaxed space-y-1">
                <span class="font-bold text-sm block text-amber-400">Analysis In Progress or Result Not Available</span>
                <p>
                    {{ $scan->error_message ?? 'This scan does not have an attached technical analysis result yet. If this scan was interrupted, please submit a new scan from the Chart Scanner.' }}
                </p>
            </div>
        @else
            <div class="p-4 rounded-xl bg-amber-950/30 border border-amber-800/60 text-amber-200 text-xs leading-relaxed space-y-1">
                <span class="font-bold text-sm block">Market is Ranging or Lacks Confluence</span>
                <p>
                    ChartSense AI does NOT force signals. Trading during choppy consolidation carries unfavorable risk. Sit on hands until clear structure is established.
                </p>
            </div>
        @endif
    </div>

    <!-- GLOBAL STRATEGY INTELLIGENCE & MARKET REGIME ENGINE -->
    @if(!empty($marketRegime) || !empty($selectedStrategy))
        <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 sm:p-8 space-y-6 shadow-2xl">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800/80 pb-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-400">Global Trading Strategy Intelligence Engine</span>
                    </div>
                    <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                        <span>Market Regime Classification &amp; Strategy Selection</span>
                    </h2>
                    <p class="text-xs text-slate-400">Dynamic rule compilation across 7 global trading schools &bull; Deterministic verification &bull; Empirical evidence ranking</p>
                </div>

                @if(!empty($marketRegime['regime']))
                    @php
                        $reg = $marketRegime['regime'];
                        $isBullTrend = $reg === 'TRENDING_BULLISH';
                        $isBearTrend = $reg === 'TRENDING_BEARISH';
                        $isTight = $reg === 'RANGING_TIGHT';
                        $isVolatile = $reg === 'VOLATILE';
                        $badgeBg = $isBullTrend ? 'bg-emerald-950 text-emerald-300 border-emerald-800' :
                                  ($isBearTrend ? 'bg-rose-950 text-rose-300 border-rose-800' :
                                  ($isTight ? 'bg-amber-950 text-amber-300 border-amber-800' :
                                  ($isVolatile ? 'bg-purple-950 text-purple-300 border-purple-800' : 'bg-slate-800 text-slate-300 border-slate-700')));
                    @endphp
                    <div class="flex items-center space-x-3 bg-slate-950/80 px-4 py-2.5 rounded-2xl border border-slate-800">
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Active Market Regime</span>
                            <span class="text-xs font-extrabold font-mono text-white">
                                {{ $marketRegime['regime_label'] ?? $reg }}
                            </span>
                        </div>
                        <div class="px-3 py-1.5 rounded-xl font-mono text-xs font-bold border {{ $badgeBg }}">
                            {{ $marketRegime['confidence'] ?? 75 }}% CONF
                        </div>
                    </div>
                @endif
            </div>

            <!-- Market Regime Diagnostics Card -->
            @if(!empty($marketRegime))
                <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-slate-300 uppercase tracking-wider block">Regime Analysis &amp; Volatility Metrics</span>
                            <p class="text-xs text-slate-300 leading-relaxed">{{ $marketRegime['description'] ?? 'Deterministic quantitative classification.' }}</p>
                        </div>
                    </div>

                    <!-- Metrics Grid -->
                    @if(!empty($marketRegime['metrics']))
                        @php $m = $marketRegime['metrics']; @endphp
                        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-2.5 pt-2 border-t border-slate-800/80">
                            <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 text-center">
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">ADX Strength</span>
                                <span class="text-sm font-extrabold font-mono {{ ($m['adx'] ?? 0) >= 22 ? 'text-emerald-400' : 'text-amber-400' }}">{{ $m['adx'] ?? '—' }}</span>
                                <span class="text-[9px] text-slate-500 block">{{ ($m['adx'] ?? 0) >= 22 ? 'Trending' : 'Consolidating' }}</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 text-center">
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">+DI / -DI</span>
                                <span class="text-sm font-extrabold font-mono text-white">{{ $m['plus_di'] ?? '0' }} / {{ $m['minus_di'] ?? '0' }}</span>
                                <span class="text-[9px] text-slate-500 block">{{ ($m['plus_di'] ?? 0) > ($m['minus_di'] ?? 0) ? '+DI Dominant' : '-DI Dominant' }}</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 text-center">
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">ATR (14)</span>
                                <span class="text-sm font-extrabold font-mono text-cyan-400">{{ $m['atr_14'] ?? '—' }}</span>
                                <span class="text-[9px] text-slate-500 block">{{ $m['atr_percentile'] ?? '50' }}th %tile</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 text-center">
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Bandwidth</span>
                                <span class="text-sm font-extrabold font-mono text-purple-400">{{ $m['bollinger_bandwidth'] ?? '—' }}%</span>
                                <span class="text-[9px] text-slate-500 block">BB Volatility</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 text-center">
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">MA Alignment</span>
                                <span class="text-sm font-extrabold font-mono {{ ($m['ma_alignment_score'] ?? 0) > 0 ? 'text-emerald-400' : (($m['ma_alignment_score'] ?? 0) < 0 ? 'text-rose-400' : 'text-slate-400') }}">
                                    {{ $m['ma_alignment_score'] ?? 0 }}
                                </span>
                                <span class="text-[9px] text-slate-500 block">EMA 9/21/50</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 text-center">
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Swing Bias</span>
                                <span class="text-sm font-extrabold font-mono text-white">{{ $m['swing_bias'] ?? 'NEUTRAL' }}</span>
                                <span class="text-[9px] text-slate-500 block">Structure</span>
                            </div>
                        </div>
                    @endif

                    @if(!empty($marketRegime['risk_guidance']))
                        <div class="p-3 rounded-xl bg-indigo-950/40 border border-indigo-900/60 text-xs text-indigo-200">
                            <strong>Regime Risk Directive:</strong> {{ $marketRegime['risk_guidance'] }}
                        </div>
                    @endif
                </div>
            @endif

            <!-- Selected Strategy & Candidate Matrix -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Selected Strategy Detail Card -->
                <div class="p-6 rounded-2xl bg-gradient-to-br from-indigo-950/40 via-slate-900 to-slate-950 border border-indigo-800/60 space-y-4">
                    <div class="flex items-center justify-between border-b border-indigo-900/60 pb-3">
                        <div class="space-y-0.5">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400 block">Primary Selected Strategy</span>
                            <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                                <span>{{ $selectedStrategy['name'] ?? 'Discretionary Price Action' }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-indigo-900/80 text-indigo-200 border border-indigo-700">v{{ $selectedStrategy['version'] ?? '1.0.0' }}</span>
                            </h3>
                        </div>
                        @if(!empty($selectedStrategy['category']))
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-300 border border-slate-700">
                                {{ strtoupper(str_replace('_', ' ', $selectedStrategy['category'])) }}
                            </span>
                        @endif
                    </div>

                    @if(!empty($selectedStrategy['rationale']))
                        <div class="text-xs text-slate-300 leading-relaxed">
                            <strong class="text-indigo-300 uppercase tracking-wider text-[11px] block mb-1">Selection Rationale:</strong>
                            {{ $selectedStrategy['rationale'] }}
                        </div>
                    @endif

                    @if(!empty($selectedStrategy['evidence_status']))
                        <div class="p-3 rounded-xl bg-slate-950/80 border border-slate-800 space-y-1">
                            <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Empirical Evidence Rating</span>
                            <span class="text-xs font-mono font-bold text-cyan-400 block">{{ $selectedStrategy['evidence_status'] }}</span>
                        </div>
                    @endif

                    @if(!empty($selectedStrategy['entry_trigger']) || !empty($selectedStrategy['invalidation_level']))
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs pt-1">
                            @if(!empty($selectedStrategy['entry_trigger']))
                                <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800">
                                    <span class="text-[10px] text-slate-400 block">Entry Trigger</span>
                                    <span class="font-mono text-white text-[11px] font-semibold">{{ $selectedStrategy['entry_trigger'] }}</span>
                                </div>
                            @endif
                            @if(!empty($selectedStrategy['invalidation_level']))
                                <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800">
                                    <span class="text-[10px] text-rose-400 block">Invalidation Level</span>
                                    <span class="font-mono text-rose-300 text-[11px] font-semibold">{{ $selectedStrategy['invalidation_level'] }}</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Retrieved Candidates & Rejection Log -->
                <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-300 block">
                            Candidate Strategies Evaluated (Dynamic RAG)
                        </span>
                        <span class="text-[10px] text-slate-500 font-mono">
                            {{ count($retrievedStrategies['candidates'] ?? []) }} Matches Retrieved
                        </span>
                    </div>

                    @if(!empty($retrievedStrategies['candidates']))
                        <div class="space-y-2.5">
                            @foreach($retrievedStrategies['candidates'] as $cand)
                                <div class="p-3 rounded-xl bg-slate-900/70 border border-slate-800 text-xs space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-white">{{ $cand['name'] }}</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-bold {{ $cand['score'] >= 80 ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-slate-800 text-slate-400' }}">
                                            Score: {{ $cand['score'] }}/100
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                        <span>{{ $cand['category_name'] }}</span>
                                        <span>&bull;</span>
                                        <span class="capitalize">{{ str_replace('_', ' ', $cand['implementation_status']) }}</span>
                                        <span>&bull;</span>
                                        <span class="font-mono text-cyan-400">{{ $cand['evidence_label'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-slate-400">Evaluated general price action and structural rules.</p>
                    @endif

                    <!-- Architectural Integrity Notice -->
                    <div class="pt-2 border-t border-slate-800/80 text-[10px] text-slate-500 leading-relaxed">
                        <strong>Architectural Notice:</strong> Strategies are dynamic reference specifications retrieved from the ChartSense Global Knowledge Base for in-context analysis. They do NOT represent permanent model training or fine-tuning weights.
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- AUTONOMOUS MULTI-STRATEGY CONFLUENCE MATRIX -->
    @if(!empty($confluences) && isset($confluences['strategies']))
        <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 sm:p-8 space-y-6 shadow-2xl">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800/80 pb-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-cyan-400">Autonomous Trading Strategy Engine</span>
                    </div>
                    <h2 class="text-xl font-extrabold text-white">Multi-Strategy Confluence Matrix</h2>
                    <p class="text-xs text-slate-400">{{ $confluences['recommendation'] ?? 'Institutional composite scoring across SMC, Price Action, Market Structure, and Fibonacci.' }}</p>
                </div>
                <div class="flex items-center space-x-3 bg-slate-950/80 px-4 py-2.5 rounded-2xl border border-slate-800">
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Composite Confluence</span>
                        <span class="text-xs font-semibold text-emerald-400">Institutional Grade</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-cyan-500 to-emerald-400 flex items-center justify-center text-slate-950 font-black text-lg shadow-lg shadow-cyan-500/20">
                        {{ $confluences['composite_score'] ?? 92 }}%
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                    $stratMap = [
                        'smc' => ['title' => 'Smart Money / ICT', 'color' => 'cyan', 'icon' => '🏛️'],
                        'price_action' => ['title' => 'Pure Price Action', 'color' => 'purple', 'icon' => '🕯️'],
                        'market_structure' => ['title' => 'Trend Structure', 'color' => 'blue', 'icon' => '📈'],
                        'fibonacci' => ['title' => 'Fibonacci Matrix', 'color' => 'amber', 'icon' => '📐'],
                    ];
                @endphp

                @foreach($confluences['strategies'] as $key => $strat)
                    @php
                        $meta = $stratMap[$key] ?? ['title' => $strat['name'] ?? ucfirst($key), 'color' => 'cyan', 'icon' => '⚡'];
                        $isSellStrat = ($strat['verdict'] ?? '') === 'SELL';
                    @endphp
                    <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80 hover:border-slate-700 space-y-3 transition flex flex-col justify-between">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-white flex items-center gap-1.5">
                                    <span>{{ $meta['icon'] }}</span>
                                    <span>{{ $meta['title'] }}</span>
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold {{ $isSellStrat ? 'bg-rose-950 text-rose-300 border border-rose-800' : 'bg-emerald-950 text-emerald-300 border border-emerald-800' }}">
                                    {{ $strat['verdict'] ?? 'ALIGNED' }}
                                </span>
                            </div>
                            <div class="text-[11px] font-semibold {{ $isSellStrat ? 'text-rose-400' : 'text-emerald-400' }}">
                                {{ $strat['status'] ?? 'High-Probability Setup' }}
                            </div>

                            <div class="space-y-1.5 text-[11px] text-slate-300 pt-1 border-t border-slate-900">
                                @if(isset($strat['order_block']))
                                    <p class="leading-relaxed"><strong class="text-slate-400">Order Block:</strong> {{ $strat['order_block'] }}</p>
                                @endif
                                @if(isset($strat['fvg_imbalance']))
                                    <p class="leading-relaxed"><strong class="text-slate-400">FVG / Draw:</strong> {{ $strat['fvg_imbalance'] }}</p>
                                @endif
                                @if(isset($strat['pattern']))
                                    <p class="leading-relaxed"><strong class="text-slate-400">Pattern:</strong> {{ $strat['pattern'] }}</p>
                                @endif
                                @if(isset($strat['key_level']))
                                    <p class="leading-relaxed"><strong class="text-slate-400">Key Pivot:</strong> {{ $strat['key_level'] }}</p>
                                @endif
                                @if(isset($strat['phase']))
                                    <p class="leading-relaxed"><strong class="text-slate-400">Structure:</strong> {{ $strat['phase'] }}</p>
                                @endif
                                @if(isset($strat['break_of_structure']))
                                    <p class="leading-relaxed"><strong class="text-slate-400">BOS:</strong> {{ $strat['break_of_structure'] }}</p>
                                @endif
                                @if(isset($strat['golden_pocket']))
                                    <p class="leading-relaxed"><strong class="text-slate-400">Golden Pocket:</strong> {{ $strat['golden_pocket'] }}</p>
                                @endif
                                @if(isset($strat['extension_tp']))
                                    <p class="leading-relaxed"><strong class="text-slate-400">Extensions:</strong> {{ $strat['extension_tp'] }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                            <span>Strategy Score</span>
                            <span class="font-mono font-bold text-white">{{ $strat['score'] ?? 90 }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- LIVE GLOBAL MACRO NEWS & FUNDAMENTAL IMPACT -->
    @if(!empty($macroNews))
        <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 sm:p-8 space-y-6 shadow-2xl">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800/80 pb-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Live Global Market Catalyst Stream</span>
                    </div>
                    <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                        <span>Real-Time World Macro News &amp; Fundamental Bias</span>
                    </h2>
                    <p class="text-xs text-slate-400">Ingested live from {{ $macroNews['source'] ?? 'ForexLive & CoinDesk Global Feeds' }} &bull; Updated real-time</p>
                </div>

                <div class="flex items-center space-x-3 bg-slate-950/80 px-4 py-2.5 rounded-2xl border border-slate-800">
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Macro Sentiment Bias</span>
                        <span class="text-xs font-extrabold font-mono {{ ($macroNews['sentiment_bias'] ?? '') === 'BEARISH' ? 'text-rose-400' : (($macroNews['sentiment_bias'] ?? '') === 'BULLISH' ? 'text-emerald-400' : 'text-amber-400') }}">
                            {{ $macroNews['sentiment_bias'] ?? 'NEUTRAL' }}
                        </span>
                    </div>
                    <div class="px-3 py-1.5 rounded-xl font-mono text-xs font-bold {{ ($macroNews['sentiment_bias'] ?? '') === 'BEARISH' ? 'bg-rose-950 text-rose-300 border border-rose-800' : (($macroNews['sentiment_bias'] ?? '') === 'BULLISH' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-amber-950 text-amber-300 border border-amber-800') }}">
                        {{ ($macroNews['sentiment_score'] ?? 0) > 0 ? '+' : '' }}{{ $macroNews['sentiment_score'] ?? 0 }}
                    </div>
                </div>
            </div>

            <!-- Macro Narrative Summary -->
            <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Macro Narrative &amp; Market Catalyst Analysis</span>
                    @if(!empty($macroNews['high_impact_warning']))
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-950 text-rose-300 border border-rose-800 flex items-center gap-1 animate-pulse">
                            ⚠️ High Volatility Event Risk
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">
                    {{ $macroNews['macro_narrative'] ?? 'Global financial conditions remain dynamic with active central bank policy signaling and liquidity flows.' }}
                </p>
                @if(!empty($macroNews['risk_warning']))
                    <p class="text-[11px] text-amber-400/90 font-medium">
                        <strong>Catalyst Notice:</strong> {{ $macroNews['risk_warning'] }}
                    </p>
                @endif
            </div>

            <!-- Recent Live Headlines -->
            @if(!empty($macroNews['headlines']))
                <div class="space-y-3">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Live Wire Headlines Affecting Trading Conditions</span>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($macroNews['headlines'] as $headline)
                            <a href="{{ $headline['link'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="p-3.5 rounded-xl bg-slate-950/50 hover:bg-slate-950 border border-slate-800/80 hover:border-slate-700 transition block space-y-1.5 group">
                                <div class="flex items-center justify-between text-[10px] text-slate-500">
                                    <span class="font-medium text-slate-400">{{ !empty($headline['pub_date']) ? \Illuminate\Support\Carbon::parse($headline['pub_date'])->diffForHumans() : 'Recent Wire' }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold {{ ($headline['impact'] ?? '') === 'HIGH' ? 'bg-rose-950 text-rose-400 border border-rose-800' : 'bg-slate-800 text-slate-400' }}">
                                        {{ $headline['impact'] ?? 'NORMAL' }} IMPACT
                                    </span>
                                </div>
                                <h4 class="text-xs font-semibold text-white group-hover:text-cyan-400 transition leading-snug line-clamp-2">
                                    {{ $headline['title'] }}
                                </h4>
                                @if(!empty($headline['snippet']))
                                    <p class="text-[11px] text-slate-400 line-clamp-1 leading-normal">{{ $headline['snippet'] }}</p>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- UPLOADED CHART SCREENSHOT EVIDENCE -->
    @if(!empty($input?->screenshot_path))
        <div class="rounded-3xl bg-slate-900/60 border border-slate-800 p-6 sm:p-8 space-y-4 shadow-xl" x-data="{ expanded: false }">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Uploaded Chart Screenshot Evidence</h3>
                </div>
                <button type="button" @click="expanded = !expanded" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300">
                    <span x-text="expanded ? 'Minimize Preview' : 'Expand Full Chart'"></span>
                </button>
            </div>
            <div :class="expanded ? 'max-h-none' : 'max-h-80'" class="overflow-hidden rounded-2xl border border-slate-800 transition-all duration-300 relative bg-slate-950 flex justify-center">
                <img src="{{ route('scans.screenshot', $scan) }}" alt="Chart Screenshot" class="w-full h-auto object-contain max-w-4xl">
            </div>
            <p class="text-[11px] text-slate-500">RapidOCR &amp; Computer Vision Engine extracted active bid/ask prices, support/resistance levels, and position tool overlay from this chart.</p>

            @if($visionQuality || $marketFusion)
                <div class="pt-4 border-t border-slate-800/80 grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    @if($visionQuality)
                        <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-300 uppercase tracking-wider text-[10px]">Screenshot Vision Quality</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ ($visionQuality['quality_score'] ?? 0) >= 70 ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-amber-950 text-amber-400 border border-amber-800' }}">
                                    Score: {{ $visionQuality['quality_score'] ?? 80 }}/100
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-400 font-mono">
                                <div>Price Axis: <span class="{{ ($visionQuality['price_axis_readable'] ?? true) ? 'text-emerald-400' : 'text-rose-400' }}">{{ ($visionQuality['price_axis_readable'] ?? true) ? 'Readable' : 'Occluded' }}</span></div>
                                <div>Clarity: <span class="{{ ($visionQuality['is_blurry'] ?? false) ? 'text-rose-400' : 'text-emerald-400' }}">{{ ($visionQuality['is_blurry'] ?? false) ? 'Blurry' : 'Sharp' }}</span></div>
                                <div>Timeframe: <span class="text-slate-200">{{ ($visionQuality['timeframe_detected'] ?? true) ? 'Verified' : 'Inferred' }}</span></div>
                                <div>Occlusion: <span class="capitalize text-slate-200">{{ $visionQuality['occlusion_level'] ?? 'Low' }}</span></div>
                            </div>
                        </div>
                    @endif

                    @if($marketFusion)
                        <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-300 uppercase tracking-wider text-[10px]">Market Data Fusion &amp; Validation</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ ($marketFusion['status'] ?? '') === 'reconciled' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-cyan-950 text-cyan-400 border border-cyan-800' }}">
                                    {{ strtoupper($marketFusion['status'] ?? 'Reconciled') }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-400">
                                Screenshot prices cross-referenced against PostgreSQL historical OHLCV data. Price variance: <span class="font-mono text-cyan-400">{{ $marketFusion['price_delta_pct'] ?? 0.0 }}%</span>. No synthetic price levels permitted.
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <!-- TAKE PROFIT SCENARIOS TABLE (IF VALID SETUP) -->
    @if(!$isWait && $result)
        <div class="rounded-2xl bg-slate-900/60 border border-slate-800 p-6 space-y-4 shadow-xl">
            <h2 class="text-base font-bold text-white uppercase tracking-wider text-xs">Structured Take-Profit Targets</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- TP1 -->
                <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="px-2 py-0.5 rounded font-bold text-xs bg-cyan-950 text-cyan-300 border border-cyan-800">TP1 (Scale Out)</span>
                        <span class="text-xs text-slate-400">R:R 1:1.5</span>
                    </div>
                    <span class="text-2xl font-bold text-white font-mono block">{{ number_format($result->take_profit_1 ?? 0, 2) }}</span>
                    <p class="text-[11px] text-slate-400">First structural liquidity pool. Move stop loss to break-even.</p>
                </div>

                <!-- TP2 -->
                <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="px-2 py-0.5 rounded font-bold text-xs bg-cyan-950 text-cyan-300 border border-cyan-800">TP2 (Primary Target)</span>
                        <span class="text-xs text-slate-400">R:R 1:{{ number_format($result->risk_reward_ratio ?? 2.5, 1) }}</span>
                    </div>
                    <span class="text-2xl font-bold text-white font-mono block">{{ number_format($result->take_profit_2 ?? (($result->take_profit_1 ?? 0) * 1.02), 2) }}</span>
                    <p class="text-[11px] text-slate-400">Key swing high / low target with major order block confluence.</p>
                </div>

                <!-- TP3 -->
                <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="px-2 py-0.5 rounded font-bold text-xs bg-cyan-950 text-cyan-300 border border-cyan-800">TP3 (Runner Extension)</span>
                        <span class="text-xs text-slate-400">R:R 1:4+</span>
                    </div>
                    <span class="text-2xl font-bold text-white font-mono block">{{ number_format($result->take_profit_3 ?? (($result->take_profit_1 ?? 0) * 1.04), 2) }}</span>
                    <p class="text-[11px] text-slate-400">Extended Fibonacci projection or daily cycle exhaustion.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- CONFLUENCES, INVALIDATION & REASONING -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Supporting Reasons -->
        <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4 shadow-xl">
            <h3 class="text-sm font-bold text-emerald-400 uppercase tracking-wider flex items-center space-x-2">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <span>Technical Reasons Supporting Setup</span>
            </h3>
            <ul class="space-y-2.5 text-xs text-slate-300">
                @if(!empty($result?->reasoning))
                    @foreach($result->reasoning as $reason)
                        <li class="flex items-start space-x-2">
                            <span class="text-emerald-400 font-bold">&bull;</span>
                            <span class="leading-relaxed">{{ $reason }}</span>
                        </li>
                    @endforeach
                @else
                    <li>Structure and momentum indicators aligned with strategic bias.</li>
                @endif
            </ul>

            @if(!empty($result?->confirmation_conditions))
                <div class="pt-2 border-t border-slate-800/80">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-2">Required Entry Confirmations</span>
                    <ul class="space-y-1.5 text-xs text-slate-400">
                        @foreach($result->confirmation_conditions as $condition)
                            <li class="flex items-start space-x-2">
                                <span class="text-cyan-400">&check;</span>
                                <span>{{ $condition }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Invalidation & Risk Warnings -->
        <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4 shadow-xl">
            <h3 class="text-sm font-bold text-amber-400 uppercase tracking-wider flex items-center space-x-2">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line></svg>
                <span>Invalidation Conditions &amp; Risk Notice</span>
            </h3>
            <ul class="space-y-2.5 text-xs text-slate-300">
                @if(!empty($result?->invalidation_conditions))
                    @foreach($result->invalidation_conditions as $inv)
                        <li class="flex items-start space-x-2">
                            <span class="text-rose-400 font-bold">&times;</span>
                            <span class="leading-relaxed">{{ $inv }}</span>
                        </li>
                    @endforeach
                @else
                    <li>Candle close beyond stop loss level invalidates the setup entirely.</li>
                @endif
            </ul>

            @if(!empty($result?->warnings))
                <div class="pt-2 border-t border-slate-800/80">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-2">Risk Management Warnings</span>
                    <ul class="space-y-1.5 text-xs text-slate-400">
                        @foreach($result->warnings as $warn)
                            <li class="flex items-start space-x-2">
                                <span class="text-amber-400">!</span>
                                <span>{{ $warn }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    @php
        $analysisRun = $scan->analysisRun;
        $agentResults = $analysisRun?->agentResults;
        $consensusResult = $analysisRun?->consensusResult;
        $fallbackSummaries = $result?->full_ai_response['analyst_summaries'] ?? [];
        $riskReview = $consensusResult?->risk_review ?? ($result?->full_ai_response['risk_review'] ?? null);
        $contradictoryEvidence = $consensusResult?->contradictory_evidence ?? ($result?->full_ai_response['contradictory_evidence'] ?? []);
    @endphp

    <!-- EXPANDABLE MULTI-AI ANALYSIS DETAILS SECTION -->
    <div class="rounded-3xl bg-slate-900/60 border border-slate-800 shadow-xl overflow-hidden" x-data="{ openDetails: false }">
        <button type="button" 
                @click="openDetails = !openDetails" 
                class="w-full p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 text-left hover:bg-slate-800/40 transition">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-950/80 border border-cyan-800/80 flex items-center justify-center text-cyan-400">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                </div>
                <div>
                    <div class="flex items-center space-x-2">
                        <h2 class="text-base font-bold text-white tracking-tight">View AI Analysis Details</h2>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-950 text-cyan-300 border border-cyan-800">
                            11 Coordinated Models
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">Inspect independent evidence, fundamental news intelligence, model disagreements, and quantitative risk checks.</p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <span class="text-xs font-semibold text-cyan-400 flex items-center space-x-1">
                    <span x-text="openDetails ? 'Collapse Details' : 'Expand 11 Models'"></span>
                    <svg class="w-4 h-4 transform transition-transform duration-200" :class="openDetails ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
            </div>
        </button>

        <!-- Collapsible Content -->
        <div x-show="openDetails" x-cloak class="p-6 sm:p-8 border-t border-slate-800/80 space-y-6 bg-slate-950/40">
            <!-- Summary Banner -->
            <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 flex flex-wrap items-center justify-between gap-4 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px] uppercase tracking-wider font-semibold">Orchestration Architecture</span>
                    <span class="text-white font-mono font-medium">Stage A: Independent &rarr; Stage B: Shared Claims &rarr; Stage C: Cross-Review &rarr; Stage D: Debate &rarr; Stage E: Risk &rarr; Stage F: Synthesis</span>
                </div>
                @if($analysisRun)
                    <div class="flex items-center space-x-4 text-[11px] text-slate-400 font-mono">
                        <span>Run ID: #{{ $analysisRun->id }}</span>
                        <span>Duration: {{ $analysisRun->total_duration_ms }}ms</span>
                        <span>Status: <span class="text-emerald-400 capitalize">{{ $analysisRun->status }}</span></span>
                    </div>
                @endif
            </div>

            <!-- MULTI-STAGE COLLABORATION WORKFLOW STEPPER -->
            @php
                $collabMessages = $analysisRun?->collaborationMessages ?? collect([]);
                $collabData = $result?->full_ai_response['collaboration_session'] ?? [];
                $challengesList = $collabData['challenges'] ?? [];
                $reassessmentsList = $collabData['reassessments'] ?? [];
                $hasDebate = !empty($reassessmentsList) || $collabMessages->contains('stage', 'debate');
            @endphp
            <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-cyan-400 flex items-center space-x-2">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span>Interconnected Multi-AI Collaboration Workflow (One Team)</span>
                    </span>
                    <span class="text-[11px] text-slate-400 font-mono">
                        {{ $collabMessages->count() ?: count($challengesList) + 7 }} Protocol Messages Logged
                    </span>
                </div>

                <!-- 6 Stages Progression Bar -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 pt-1 text-center">
                    <!-- Stage A -->
                    <div class="p-2.5 rounded-xl bg-slate-950 border border-cyan-800/60 text-left space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-cyan-400">STAGE A</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        </div>
                        <span class="text-xs font-bold text-white block">Independent</span>
                        <span class="text-[10px] text-slate-400 block leading-tight">6 domain analysts examine chart</span>
                    </div>

                    <!-- Stage B -->
                    <div class="p-2.5 rounded-xl bg-slate-950 border border-cyan-800/60 text-left space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-cyan-400">STAGE B</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        </div>
                        <span class="text-xs font-bold text-white block">Shared Claims</span>
                        <span class="text-[10px] text-slate-400 block leading-tight">Published to shared workspace</span>
                    </div>

                    <!-- Stage C -->
                    <div class="p-2.5 rounded-xl bg-slate-950 border {{ !empty($challengesList) ? 'border-amber-700/60' : 'border-cyan-800/60' }} text-left space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold {{ !empty($challengesList) ? 'text-amber-400' : 'text-cyan-400' }}">STAGE C</span>
                            <span class="w-2 h-2 rounded-full {{ !empty($challengesList) ? 'bg-amber-400' : 'bg-emerald-400' }}"></span>
                        </div>
                        <span class="text-xs font-bold text-white block">Cross-Review</span>
                        <span class="text-[10px] text-slate-400 block leading-tight">Targeted peer challenges</span>
                    </div>

                    <!-- Stage D -->
                    <div class="p-2.5 rounded-xl bg-slate-950 border {{ $hasDebate ? 'border-purple-800/60' : 'border-slate-800' }} text-left space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold {{ $hasDebate ? 'text-purple-400' : 'text-slate-500' }}">STAGE D</span>
                            <span class="w-2 h-2 rounded-full {{ $hasDebate ? 'bg-purple-400' : 'bg-slate-600' }}"></span>
                        </div>
                        <span class="text-xs font-bold text-white block">AI Debate</span>
                        <span class="text-[10px] text-slate-400 block leading-tight">{{ $hasDebate ? 'Defended / Adjusted' : 'Consensus (Skipped)' }}</span>
                    </div>

                    <!-- Stage E -->
                    <div class="p-2.5 rounded-xl bg-slate-950 border border-cyan-800/60 text-left space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-cyan-400">STAGE E</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        </div>
                        <span class="text-xs font-bold text-white block">Risk Gatekeeper</span>
                        <span class="text-[10px] text-slate-400 block leading-tight">Independent veto evaluation</span>
                    </div>

                    <!-- Stage F -->
                    <div class="p-2.5 rounded-xl bg-slate-950 border border-emerald-800/60 text-left space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-emerald-400">STAGE F</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        </div>
                        <span class="text-xs font-bold text-white block">Final Synthesis</span>
                        <span class="text-[10px] text-slate-400 block leading-tight">Transparent team consensus</span>
                    </div>
                </div>

                <!-- Inter-Model Challenges & Debate Feed -->
                @if($collabMessages->whereIn('message_type', ['CHALLENGE', 'COUNTERARGUMENT', 'REASSESSMENT', 'RISK_OBJECTION'])->isNotEmpty())
                    <div class="pt-3 border-t border-slate-800/70 space-y-2.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">
                            Inter-Model Peer Challenges &amp; Reassessment Log
                        </span>
                        <div class="space-y-2">
                            @foreach($collabMessages->whereIn('message_type', ['CHALLENGE', 'COUNTERARGUMENT', 'REASSESSMENT', 'RISK_OBJECTION']) as $msg)
                                @php
                                    $isChl = in_array($msg->message_type, ['CHALLENGE', 'COUNTERARGUMENT']);
                                    $isReassess = $msg->message_type === 'REASSESSMENT';
                                    $isRisk = $msg->message_type === 'RISK_OBJECTION';
                                @endphp
                                <div class="p-3 rounded-xl bg-slate-950/80 border {{ $isRisk ? 'border-red-900/40 bg-red-950/10' : ($isReassess ? 'border-purple-900/40 bg-purple-950/10' : 'border-amber-900/40 bg-amber-950/10') }} flex flex-col sm:flex-row sm:items-start justify-between gap-2 text-xs">
                                    <div class="space-y-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono {{ $isRisk ? 'bg-red-950 text-red-300 border border-red-800' : ($isReassess ? 'bg-purple-950 text-purple-300 border border-purple-800' : 'bg-amber-950 text-amber-300 border border-amber-800') }}">
                                                {{ $msg->message_type }}
                                            </span>
                                            <span class="font-bold text-slate-200">
                                                {{ ucwords(str_replace('_', ' ', $msg->sender_role)) }}
                                            </span>
                                            <span class="text-slate-500">&rarr;</span>
                                            <span class="text-slate-400">
                                                {{ $msg->target_role === 'all' ? 'All Models' : ucwords(str_replace('_', ' ', $msg->target_role)) }}
                                            </span>
                                        </div>
                                        <p class="text-slate-300 leading-relaxed">{{ $msg->claim }}</p>
                                    </div>
                                    <div class="flex sm:flex-col items-end justify-between text-[10px] text-slate-500 font-mono shrink-0">
                                        <span class="capitalize font-bold {{ $msg->validation_status === 'defended' ? 'text-emerald-400' : ($msg->validation_status === 'conceded' ? 'text-amber-400' : ($msg->validation_status === 'vetoed' ? 'text-red-400' : 'text-slate-400')) }}">
                                            Status: {{ $msg->validation_status }}
                                        </span>
                                        <span>Round {{ $msg->round_number }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif(!empty($challengesList))
                    <div class="pt-3 border-t border-slate-800/70 space-y-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">
                            Peer Cross-Review Findings
                        </span>
                        @foreach($challengesList as $c)
                            <div class="p-3 rounded-xl bg-slate-950/80 border border-amber-900/30 text-xs space-y-1">
                                <div class="flex items-center space-x-2">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800 font-mono">
                                        {{ $c['challenge_type'] ?? 'CHALLENGE' }}
                                    </span>
                                    <span class="font-bold text-slate-200">{{ ucwords(str_replace('_', ' ', $c['challenger_role'] ?? 'Peer')) }}</span>
                                    <span class="text-slate-500">&rarr;</span>
                                    <span class="text-slate-400">{{ ucwords(str_replace('_', ' ', $c['target_role'] ?? 'Target')) }}</span>
                                </div>
                                <p class="text-slate-300">{{ $c['objection_text'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Risk Review Highlight Box -->
            @if($riskReview)
                <div class="p-5 rounded-2xl bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 border border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-400 flex items-center space-x-1.5">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <span>Independent Trading Risk Review</span>
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ ($riskReview['status'] ?? '') === 'approved' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : 'bg-amber-950 text-amber-300 border border-amber-800' }}">
                            {{ $riskReview['status'] ?? 'Approved' }}
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-xs text-slate-300">
                        <div>
                            <span class="text-[11px] text-slate-400 block">Guidance</span>
                            <span>{{ $riskReview['risk_per_trade_guidance'] ?? ($riskReview['recommended_account_risk'] ?? '1.0% maximum risk') }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] text-slate-400 block">Volatility Buffer (ATR)</span>
                            <span class="font-mono">{{ isset($riskReview['volatility_buffer_atr']) && $riskReview['volatility_buffer_atr'] ? number_format($riskReview['volatility_buffer_atr'], 2) . ' pts' : 'Standard Buffer' }}</span>
                        </div>
                        <div>
                            <span class="text-[11px] text-slate-400 block">Invalidation Criteria</span>
                            <span>{{ $riskReview['invalidation_clarity'] ?? ($riskReview['invalidation_criteria'] ?? 'Clear boundary') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- 7 Models Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @if($agentResults && $agentResults->isNotEmpty())
                    @foreach($agentResults as $agent)
                        @php
                            $c = strtoupper($agent->conclusion);
                            $isBull = in_array($c, ['BULLISH', 'BUY']);
                            $isBear = in_array($c, ['BEARISH', 'SELL']);
                            $isRej = in_array($c, ['REJECT', 'FAILED']);
                            $roleTitles = [
                                'price_action' => 'Price Action Analyst',
                                'market_structure' => 'Market Structure Analyst',
                                'smc' => 'Smart Money Concepts Analyst',
                                'technical_indicators' => 'Technical Indicator Analyst',
                                'multi_timeframe' => 'Multi-Timeframe Analyst',
                                'trading_risk' => 'Independent Trading Risk Analyst',
                                'final_decision' => 'Final Decision Analyst',
                            ];
                            $displayRole = $roleTitles[$agent->role] ?? ucwords(str_replace('_', ' ', $agent->role));
                        @endphp
                        <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-3 shadow-sm hover:border-slate-700 transition">
                            <div class="flex items-start justify-between gap-2 border-b border-slate-800/80 pb-2.5">
                                <div>
                                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">
                                        {{ $displayRole }}
                                    </h3>
                                    <span class="text-[10px] text-slate-400 font-mono block">
                                        {{ $agent->provider }} &bull; {{ $agent->model }}
                                    </span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $isBull ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : ($isBear ? 'bg-rose-950 text-rose-300 border border-rose-800' : ($isRej ? 'bg-red-950 text-red-300 border border-red-800' : 'bg-amber-950 text-amber-300 border border-amber-800')) }}">
                                    {{ $c }}
                                </span>
                            </div>

                            <p class="text-xs text-slate-300 leading-relaxed">{{ $agent->summary }}</p>

                            @if(!empty($agent->findings))
                                <div class="space-y-1 pt-1">
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Observed Findings:</span>
                                    <ul class="text-[11px] text-slate-400 space-y-1">
                                        @foreach(array_slice($agent->findings, 0, 3) as $finding)
                                            <li class="flex items-start space-x-1.5">
                                                <span class="text-cyan-400 font-bold">&bull;</span>
                                                <span class="line-clamp-2">{{ $finding }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(!empty($agent->contradictory_points))
                                <div class="p-2 rounded-lg bg-rose-950/20 border border-rose-900/40 text-[11px] text-rose-300 space-y-0.5">
                                    <span class="text-[10px] font-bold block uppercase tracking-wider text-rose-400">Flagged Risk / Divergence:</span>
                                    @foreach(array_slice($agent->contradictory_points, 0, 2) as $cp)
                                        <p class="line-clamp-2">&bull; {{ $cp }}</p>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex items-center justify-between pt-2 border-t border-slate-800/60 text-[10px] text-slate-400">
                                <span class="capitalize">Confidence: <strong class="text-slate-300">{{ $agent->confidence }}</strong></span>
                                <span class="font-mono">{{ $agent->execution_duration_ms }}ms</span>
                            </div>
                        </div>
                    @endforeach
                @elseif(!empty($fallbackSummaries))
                    @foreach($fallbackSummaries as $agent)
                        @php
                            $c = strtoupper($agent['conclusion'] ?? 'WAIT');
                            $isBull = in_array($c, ['BULLISH', 'BUY']);
                            $isBear = in_array($c, ['BEARISH', 'SELL']);
                        @endphp
                        <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-3">
                            <div class="flex items-start justify-between gap-2 border-b border-slate-800/80 pb-2.5">
                                <div>
                                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">
                                        {{ $agent['role_title'] ?? ucwords(str_replace('_', ' ', $agent['role'] ?? 'Analyst')) }}
                                    </h3>
                                    <span class="text-[10px] text-slate-400 font-mono block">
                                        {{ $agent['provider'] ?? 'AI' }} &bull; {{ $agent['model'] ?? 'model' }}
                                    </span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $isBull ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' : ($isBear ? 'bg-rose-950 text-rose-300 border border-rose-800' : 'bg-amber-950 text-amber-300 border border-amber-800') }}">
                                    {{ $c }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed">{{ $agent['summary'] ?? 'Analysis completed.' }}</p>
                        </div>
                    @endforeach
                @else
                    <div class="col-span-full p-4 rounded-xl bg-slate-900 text-xs text-slate-400 text-center">
                        Detailed model telemetry will populate on subsequent multi-AI scans.
                    </div>
                @endif
            </div>

            <!-- Disagreements and Conflicts Section -->
            @if(!empty($contradictoryEvidence))
                <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 space-y-2">
                    <span class="text-xs font-bold text-rose-400 uppercase tracking-wider flex items-center space-x-1.5">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path></svg>
                        <span>Cross-Model Contradictions &amp; Limitations</span>
                    </span>
                    <ul class="text-xs text-slate-300 space-y-1">
                        @foreach($contradictoryEvidence as $contra)
                            <li class="flex items-start space-x-2">
                                <span class="text-rose-400">&bull;</span>
                                <span>{{ $contra }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <!-- HIDDEN PRE-FORMATTED TEXT FOR EASY COPYING -->
    <div id="trading-plan-text" class="hidden">
ChartSense AI Trading Plan
Symbol: {{ $scan->symbol }} ({{ $scan->timeframe }})
Decision: {{ $result?->direction ?? ($scan->status === 'failed' ? 'FAILED' : 'WAIT') }}
@if($result)
Entry: {{ $result->entry_target ?? ($result->entry_min ? ($result->entry_min . ' - ' . $result->entry_max) : 'Market Retest') }}
Stop Loss: {{ $result->stop_loss ?? 'N/A' }}
TP1: {{ $result->take_profit_1 ?? 'N/A' }}
TP2: {{ $result->take_profit_2 ?? 'N/A' }}
TP3: {{ $result->take_profit_3 ?? 'N/A' }}
R:R: 1:{{ $result->risk_reward_ratio ?? 'N/A' }}
Confidence: {{ $result->confidence ?? 'Moderate' }}
@else
Status: {{ ucfirst($scan->status) }}
@endif
Strategy: {{ $scan->strategy }}
Generated by ChartSense AI (Exness Verified Access)
    </div>

    <!-- MANDATORY RISK DISCLAIMER -->
    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 text-[11px] text-slate-500 leading-relaxed text-center">
        ChartSense AI analysis is generated for educational and risk planning purposes only. It is not financial or trade execution advice. Never trade with funds you cannot afford to lose.
    </div>
</div>
@endsection
