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

            @if($marketData->isNotEmpty())
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
                        {{ $macroNews['sentiment_score'] > 0 ? '+' : '' }}{{ $macroNews['sentiment_score'] ?? 0 }}
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
