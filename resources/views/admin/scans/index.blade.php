@extends('layouts.admin')

@section('title', 'Scan Monitor - ChartSense Admin')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">System Scan Monitor</h1>
            <p class="text-xs text-slate-400 mt-1">Review live and historical chart scans processed across all registered traders.</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('admin.scans') }}" class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
        <div>
            <input type="text" name="symbol" value="{{ request('symbol') }}" placeholder="Filter symbol (e.g. XAUUSD)" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500">
        </div>
        <div>
            <select name="status" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white focus:outline-none focus:border-cyan-500">
                <option value="">All Statuses</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
        <div>
            <select name="direction" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white focus:outline-none focus:border-cyan-500">
                <option value="">All Trading Decisions</option>
                <option value="BUY" {{ request('direction') === 'BUY' ? 'selected' : '' }}>BUY</option>
                <option value="SELL" {{ request('direction') === 'SELL' ? 'selected' : '' }}>SELL</option>
                <option value="WAIT" {{ request('direction') === 'WAIT' ? 'selected' : '' }}>WAIT / NO SETUP</option>
            </select>
        </div>
        <div class="flex items-center space-x-2">
            <button type="submit" class="w-full py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold transition">
                Filter
            </button>
            <a href="{{ route('admin.scans') }}" class="py-2 px-3 rounded-xl bg-slate-950 border border-slate-800 hover:bg-slate-800 text-slate-400 text-center transition">
                Reset
            </a>
        </div>
    </form>

    <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3.5 px-6">Scan ID</th>
                        <th class="py-3.5 px-6">Trader</th>
                        <th class="py-3.5 px-6">Instrument</th>
                        <th class="py-3.5 px-6">Decision</th>
                        <th class="py-3.5 px-6">R:R</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Date</th>
                        <th class="py-3.5 px-6 text-right">View</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    @forelse($scans as $scan)
                        @php $r = $scan->result; @endphp
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-6 font-mono font-bold text-slate-400">
                                #{{ $scan->id }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-semibold text-white">{{ $scan->user->name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $scan->user->email }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="font-bold text-white font-mono">{{ $scan->symbol }}</span>
                                <span class="px-1.5 py-0.5 rounded bg-slate-800 font-mono text-[10px] ml-1">{{ $scan->timeframe }}</span>
                            </td>
                            <td class="py-4 px-6">
                                @if($r)
                                    @if($r->direction === 'BUY')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">BUY</span>
                                    @elseif($r->direction === 'SELL')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-950 text-rose-400 border border-rose-800">SELL</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-950 text-amber-400 border border-amber-800">{{ $r->direction }}</span>
                                    @endif
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 font-mono">
                                {{ $r?->risk_reward_ratio ? '1:' . number_format($r->risk_reward_ratio, 2) : '—' }}
                            </td>
                            <td class="py-4 px-6">
                                @if($scan->status === 'completed')
                                    <span class="text-emerald-400">✓ Completed</span>
                                @elseif($scan->status === 'processing')
                                    <span class="text-cyan-400 animate-pulse">Processing</span>
                                @else
                                    <span class="text-rose-400">Failed</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-slate-500 text-[11px]">
                                {{ $scan->created_at->format('M d H:i') }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                <a href="{{ route('scans.show', $scan) }}" class="text-cyan-400 hover:text-cyan-300 font-semibold">
                                    Report &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                No scans recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800/80">
            {{ $scans->links() }}
        </div>
    </div>
</div>
@endsection
