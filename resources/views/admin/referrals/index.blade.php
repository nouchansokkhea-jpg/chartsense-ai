@extends('layouts.admin')

@section('title', 'Referral Verification Queue - ChartSense Admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Exness Referral Verifications</h1>
            <p class="text-xs text-slate-400 mt-1">Review trader affiliate registrations and grant or revoke scanner access.</p>
        </div>
        <a href="https://one.exnessonelink.com/a/l4l2nsdkfb?source=app" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-amber-300 bg-amber-500/10 border border-amber-500/30 hover:bg-amber-500/20 transition">
            <span>Official Partner Portal</span>
            <svg class="w-3.5 h-3.5 ml-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline></svg>
        </a>
    </div>

    <!-- Filters Form -->
    <form method="GET" action="{{ route('admin.referrals') }}" class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
        <div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search email, name, or Exness ID..." class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500">
        </div>
        <div>
            <select name="status" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white focus:outline-none focus:border-cyan-500">
                <option value="">All Verification Statuses</option>
                <option value="PENDING_VERIFICATION" {{ request('status') === 'PENDING_VERIFICATION' ? 'selected' : '' }}>Pending Review Queue</option>
                <option value="VERIFIED" {{ request('status') === 'VERIFIED' ? 'selected' : '' }}>Verified (Unlocked)</option>
                <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
                <option value="SUSPENDED" {{ request('status') === 'SUSPENDED' ? 'selected' : '' }}>Suspended</option>
                <option value="NOT_STARTED" {{ request('status') === 'NOT_STARTED' ? 'selected' : '' }}>Not Started</option>
            </select>
        </div>
        <div class="flex items-center space-x-2">
            <button type="submit" class="w-full py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold transition">
                Filter
            </button>
            <a href="{{ route('admin.referrals') }}" class="py-2 px-3 rounded-xl bg-slate-950 border border-slate-800 hover:bg-slate-800 text-slate-400 text-center transition">
                Reset
            </a>
        </div>
    </form>

    <!-- Queue Table -->
    <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3.5 px-6">User</th>
                        <th class="py-3.5 px-6">Exness ID</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Evidence File</th>
                        <th class="py-3.5 px-6">Submitted / Reviewed</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    @forelse($verifications as $v)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-6">
                                <div class="font-bold text-white">{{ $v->user->name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $v->user->email }}</div>
                            </td>
                            <td class="py-4 px-6 font-mono font-bold">
                                {{ $v->exness_account_id ?? '—' }}
                            </td>
                            <td class="py-4 px-6">
                                @if($v->status === 'VERIFIED')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">VERIFIED</span>
                                @elseif($v->status === 'PENDING_VERIFICATION')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-950 text-amber-400 border border-amber-800 animate-pulse">PENDING REVIEW</span>
                                @elseif($v->status === 'REJECTED')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-950 text-rose-400 border border-rose-800">REJECTED</span>
                                @elseif($v->status === 'SUSPENDED')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-950 text-rose-400 border border-rose-800">SUSPENDED</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">NOT STARTED</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if($v->evidence->isNotEmpty())
                                    @foreach($v->evidence as $ev)
                                        <a href="{{ route('admin.referrals.evidence', $ev) }}" class="inline-flex items-center text-cyan-400 hover:text-cyan-300 underline font-mono text-[11px]">
                                            <span>{{ Str::limit($ev->file_name, 16) }}</span>
                                            <svg class="w-3 h-3 ml-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                        </a>
                                    @endforeach
                                @else
                                    <span class="text-slate-500">None</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-slate-400 text-[11px]">
                                <div>Sub: {{ $v->submitted_at?->diffForHumans() ?? '—' }}</div>
                                @if($v->reviewed_at)
                                    <div class="text-slate-500">Rev: {{ $v->reviewed_at->format('M d H:i') }} by {{ $v->reviewer?->name ?? 'Admin' }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                @if($v->status !== 'VERIFIED')
                                    <form method="POST" action="{{ route('admin.referrals.approve', $v) }}" class="inline-block">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition">
                                            Approve
                                        </button>
                                    </form>
                                @endif

                                @if($v->status !== 'REJECTED')
                                    <button type="button" onclick="document.getElementById('reject-dialog-{{ $v->id }}').showModal()" class="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs transition">
                                        Reject
                                    </button>
                                @endif

                                @if($v->status === 'VERIFIED')
                                    <form method="POST" action="{{ route('admin.referrals.suspend', $v) }}" class="inline-block" onsubmit="return confirm('Suspend scanner access for this user?');">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-rose-900/60 text-slate-300 hover:text-rose-200 text-xs transition">
                                            Suspend
                                        </button>
                                    </form>
                                @endif

                                <!-- Reject Reason Modal -->
                                <dialog id="reject-dialog-{{ $v->id }}" class="p-6 rounded-2xl bg-slate-900 border border-slate-700 text-slate-200 text-left backdrop:bg-slate-950/80 shadow-2xl max-w-md w-full">
                                    <h3 class="text-base font-bold text-white mb-2">Reject Verification Request</h3>
                                    <p class="text-xs text-slate-400 mb-4">User: <strong>{{ $v->user->email }}</strong> (Exness ID: {{ $v->exness_account_id }})</p>
                                    <form method="POST" action="{{ route('admin.referrals.reject', $v) }}" class="space-y-4">
                                        @csrf
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Reason for Rejection</label>
                                            <input type="text" name="reason" required value="Exness Account ID was not registered via official referral link." class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs">
                                        </div>
                                        <div class="flex items-center justify-end space-x-2 pt-2">
                                            <button type="button" onclick="document.getElementById('reject-dialog-{{ $v->id }}').close()" class="px-4 py-2 rounded-xl text-xs bg-slate-800 text-slate-300">Cancel</button>
                                            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-500 text-white">Reject</button>
                                        </div>
                                    </form>
                                </dialog>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No referral verifications match your search query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-800/80">
            {{ $verifications->links() }}
        </div>
    </div>
</div>
@endsection
