@extends('layouts.app')

@section('title', 'Global Market News Intelligence — Real-Time Live Stream — ChartSense AI')

@section('content')
<div class="min-h-screen bg-slate-950 text-slate-100 py-8 px-4 sm:px-6 lg:px-8"
     x-data="newsLiveStream({
         latestId: {{ $recentNews->first()?->id ?? 0 }},
         symbol: '{{ $symbol }}',
         asset: '{{ $assetFilter }}',
         initialRiskLevel: '{{ $contextSnapshot['market_risk_level'] ?? 'NORMAL' }}',
         initialSentiment: '{{ $contextSnapshot['sentiment_bias'] ?? 'NEUTRAL' }}',
         initialNarrative: '{{ addslashes($contextSnapshot['summary_narrative'] ?? 'Baseline liquidity conditions.') }}'
     })"
     x-init="initStream()">

    <!-- Floating Real-Time Breaking News Toast Notification -->
    <div x-show="toastMessage" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-4"
         x-cloak
         class="fixed bottom-6 right-6 z-50 max-w-md p-4 rounded-2xl bg-slate-900 border-2 border-cyan-500/80 shadow-2xl shadow-cyan-500/20 backdrop-blur-xl flex items-start space-x-3">
        <div class="w-8 h-8 rounded-xl bg-cyan-950 border border-cyan-800 flex items-center justify-center text-cyan-400 shrink-0 mt-0.5">
            <svg class="w-4 h-4 animate-bounce" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        </div>
        <div class="flex-1 space-y-1">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase tracking-wider text-cyan-400">Live Wire Update</span>
                <button type="button" @click="toastMessage = null" class="text-slate-400 hover:text-white">&times;</button>
            </div>
            <p class="text-xs text-white font-semibold leading-snug line-clamp-2" x-text="toastMessage"></p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Top Header & Live Radar Controls -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 pb-6 border-b border-slate-800/80">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Live Pulsating Radar Badge -->
                    <div class="flex items-center space-x-2 text-xs font-bold px-3 py-1.5 rounded-full border shadow-sm transition"
                         :class="isStreaming ? 'bg-emerald-950/80 text-emerald-400 border-emerald-800 shadow-emerald-950/50' : 'bg-slate-900 text-slate-400 border-slate-700'">
                        <span class="w-2 h-2 rounded-full" :class="isStreaming ? 'bg-emerald-400 animate-ping' : 'bg-slate-500'"></span>
                        <span x-text="isStreaming ? 'LIVE REAL-TIME STREAMING: ACTIVE' : 'LIVE STREAM PAUSED'"></span>
                    </div>

                    <!-- UTC Live Clock -->
                    <span class="text-xs text-slate-400 font-mono flex items-center gap-1.5 bg-slate-900 px-3 py-1.5 rounded-full border border-slate-800">
                        <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span x-text="utcTime"></span>
                    </span>

                    <!-- Synced Badge -->
                    <span class="text-[11px] text-slate-400 font-mono" x-text="'Last updated: ' + lastSyncText"></span>
                </div>

                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-white flex items-center gap-3">
                    <span>GLOBAL MARKET NEWS INTELLIGENCE</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 max-w-3xl leading-relaxed">
                    Continuous 24/7 real-time global news from Forex Factory, ForexLive, InvestingLive, CNBC, BBC, MarketWatch, and CoinDesk, combined with verified macroeconomic calendars and automated News Risk Firewall protections.
                </p>

                <!-- Live Stream Action Buttons -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <!-- Toggle Live Auto-Stream -->
                    <button type="button" 
                            @click="toggleStreaming()"
                            class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold transition border"
                            :class="isStreaming ? 'bg-slate-900 hover:bg-slate-800 text-slate-200 border-slate-700' : 'bg-emerald-600 hover:bg-emerald-500 text-slate-950 border-emerald-500'">
                        <svg class="w-3.5 h-3.5 mr-1.5" :class="isStreaming ? 'text-amber-400' : 'text-slate-950'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="6" y="4" width="4" height="16" x-show="isStreaming"></rect>
                            <rect x="14" y="4" width="4" height="16" x-show="isStreaming"></rect>
                            <polygon points="5 3 19 12 5 21 5 3" x-show="!isStreaming"></polygon>
                        </svg>
                        <span x-text="isStreaming ? 'Pause Real-Time Stream' : 'Resume Live Stream'"></span>
                    </button>

                    <!-- Force Sync Global Wire Now -->
                    <button type="button" 
                            @click="forceSyncNow()"
                            :disabled="isSyncing"
                            class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold bg-cyan-500 hover:bg-cyan-400 text-slate-950 shadow-lg shadow-cyan-500/20 transition disabled:opacity-50">
                        <svg class="w-3.5 h-3.5 mr-1.5" :class="isSyncing ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                        </svg>
                        <span x-text="isSyncing ? 'Syncing All Global Feeds...' : '⚡ Force Sync Global Wire Now'"></span>
                    </button>

                    <!-- Sound Alert Toggle -->
                    <button type="button" 
                            @click="soundEnabled = !soundEnabled"
                            class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07" x-show="soundEnabled"></path>
                            <line x1="23" y1="9" x2="17" y2="15" x2="17" x-show="!soundEnabled"></line>
                            <line x1="17" y1="9" x2="23" y2="15" x-show="!soundEnabled"></line>
                        </svg>
                        <span x-text="soundEnabled ? 'Chime: On' : 'Chime: Muted'"></span>
                    </button>

                    <!-- Live Incoming Badge -->
                    <span x-show="newStreamCount > 0" 
                          x-transition
                          class="px-2.5 py-1 rounded-full text-xs font-black bg-rose-500 text-white animate-bounce shadow-lg shadow-rose-500/30">
                        +<span x-text="newStreamCount"></span> New Breaking Wires!
                    </span>
                </div>
            </div>

            <!-- Global Market Risk Gauge (Reactive) -->
            <div id="market-risk-card" 
                 class="rounded-3xl border p-5 backdrop-blur-md shadow-2xl flex items-center space-x-4 min-w-[300px] transition duration-300"
                 :class="riskCardClass">
                <div class="p-3.5 rounded-2xl bg-slate-900/90 border border-slate-700/60 shrink-0">
                    <svg class="w-8 h-8" :class="riskIconClass" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div class="space-y-1">
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest font-mono block font-semibold">Current Market Risk</span>
                    <div class="flex items-center space-x-2">
                        <span class="text-2xl font-black tracking-tight" x-text="riskLevel"></span>
                        <span class="text-[10px] font-black px-2 py-0.5 rounded border uppercase"
                              :class="riskBadgeClass"
                              x-text="riskLevel === 'CRITICAL' ? 'FIREWALL ACTIVE' : (riskLevel === 'HIGH' ? 'ELEVATED RISK' : 'NORMAL CONDITIONS')">
                        </span>
                    </div>
                    <span class="text-xs text-slate-300 block leading-snug max-w-[260px] truncate" x-text="riskNarrative"></span>
                </div>
            </div>
        </div>

        <!-- Active News Risk Firewall Restrictions Alert -->
        @if($activeRestrictions->isNotEmpty())
            <div id="firewall-restrictions-banner" class="rounded-3xl border-2 border-rose-500 bg-rose-950/60 p-6 backdrop-blur-md shadow-2xl shadow-rose-950/60 animate-pulse space-y-3">
                <div class="flex items-start space-x-4">
                    <div class="p-3 rounded-2xl bg-rose-900/80 border border-rose-600 text-rose-300 shrink-0">
                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                        </svg>
                    </div>
                    <div class="space-y-1.5 flex-1">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-black uppercase tracking-wider text-rose-300">NEWS RISK FIREWALL — ACTIVE TRADE RESTRICTIONS</span>
                                <span class="px-2 py-0.5 text-[10px] font-extrabold rounded bg-rose-900 text-rose-100 border border-rose-700">ENFORCED HARD VETO</span>
                            </div>
                        </div>
                        @foreach($activeRestrictions as $restriction)
                            <p class="text-xs sm:text-sm font-semibold text-rose-200">
                                <span class="font-black text-white">[{{ $restriction->symbol }}]</span> {{ $restriction->reason }}
                                <span class="text-xs text-rose-400 font-mono ml-2">Cooldown expires at {{ $restriction->expires_at->format('H:i') }} UTC</span>
                            </p>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Upcoming High-Impact Economic Events (Next 48 Hours) -->
        <div id="economic-calendar-section" class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-800/80 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white tracking-tight">Upcoming High-Impact Economic Releases</h2>
                        <p class="text-xs text-slate-400">Institutional events triggering the automated News Risk Firewall pre-event freeze windows.</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 text-xs">
                    <span class="text-slate-400">Filter:</span>
                    <a href="{{ route('news.index', ['symbol' => $symbol]) }}" class="px-2.5 py-1 rounded-lg border border-slate-700 bg-slate-950 text-slate-300 hover:text-white">All</a>
                    <a href="{{ route('news.index', ['importance' => 'CRITICAL', 'symbol' => $symbol]) }}" class="px-2.5 py-1 rounded-lg border border-rose-800 bg-rose-950/60 text-rose-300 font-bold hover:bg-rose-900">Critical Only</a>
                </div>
            </div>

            <!-- Calendar Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800 bg-slate-950/40">
                        <tr>
                            <th class="py-3 px-3 font-semibold">Currency</th>
                            <th class="py-3 px-3 font-semibold">Event Name</th>
                            <th class="py-3 px-3 font-semibold">Scheduled (UTC)</th>
                            <th class="py-3 px-3 font-semibold">Countdown</th>
                            <th class="py-3 px-3 font-semibold">Importance</th>
                            <th class="py-3 px-3 font-semibold">Prior / Consensus</th>
                            <th class="py-3 px-3 font-semibold">Actual / Surprise</th>
                            <th class="py-3 px-4 font-semibold text-right">Firewall Gatekeeper</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($upcomingEvents as $event)
                            @php
                                $now = now()->utc();
                                $diffMin = (int)round($now->diffInMinutes($event->scheduled_at, false));
                                $isBlockedNow = ($event->importance === 'CRITICAL' && $diffMin >= 0 && $diffMin <= 30)
                                    || ($event->importance === 'HIGH' && $diffMin >= 0 && $diffMin <= 15);
                                $isStabilizing = ($diffMin < 0 && abs($diffMin) <= 15);
                            @endphp
                            <tr class="hover:bg-slate-800/40 transition {{ $isBlockedNow ? 'bg-rose-950/20' : '' }}">
                                <td class="py-3 px-3 font-mono font-bold text-white">
                                    <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-xs">{{ $event->currency }}</span>
                                </td>
                                <td class="py-3 px-3 font-semibold text-white">
                                    {{ $event->event_name }}
                                </td>
                                <td class="py-3 px-3 font-mono text-xs text-slate-300">
                                    {{ $event->scheduled_at->format('M d, H:i') }}
                                </td>
                                <td class="py-3 px-3 font-mono text-xs">
                                    @if($diffMin < 0)
                                        <span class="text-slate-500">Released {{ abs($diffMin) }}m ago</span>
                                    @elseif($diffMin <= 30)
                                        <span class="font-bold text-rose-400 animate-pulse">In {{ $diffMin }}m</span>
                                    @else
                                        <span class="text-cyan-400">In {{ $diffMin }}m</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    @if($event->importance === 'CRITICAL')
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-rose-950 text-rose-300 border border-rose-800">CRITICAL</span>
                                    @elseif($event->importance === 'HIGH')
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-amber-950 text-amber-300 border border-amber-800">HIGH</span>
                                    @else
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-slate-800 text-slate-400 border border-slate-700">{{ $event->importance }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 font-mono text-xs text-slate-400">
                                    {{ $event->previous_value ?? '—' }} / <span class="text-slate-200">{{ $event->forecast_value ?? '—' }}</span>
                                </td>
                                <td class="py-3 px-3 font-mono text-xs">
                                    @if($event->actual_value)
                                        <div class="flex items-center space-x-1.5">
                                            <span class="font-bold text-white">{{ $event->actual_value }}</span>
                                            @if($event->surprise_direction === 'BETTER_THAN_EXPECTED')
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-950 text-emerald-400 border border-emerald-800 font-bold">BEAT</span>
                                            @elseif($event->surprise_direction === 'WORSE_THAN_EXPECTED')
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-rose-950 text-rose-400 border border-rose-800 font-bold">MISS</span>
                                            @else
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">IN LINE</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-500 italic">Pending</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    @if($isBlockedNow)
                                        <span class="px-2 py-1 text-[10px] font-bold rounded bg-rose-900 text-rose-200 border border-rose-700 animate-pulse">
                                            TRADE BLOCKED
                                        </span>
                                    @elseif($isStabilizing)
                                        <span class="px-2 py-1 text-[10px] font-bold rounded bg-amber-900 text-amber-200 border border-amber-700">
                                            STABILIZING
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-[10px] font-semibold rounded bg-slate-800 text-emerald-400 border border-slate-700">
                                            ELIGIBLE
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-500">
                                    No scheduled economic events found for the requested period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Global Financial News Stream & Asset Filter -->
        <div id="live-news-stream-section" class="bg-slate-900/70 border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-2xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800/80 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 shadow">
                        <svg class="w-4 h-4 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 11a9 9 0 0 1 9 9"></path>
                            <path d="M4 4a16 16 0 0 1 16 16"></path>
                            <circle cx="5" cy="19" r="1"></circle>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h2 class="text-lg font-bold text-white">Live Financial News Stream (Global Real-Time Wire)</h2>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-950/80 text-amber-300 border border-amber-800">
                                7 Feeds Streaming (Inc. Forex Factory)
                            </span>
                        </div>
                        <p class="text-xs text-slate-400">Live multi-source ingestion across Forex Factory, ForexLive, InvestingLive, CNBC, BBC, MarketWatch &amp; CoinDesk.</p>
                    </div>
                </div>

                <!-- Asset Filter Tabs -->
                <div class="flex items-center space-x-1.5 overflow-x-auto text-xs bg-slate-950 p-1.5 rounded-2xl border border-slate-800">
                    @foreach([
                        'all' => 'All Markets',
                        'forex' => 'Forex',
                        'commodities' => 'Gold & Commodities',
                        'central_bank' => 'Central Banks',
                        'crypto' => 'Crypto',
                        'geopolitical' => 'Geopolitics'
                    ] as $key => $label)
                        <a href="{{ route('news.index', ['asset' => $key, 'symbol' => $symbol]) }}" 
                           class="px-3 py-1.5 rounded-xl font-semibold transition whitespace-nowrap {{ $assetFilter === $key ? 'bg-cyan-500 text-slate-950 font-bold shadow' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Real-Time Streamed New Articles (Injected via Alpine without page refresh) -->
            <template x-if="streamedArticles.length > 0">
                <div class="space-y-4">
                    <div class="flex items-center justify-between text-xs text-cyan-400 font-bold uppercase tracking-wider border-b border-cyan-900/60 pb-2">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                            <span>Just Received In Live Stream (<span x-text="streamedArticles.length"></span> articles)</span>
                        </span>
                        <button type="button" @click="streamedArticles = []" class="text-[11px] text-slate-400 hover:text-white normal-case font-normal">Clear New</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="art in streamedArticles" :key="'live-' + art.id">
                            <div class="rounded-2xl border-2 border-cyan-500/80 bg-cyan-950/20 p-5 shadow-xl flex flex-col justify-between space-y-3 transform transition hover:scale-[1.01]">
                                <div class="space-y-2.5">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-black bg-rose-500 text-white animate-pulse">JUST IN</span>
                                            <span class="font-bold text-cyan-400 font-mono" x-text="art.source_name"></span>
                                        </div>
                                        <span class="font-mono text-slate-400" x-text="art.time_ago"></span>
                                    </div>
                                    <h3 class="text-sm font-bold text-white hover:text-cyan-300 transition leading-snug">
                                        <a :href="art.canonical_url || '#'" target="_blank" rel="noopener noreferrer" x-text="art.title"></a>
                                    </h3>
                                    <p class="text-xs text-slate-300 line-clamp-3 leading-relaxed" x-text="art.summary"></p>
                                </div>

                                <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px]">
                                    <span class="px-2 py-0.5 rounded border font-mono font-bold text-[10px]"
                                          :class="art.sentiment === 'BULLISH' ? 'bg-emerald-950 text-emerald-400 border-emerald-800' : (art.sentiment === 'BEARISH' ? 'bg-rose-950 text-rose-400 border-rose-800' : 'bg-slate-800 text-slate-300 border-slate-700')"
                                          x-text="art.sentiment">
                                    </span>
                                    <div class="flex items-center space-x-1">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-900 border border-slate-800 text-[10px] text-cyan-300 font-mono" x-text="art.country_code"></span>
                                        <template x-for="asset in (art.affected_assets || []).slice(0, 2)" :key="asset">
                                            <span class="px-1.5 py-0.5 rounded bg-slate-900 border border-slate-800 text-[10px] text-slate-300 font-mono" x-text="asset"></span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- News Articles Grid (Persistent Paginated Feed) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($recentNews as $article)
                    @php
                        $sentClass = match($article->sentiment_classification) {
                            'BULLISH' => 'bg-emerald-950 text-emerald-400 border-emerald-800',
                            'BEARISH' => 'bg-rose-950 text-rose-400 border-rose-800',
                            'MIXED' => 'bg-amber-950 text-amber-400 border-amber-800',
                            default => 'bg-slate-800 text-slate-300 border-slate-700',
                        };

                        $sourceColor = match($article->source_name) {
                            'Forex Factory' => 'text-amber-300 font-extrabold',
                            'ForexLive' => 'text-cyan-400',
                            'InvestingLive' => 'text-emerald-400',
                            'CNBC Markets' => 'text-blue-400',
                            'BBC World Business' => 'text-rose-400',
                            'MarketWatch' => 'text-amber-400',
                            'CoinDesk' => 'text-purple-400',
                            default => 'text-cyan-400',
                        };
                    @endphp
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4 sm:p-5 hover:border-slate-700 transition flex flex-col justify-between space-y-3 group shadow-lg">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold font-mono {{ $sourceColor }}">{{ $article->source_name }}</span>
                                    @if($article->importance === 'CRITICAL')
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-rose-950 text-rose-300 border border-rose-800">CRITICAL</span>
                                    @elseif($article->importance === 'HIGH')
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-950 text-amber-300 border border-amber-800">HIGH</span>
                                    @endif
                                </div>
                                <span class="font-mono text-slate-400">{{ $article->published_at->diffForHumans() }}</span>
                            </div>
                            <h3 class="text-sm font-bold text-white group-hover:text-cyan-300 transition leading-snug line-clamp-2">
                                @if($article->canonical_url)
                                    <a href="{{ $article->canonical_url }}" target="_blank" rel="noopener noreferrer" class="hover:underline flex items-start gap-1">
                                        <span>{{ $article->title }}</span>
                                        <svg class="w-3.5 h-3.5 text-slate-500 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                    </a>
                                @else
                                    {{ $article->title }}
                                @endif
                            </h3>
                            @if($article->summary)
                                <p class="text-xs text-slate-400 line-clamp-3 leading-relaxed">
                                    {{ $article->summary }}
                                </p>
                            @endif
                        </div>

                        <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px]">
                            <span class="px-2 py-0.5 rounded border font-mono font-bold text-[10px] {{ $sentClass }}">
                                {{ $article->sentiment_classification }}
                            </span>
                            <div class="flex items-center space-x-1">
                                <span class="px-1.5 py-0.5 rounded bg-slate-900 border border-slate-800 text-[10px] text-cyan-300 font-mono font-bold">
                                    {{ $article->country_code ?? 'GLOBAL' }}
                                </span>
                                @if(is_array($article->affected_assets))
                                    @foreach(array_slice($article->affected_assets, 0, 2) as $asset)
                                        <span class="px-1.5 py-0.5 rounded bg-slate-900 border border-slate-800 text-[10px] text-slate-300 font-mono">
                                            {{ $asset }}
                                        </span>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-slate-500 space-y-2">
                        <p class="text-sm">No articles ingested for the selected asset filter yet.</p>
                        <button type="button" @click="forceSyncNow()" class="text-xs text-cyan-400 font-bold underline">Click here to sync global feeds immediately</button>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="pt-4 border-t border-slate-800">
                {{ $recentNews->links() }}
            </div>
        </div>

        <!-- News Provider Health Matrix -->
        <div id="provider-health-section" class="bg-slate-900/50 border border-slate-800 rounded-3xl p-6 sm:p-7 shadow-xl space-y-4">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-white">News Ingestion Provider Health &amp; Global Coverage</h2>
                    <p class="text-xs text-slate-400">Live operational status, latency, and synchronization health for all global financial wire sources.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                @foreach($providers as $provider)
                    @php
                        $isHealthy = $provider->health_status === 'healthy';
                        $statusBadge = $isHealthy 
                            ? 'bg-emerald-950 text-emerald-400 border-emerald-800'
                            : ($provider->health_status === 'degraded' ? 'bg-amber-950 text-amber-400 border-amber-800' : 'bg-rose-950 text-rose-400 border-rose-800');
                    @endphp
                    <div class="p-4 rounded-2xl border border-slate-800 bg-slate-950/70 flex items-center justify-between">
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-white block">{{ $provider->name }}</span>
                            <span class="text-[10px] text-slate-400 font-mono block">Type: {{ strtoupper($provider->type) }}</span>
                            <span class="text-[10px] text-slate-500 font-mono block">
                                Last sync: {{ $provider->last_sync_at ? $provider->last_sync_at->diffForHumans() : 'Just now' }}
                            </span>
                        </div>
                        <div class="text-right space-y-1">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $statusBadge }} block">
                                {{ strtoupper($provider->health_status) }}
                            </span>
                            @if($provider->latency_ms)
                                <span class="text-[10px] text-slate-400 font-mono block">{{ $provider->latency_ms }} ms</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

<script>
function newsLiveStream(config) {
    return {
        latestId: config.latestId || 0,
        symbol: config.symbol || 'XAUUSD',
        asset: config.asset || 'all',
        isStreaming: true,
        isSyncing: false,
        soundEnabled: false,
        lastSyncText: 'Moments ago',
        newStreamCount: 0,
        streamedArticles: [],
        toastMessage: null,
        pollTimer: null,
        clockTimer: null,
        utcTime: new Date().toISOString().replace('T', ' ').substring(0, 19) + ' UTC',
        riskLevel: config.initialRiskLevel || 'NORMAL',
        sentimentBias: config.initialSentiment || 'NEUTRAL',
        riskNarrative: config.initialNarrative || 'Baseline liquidity conditions.',

        get riskCardClass() {
            if (this.riskLevel === 'CRITICAL') return 'border-rose-500 bg-rose-950/40 text-rose-300';
            if (this.riskLevel === 'HIGH') return 'border-amber-500 bg-amber-950/40 text-amber-300';
            if (this.riskLevel === 'ELEVATED') return 'border-cyan-500 bg-cyan-950/40 text-cyan-300';
            return 'border-emerald-500 bg-emerald-950/40 text-emerald-300';
        },

        get riskIconClass() {
            if (this.riskLevel === 'CRITICAL') return 'text-rose-400';
            if (this.riskLevel === 'HIGH') return 'text-amber-400';
            return 'text-emerald-400';
        },

        get riskBadgeClass() {
            if (this.riskLevel === 'CRITICAL') return 'bg-rose-500/20 text-rose-300 border-rose-500/40';
            if (this.riskLevel === 'HIGH') return 'bg-amber-500/20 text-amber-300 border-amber-500/40';
            if (this.riskLevel === 'ELEVATED') return 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40';
            return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40';
        },

        initStream() {
            // Update clock every second
            this.clockTimer = setInterval(() => {
                this.utcTime = new Date().toISOString().replace('T', ' ').substring(0, 19) + ' UTC';
            }, 1000);

            // Auto-poll live stream every 8 seconds
            this.pollTimer = setInterval(() => {
                this.pollLiveNews();
            }, 8000);
        },

        toggleStreaming() {
            this.isStreaming = !this.isStreaming;
            if (this.isStreaming) {
                this.pollLiveNews();
            }
        },

        async pollLiveNews() {
            if (!this.isStreaming || this.isSyncing) return;

            try {
                const res = await fetch(`/api/news/live?after_id=${this.latestId}&symbol=${this.symbol}&asset=${this.asset}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.articles && data.articles.length > 0) {
                        data.articles.forEach(art => {
                            if (!this.hasArticle(art.id)) {
                                this.streamedArticles.unshift(art);
                                this.latestId = Math.max(this.latestId, art.id);
                                this.newStreamCount++;
                            }
                        });

                        const first = data.articles[0];
                        this.showToast(first.source_name + ': ' + first.title);

                        if (this.soundEnabled) {
                            this.playChime();
                        }
                    }

                    if (data.market_risk) {
                        this.riskNarrative = data.market_risk.macro_narrative || this.riskNarrative;
                        this.sentimentBias = data.market_risk.sentiment_bias || this.sentimentBias;
                    }

                    this.lastSyncText = 'Just now';
                }
            } catch (err) {
                // Background poll failure is silent
            }
        },

        async forceSyncNow() {
            this.isSyncing = true;
            this.showToast('Connecting to global news wires across the world...');

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/api/news/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    }
                });

                if (res.ok) {
                    const data = await res.json();
                    this.showToast(data.message || 'Global synchronization complete!');
                    await this.pollLiveNews();
                } else {
                    this.showToast('Global sync returned an advisory response.');
                }
            } catch (err) {
                this.showToast('Sync completed with partial network response.');
            } finally {
                this.isSyncing = false;
            }
        },

        hasArticle(id) {
            return this.streamedArticles.some(a => a.id === id);
        },

        showToast(msg) {
            this.toastMessage = msg;
            setTimeout(() => {
                if (this.toastMessage === msg) {
                    this.toastMessage = null;
                }
            }, 5000);
        },

        playChime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
                gain.gain.setValueAtTime(0.1, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                osc.start();
                osc.stop(ctx.currentTime + 0.3);
            } catch (e) {}
        }
    };
}
</script>
@endsection
