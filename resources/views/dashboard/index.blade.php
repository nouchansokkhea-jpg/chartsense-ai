@extends('layouts.app')

@section('title', 'Trader Dashboard - ChartSense AI')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <!-- Top Welcome Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-3xl font-extrabold text-white tracking-tight">Trader Dashboard</h1>
                @if($referralStatus === 'VERIFIED')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950 text-emerald-400 border border-emerald-800">
                        ✓ Exness Verified
                    </span>
                @elseif($referralStatus === 'PENDING_VERIFICATION')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-950 text-amber-400 border border-amber-800">
                        ⏳ Verification Pending
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-950 text-rose-400 border border-rose-800">
                        🔒 Scanner Locked
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-400 mt-1">Welcome back, <span class="text-white font-medium">{{ $user->name }}</span>. Ready to analyze the next market structure?</p>
        </div>

        <div class="flex items-center space-x-3">
            @if($canScan)
                <a href="{{ route('scanner') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-xl shadow-cyan-500/20 transition">
                    <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                    New Chart Scan
                </a>
            @else
                <a href="{{ route('referral-access') }}" class="inline-flex items-center px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-950 bg-amber-400 hover:bg-amber-300 shadow-xl shadow-amber-500/20 transition">
                    <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    Unlock Scanner Access
                </a>
            @endif
            <a href="{{ route('scans.index') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-200 bg-slate-900 border border-slate-700 hover:bg-slate-800 transition">
                Scan History
            </a>
        </div>
    </div>

    <!-- Referral Status Banner if Locked or Pending -->
    @if(!$canScan)
        <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-950/40 via-slate-900 to-slate-900 border border-amber-500/30 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-xl">
            <div class="flex items-start space-x-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
                <div class="space-y-1 text-xs">
                    <h3 class="font-bold text-sm text-slate-100">Exness Referral Verification Required</h3>
                    <p class="text-slate-400">
                        {{ $referralStatus === 'PENDING_VERIFICATION' ? 'Your verification is currently under review by our team. Check your status anytime.' : 'Join Exness via our official referral link and submit your account ID to unlock the scanner.' }}
                    </p>
                </div>
            </div>
            <a href="{{ route('referral-access') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-amber-950 bg-amber-400 hover:bg-amber-300 text-center shrink-0 transition">
                {{ $referralStatus === 'PENDING_VERIFICATION' ? 'Check Verification Status &rarr;' : 'Complete Verification &rarr;' }}
            </a>
        </div>
    @endif

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Total Scans -->
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Analyzed</span>
            <div class="flex items-baseline justify-between pt-1">
                <span class="text-3xl font-extrabold text-white font-mono">{{ $totalScans }}</span>
                <span class="text-xs text-slate-500">Scans</span>
            </div>
        </div>

        <!-- Metric 2: Buy Setups -->
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Bullish Setups</span>
            <div class="flex items-baseline justify-between pt-1">
                <span class="text-3xl font-extrabold text-emerald-400 font-mono">{{ $buyCount }}</span>
                <span class="text-xs font-semibold text-emerald-500/80 bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-800/40">BUY</span>
            </div>
        </div>

        <!-- Metric 3: Sell Setups -->
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Bearish Setups</span>
            <div class="flex items-baseline justify-between pt-1">
                <span class="text-3xl font-extrabold text-rose-400 font-mono">{{ $sellCount }}</span>
                <span class="text-xs font-semibold text-rose-500/80 bg-rose-950/60 px-2 py-0.5 rounded border border-rose-800/40">SELL</span>
            </div>
        </div>

        <!-- Metric 4: Neutral / Wait -->
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-1">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Neutral / Wait</span>
            <div class="flex items-baseline justify-between pt-1">
                <span class="text-3xl font-extrabold text-amber-400 font-mono">{{ $waitCount }}</span>
                <span class="text-xs font-semibold text-amber-500/80 bg-amber-950/60 px-2 py-0.5 rounded border border-amber-800/40">WAIT</span>
            </div>
        </div>
    </div>

    <!-- Recent Scans Section -->
    <div class="rounded-2xl bg-slate-900/60 border border-slate-800 shadow-xl overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-800/80 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-white">Recent Analyses</h2>
                <p class="text-xs text-slate-400 mt-0.5">Your latest chart scans and trading plans</p>
            </div>
            <a href="{{ route('scans.index') }}" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300 transition">
                View All Scans &rarr;
            </a>
        </div>

        @if($recentScans->isEmpty())
            <div class="p-12 text-center space-y-4">
                <div class="w-14 h-14 rounded-2xl bg-slate-800/80 border border-slate-700 mx-auto flex items-center justify-center text-slate-400">
                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                </div>
                <div class="space-y-1 max-w-sm mx-auto">
                    <h3 class="text-base font-bold text-white">No scans yet</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        @if($canScan)
                            Upload a chart screenshot or CSV market data to generate your first AI trading plan.
                        @else
                            Complete your Exness referral verification to unlock the scanner.
                        @endif
                    </p>
                </div>
                <div class="pt-2">
                    @if($canScan)
                        <a href="{{ route('scanner') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-slate-950 bg-cyan-400 hover:bg-cyan-300 transition">
                            Scan a Chart Now
                        </a>
                    @else
                        <a href="{{ route('referral-access') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-slate-950 bg-amber-400 hover:bg-amber-300 transition">
                            Unlock Scanner via Exness
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3.5 px-6">Symbol &bull; Timeframe</th>
                            <th class="py-3.5 px-6">Input Method</th>
                            <th class="py-3.5 px-6">Trading Decision</th>
                            <th class="py-3.5 px-6">Entry Zone</th>
                            <th class="py-3.5 px-6">R:R Ratio</th>
                            <th class="py-3.5 px-6">Date</th>
                            <th class="py-3.5 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300 font-medium">
                        @foreach($recentScans as $scan)
                            @php $result = $scan->result; @endphp
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-white font-mono text-sm">{{ $scan->symbol }}</span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[10px]">{{ $scan->timeframe }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center text-slate-400">
                                        @if($scan->input_type === 'screenshot')
                                            <svg class="w-3.5 h-3.5 mr-1 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                            Screenshot
                                        @elseif($scan->input_type === 'csv')
                                            <svg class="w-3.5 h-3.5 mr-1 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                            CSV ({{ $scan->input?->candles_count ?? 0 }} bars)
                                        @else
                                            Manual OHLCV
                                        @endif
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    @if($result)
                                        @if($result->direction === 'BUY')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">
                                                BUY SETUP
                                            </span>
                                        @elseif($result->direction === 'SELL')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-950 text-rose-400 border border-rose-800">
                                                SELL SETUP
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-950 text-amber-400 border border-amber-800">
                                                {{ $result->direction }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-slate-500">Processing...</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 font-mono">
                                    @if($result && $result->entry_target)
                                        {{ number_format($result->entry_target, 2) }}
                                    @elseif($result && $result->entry_min)
                                        {{ number_format($result->entry_min, 2) }} - {{ number_format($result->entry_max, 2) }}
                                    @else
                                        <span class="text-slate-500">&mdash;</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 font-mono">
                                    @if($result && $result->risk_reward_ratio)
                                        <span class="text-cyan-400 font-semibold">1 : {{ number_format($result->risk_reward_ratio, 2) }}</span>
                                    @else
                                        <span class="text-slate-500">&mdash;</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-slate-500">
                                    {{ $scan->created_at->diffForHumans() }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('scans.show', $scan) }}" class="inline-flex items-center text-xs font-semibold text-cyan-400 hover:text-cyan-300">
                                        <span>View Plan</span>
                                        <svg class="w-3.5 h-3.5 ml-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
