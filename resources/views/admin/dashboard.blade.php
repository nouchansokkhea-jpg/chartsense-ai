@extends('layouts.admin')

@section('title', 'Admin Overview - ChartSense AI')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight">System &amp; Verification Overview</h1>
        <p class="text-xs text-slate-400 mt-1">Monitor Exness referral verifications, user status, and AI chart scan operations.</p>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Total Users -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Registered Traders</span>
            <div class="flex items-baseline justify-between pt-1">
                <span class="text-3xl font-extrabold text-white font-mono">{{ $totalUsers }}</span>
                <a href="{{ route('admin.users') }}" class="text-xs text-cyan-400 hover:text-cyan-300">View &rarr;</a>
            </div>
        </div>

        <!-- Metric 2: Pending Verifications -->
        <div class="p-5 rounded-2xl {{ $pendingReferrals > 0 ? 'bg-amber-950/20 border-amber-500/40' : 'bg-slate-900/80 border-slate-800' }} border space-y-1">
            <span class="text-xs font-semibold {{ $pendingReferrals > 0 ? 'text-amber-400' : 'text-slate-400' }} uppercase tracking-wider">Pending Review Queue</span>
            <div class="flex items-baseline justify-between pt-1">
                <span class="text-3xl font-extrabold {{ $pendingReferrals > 0 ? 'text-amber-400' : 'text-white' }} font-mono">{{ $pendingReferrals }}</span>
                <a href="{{ route('admin.referrals') }}" class="text-xs font-bold text-amber-400 hover:text-amber-300">Review Queue &rarr;</a>
            </div>
        </div>

        <!-- Metric 3: Verified Referrals -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Verified Exness Accounts</span>
            <div class="flex items-baseline justify-between pt-1">
                <span class="text-3xl font-extrabold text-emerald-400 font-mono">{{ $verifiedReferrals }}</span>
                <span class="text-xs text-emerald-500 font-semibold">Active Scanners</span>
            </div>
        </div>

        <!-- Metric 4: Total Scans -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Completed AI Scans</span>
            <div class="flex items-baseline justify-between pt-1">
                <span class="text-3xl font-extrabold text-cyan-400 font-mono">{{ $completedScans }}</span>
                <span class="text-xs text-slate-500">of {{ $totalScans }} total</span>
            </div>
        </div>
    </div>

    <!-- Quick Review: Pending Referral Verifications -->
    <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden">
        <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                <h2 class="text-base font-bold text-white">Pending Referral Audit Queue</h2>
            </div>
            <a href="{{ route('admin.referrals') }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300">
                Full Queue ({{ $pendingReferrals }}) &rarr;
            </a>
        </div>

        @if($recentVerifications->isEmpty())
            <div class="p-8 text-center text-xs text-slate-400">
                ✓ No verification requests waiting in queue. All accounts audited.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-6">User</th>
                            <th class="py-3 px-6">Submitted Exness ID</th>
                            <th class="py-3 px-6">Evidence File</th>
                            <th class="py-3 px-6">Submitted Time</th>
                            <th class="py-3 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @foreach($recentVerifications as $v)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3.5 px-6">
                                    <div class="font-bold text-white">{{ $v->user->name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $v->user->email }}</div>
                                </td>
                                <td class="py-3.5 px-6 font-mono font-bold text-amber-300">
                                    {{ $v->exness_account_id }}
                                </td>
                                <td class="py-3.5 px-6">
                                    @if($v->evidence->isNotEmpty())
                                        @foreach($v->evidence as $ev)
                                            <a href="{{ route('admin.referrals.evidence', $ev) }}" class="inline-flex items-center text-cyan-400 hover:text-cyan-300 underline font-mono text-[11px]">
                                                <span>{{ Str::limit($ev->file_name, 20) }}</span>
                                                <svg class="w-3 h-3 ml-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                            </a>
                                        @endforeach
                                    @else
                                        <span class="text-slate-500">None attached</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-6 text-slate-400">
                                    {{ $v->submitted_at?->diffForHumans() ?? 'Pending' }}
                                </td>
                                <td class="py-3.5 px-6 text-right space-x-2">
                                    <form method="POST" action="{{ route('admin.referrals.approve', $v) }}" class="inline-block">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition">
                                            Approve
                                        </button>
                                    </form>
                                    <button type="button" onclick="document.getElementById('reject-modal-{{ $v->id }}').showModal()" class="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs transition">
                                        Reject
                                    </button>

                                    <!-- Quick Reject Modal -->
                                    <dialog id="reject-modal-{{ $v->id }}" class="p-6 rounded-2xl bg-slate-900 border border-slate-700 text-slate-200 text-left backdrop:bg-slate-950/80 shadow-2xl max-w-md w-full">
                                        <h3 class="text-base font-bold text-white mb-2">Reject Referral Verification</h3>
                                        <p class="text-xs text-slate-400 mb-4">Please specify why this Exness account ID was not verified under the partner link.</p>
                                        <form method="POST" action="{{ route('admin.referrals.reject', $v) }}" class="space-y-4">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-300 mb-1">Rejection Reason</label>
                                                <input type="text" name="reason" required value="Account ID not found under affiliate partner code." class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs">
                                            </div>
                                            <div class="flex items-center justify-end space-x-2 pt-2">
                                                <button type="button" onclick="document.getElementById('reject-modal-{{ $v->id }}').close()" class="px-4 py-2 rounded-xl text-xs bg-slate-800 text-slate-300">Cancel</button>
                                                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-500 text-white">Reject Account</button>
                                            </div>
                                        </form>
                                    </dialog>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Recent Audit Logs Grid -->
    <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden">
        <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
            <h2 class="text-base font-bold text-white">System Audit Trail</h2>
            <a href="{{ route('admin.audit-logs') }}" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300">
                All Logs &rarr;
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-6">Timestamp</th>
                        <th class="py-3 px-6">Action</th>
                        <th class="py-3 px-6">Entity</th>
                        <th class="py-3 px-6">User / Admin</th>
                        <th class="py-3 px-6">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300 font-mono text-[11px]">
                    @foreach($recentAuditLogs as $log)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3 px-6 text-slate-400">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="py-3 px-6 font-bold text-cyan-400">{{ $log->action }}</td>
                            <td class="py-3 px-6">{{ $log->entity_type }} ({{ $log->entity_id ?? 'N/A' }})</td>
                            <td class="py-3 px-6 text-slate-300">{{ $log->user?->email ?? ($log->admin?->email ?? 'System') }}</td>
                            <td class="py-3 px-6 text-slate-500">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
