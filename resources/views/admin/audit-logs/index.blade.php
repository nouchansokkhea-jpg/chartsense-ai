@extends('layouts.admin')

@section('title', 'System Audit Trail - ChartSense Admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">System Audit Logs</h1>
            <p class="text-xs text-slate-400 mt-1">Immutable security ledger tracking authentication, referral reviews, scan pipeline jobs, and configuration modifications.</p>
        </div>
        <div class="text-xs text-slate-400">
            Total Logged Events: <span class="font-bold text-white">{{ $logs->total() }}</span>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.audit-logs') }}" class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 flex flex-col sm:flex-row gap-3 text-xs">
        <div class="flex-1">
            <input type="text" name="action" value="{{ request('action') }}" placeholder="Filter by action name (e.g. login, referral, scan, settings)..." class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500">
        </div>
        <div class="flex items-center space-x-2">
            <button type="submit" class="py-2 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold transition">
                Search Logs
            </button>
            <a href="{{ route('admin.audit-logs') }}" class="py-2 px-3 rounded-xl bg-slate-950 border border-slate-800 hover:bg-slate-800 text-slate-400 text-center transition">
                Reset
            </a>
        </div>
    </form>

    <!-- Table of Audit Events -->
    <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3.5 px-6">Timestamp</th>
                        <th class="py-3.5 px-6">Action</th>
                        <th class="py-3.5 px-6">Actor / User</th>
                        <th class="py-3.5 px-6">Entity Target</th>
                        <th class="py-3.5 px-6">IP / Metadata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3.5 px-6 whitespace-nowrap text-slate-400 font-mono text-[11px]">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                                <div class="text-[10px] text-slate-500">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="py-3.5 px-6">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-slate-800 text-cyan-400 border border-slate-700">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3.5 px-6">
                                @if($log->admin)
                                    <div class="font-bold text-indigo-400">{{ $log->admin->name }}</div>
                                    <div class="text-[10px] text-slate-500">Admin (ID: {{ $log->admin->id }})</div>
                                @elseif($log->user)
                                    <div class="font-semibold text-white">{{ $log->user->name }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-slate-500">System Guest / Automated</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-6 text-slate-400 font-mono text-[11px]">
                                {{ $log->entity_type ?? 'N/A' }}
                                @if($log->entity_id)
                                    <span class="text-slate-500">#{{ $log->entity_id }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-6 max-w-xs">
                                <div class="font-mono text-[10px] text-slate-400">{{ $log->ip_address ?? '127.0.0.1' }}</div>
                                @if(!empty($log->details))
                                    <div class="truncate text-[10px] text-slate-500 font-mono" title="{{ json_encode($log->details) }}">
                                        {{ Str::limit(json_encode($log->details), 40) }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500">
                                No audit events recorded matching your query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-800/80">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
