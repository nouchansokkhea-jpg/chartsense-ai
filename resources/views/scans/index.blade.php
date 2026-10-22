@extends('layouts.app')

@section('title', 'Scan History - ChartSense AI')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Chart Scan History</h1>
            <p class="text-xs text-slate-400 mt-1">Review, filter, and export your previous AI trading chart analyses.</p>
        </div>
        <a href="{{ route('scanner') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-950 bg-cyan-400 hover:bg-cyan-300 transition">
            <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
            New Scan
        </a>
    </div>

    <!-- Filters Form -->
    <form method="GET" action="{{ route('scans.index') }}" class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
        <div>
            <input type="text" name="symbol" value="{{ request('symbol') }}" placeholder="Filter by symbol (e.g. XAUUSD)" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500">
        </div>
        <div>
            <select name="timeframe" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white focus:outline-none focus:border-cyan-500">
                <option value="">All Timeframes</option>
                <option value="M5" {{ request('timeframe') == 'M5' ? 'selected' : '' }}>M5</option>
                <option value="M15" {{ request('timeframe') == 'M15' ? 'selected' : '' }}>M15</option>
                <option value="H1" {{ request('timeframe') == 'H1' ? 'selected' : '' }}>H1</option>
                <option value="H4" {{ request('timeframe') == 'H4' ? 'selected' : '' }}>H4</option>
                <option value="D1" {{ request('timeframe') == 'D1' ? 'selected' : '' }}>D1</option>
            </select>
        </div>
        <div>
            <select name="direction" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white focus:outline-none focus:border-cyan-500">
                <option value="">All Decisions</option>
                <option value="BUY" {{ request('direction') == 'BUY' ? 'selected' : '' }}>BUY SETUP</option>
                <option value="SELL" {{ request('direction') == 'SELL' ? 'selected' : '' }}>SELL SETUP</option>
                <option value="WAIT" {{ request('direction') == 'WAIT' ? 'selected' : '' }}>WAIT / NO SETUP</option>
            </select>
        </div>
        <div class="flex items-center space-x-2">
            <button type="submit" class="w-full py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold transition">
                Filter
            </button>
            <a href="{{ route('scans.index') }}" class="py-2 px-3 rounded-xl bg-slate-950 border border-slate-800 hover:bg-slate-800 text-slate-400 text-center transition">
                Clear
            </a>
        </div>
    </form>

    <!-- Scans Grid / List -->
    @if($scans->isEmpty())
        <div class="p-16 text-center space-y-4 rounded-2xl bg-slate-900/40 border border-slate-800">
            <div class="w-12 h-12 rounded-xl bg-slate-800 flex items-center justify-center text-slate-400 mx-auto">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <h3 class="text-base font-bold text-white">No scans match your criteria</h3>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">Try clearing your filters or upload a new trading chart.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($scans as $scan)
                @php $res = $scan->result; @endphp
                <div class="rounded-2xl bg-slate-900/80 border border-slate-800 p-6 space-y-4 hover:border-slate-700 shadow-xl transition flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="font-extrabold text-lg text-white font-mono">{{ $scan->symbol }}</span>
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-slate-800 text-slate-300">{{ $scan->timeframe }}</span>
                            </div>
                            @if($res)
                                @if($res->direction === 'BUY')
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">BUY</span>
                                @elseif($res->direction === 'SELL')
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-950 text-rose-400 border border-rose-800">SELL</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-950 text-amber-400 border border-amber-800">{{ $res->direction }}</span>
                                @endif
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs py-2 border-y border-slate-800/80">
                            <div>
                                <span class="text-slate-500 block text-[10px] uppercase">Entry</span>
                                <span class="font-mono text-white font-medium">{{ $res?->entry_target ? number_format($res->entry_target, 2) : 'Retest' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px] uppercase">Stop Loss</span>
                                <span class="font-mono text-rose-400 font-medium">{{ $res?->stop_loss ? number_format($res->stop_loss, 2) : '—' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px] uppercase">R:R Ratio</span>
                                <span class="font-mono text-cyan-400 font-semibold">{{ $res?->risk_reward_ratio ? '1:' . number_format($res->risk_reward_ratio, 2) : '—' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[10px] uppercase">Confidence</span>
                                <span class="capitalize text-slate-300 font-medium">{{ $res?->confidence ?? 'Moderate' }}</span>
                            </div>
                        </div>

                        <p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">
                            {{ $res?->technical_explanation ?? 'Analysis generated with confluence validation.' }}
                        </p>
                    </div>

                    <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
                        <span class="text-slate-500">{{ $scan->created_at->diffForHumans() }}</span>
                        <div class="flex items-center space-x-3">
                            <form method="POST" action="{{ route('scans.destroy', $scan) }}" onsubmit="return confirm('Delete this scan record?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-500 hover:text-rose-400 transition" title="Delete scan">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </form>
                            <a href="{{ route('scans.show', $scan) }}" class="font-semibold text-cyan-400 hover:text-cyan-300 flex items-center space-x-1">
                                <span>View Plan</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-4">
            {{ $scans->links() }}
        </div>
    @endif
</div>
@endsection
