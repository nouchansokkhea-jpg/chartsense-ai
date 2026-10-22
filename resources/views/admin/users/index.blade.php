@extends('layouts.admin')

@section('title', 'User Management - ChartSense Admin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Trader Accounts</h1>
            <p class="text-xs text-slate-400 mt-1">Manage user roles, account suspension, and scanner standing.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.users') }}" class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
        <div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search email or name..." class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500">
        </div>
        <div>
            <select name="role" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white focus:outline-none focus:border-cyan-500">
                <option value="">All Roles</option>
                <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>
        <div>
            <select name="account_status" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white focus:outline-none focus:border-cyan-500">
                <option value="">All Account Statuses</option>
                <option value="active" {{ request('account_status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('account_status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
        </div>
        <div class="flex items-center space-x-2">
            <button type="submit" class="w-full py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold transition">
                Filter
            </button>
            <a href="{{ route('admin.users') }}" class="py-2 px-3 rounded-xl bg-slate-950 border border-slate-800 hover:bg-slate-800 text-slate-400 text-center transition">
                Reset
            </a>
        </div>
    </form>

    <div class="rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/60 border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3.5 px-6">Trader Name</th>
                        <th class="py-3.5 px-6">Email Address</th>
                        <th class="py-3.5 px-6">Role</th>
                        <th class="py-3.5 px-6">Referral Status</th>
                        <th class="py-3.5 px-6">Account Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    @foreach($users as $u)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-4 px-6 font-semibold text-white">
                                {{ $u->name }}
                            </td>
                            <td class="py-4 px-6 font-mono">
                                {{ $u->email }}
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $u->role === 'admin' ? 'bg-indigo-950 text-indigo-300 border border-indigo-800' : 'bg-slate-800 text-slate-300' }}">
                                    {{ strtoupper($u->role) }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                @php $ref = $u->referralStatus(); @endphp
                                @if($ref === 'VERIFIED')
                                    <span class="text-emerald-400 font-semibold text-[11px]">✓ Verified</span>
                                @elseif($ref === 'PENDING_VERIFICATION')
                                    <span class="text-amber-400 font-semibold text-[11px]">⏳ Pending</span>
                                @else
                                    <span class="text-slate-500 text-[11px]">Locked ({{ $ref }})</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if($u->account_status === 'active')
                                    <span class="inline-flex items-center text-emerald-400 text-xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-rose-400 text-xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 mr-1.5"></span>
                                        Suspended
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.toggle-role', $u) }}" class="inline-block">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs">
                                            Make {{ $u->role === 'admin' ? 'User' : 'Admin' }}
                                        </button>
                                    </form>

                                    @if($u->account_status === 'active')
                                        <form method="POST" action="{{ route('admin.users.suspend', $u) }}" class="inline-block" onsubmit="return confirm('Suspend user account?');">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs">
                                                Suspend
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.restore', $u) }}" class="inline-block">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs">
                                                Restore
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-slate-500 text-[11px]">Current User</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800/80">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
