<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Administration - ChartSense AI' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#080C16] text-slate-100 font-sans min-h-screen flex antialiased">
    <!-- Admin Sidebar Navigation -->
    <aside class="w-64 bg-slate-950 border-r border-slate-800/80 flex flex-col justify-between shrink-0 hidden md:flex">
        <div class="space-y-6 p-5">
            <!-- Brand -->
            <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-cyan-500 to-indigo-600 p-0.5">
                    <div class="w-full h-full bg-slate-950 rounded-[6px] flex items-center justify-center">
                        <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    </div>
                </div>
                <div>
                    <span class="font-bold text-white tracking-tight">ChartSense</span>
                    <span class="text-[10px] ml-1 px-1.5 py-0.2 rounded font-bold bg-indigo-950 text-indigo-400 border border-indigo-800 uppercase">Admin</span>
                </div>
            </a>

            <!-- Nav Links -->
            <nav class="space-y-1 text-xs font-medium">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2.5 rounded-xl {{ request()->routeIs('admin.dashboard') ? 'bg-cyan-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }} transition">
                    <svg class="w-4 h-4 mr-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                    Overview
                </a>
                <a href="{{ route('admin.referrals') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl {{ request()->routeIs('admin.referrals*') ? 'bg-cyan-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }} transition">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7.5" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                        Referral Queue
                    </div>
                    @php $pending = \App\Models\ReferralVerification::where('status', 'PENDING_VERIFICATION')->count(); @endphp
                    @if($pending > 0)
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-slate-950">
                            {{ $pending }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('admin.users') }}" class="flex items-center px-3 py-2.5 rounded-xl {{ request()->routeIs('admin.users*') ? 'bg-cyan-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }} transition">
                    <svg class="w-4 h-4 mr-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Users Management
                </a>
                <a href="{{ route('admin.scans') }}" class="flex items-center px-3 py-2.5 rounded-xl {{ request()->routeIs('admin.scans*') ? 'bg-cyan-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }} transition">
                    <svg class="w-4 h-4 mr-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                    Scans Monitor
                </a>
                <a href="{{ route('admin.strategies.index') }}" class="flex items-center px-3 py-2.5 rounded-xl {{ request()->routeIs('admin.strategies*') ? 'bg-cyan-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }} transition">
                    <svg class="w-4 h-4 mr-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                    Strategy Studio
                </a>
                <a href="{{ route('admin.learning.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl {{ request()->routeIs('admin.learning*') ? 'bg-cyan-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }} transition">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2.5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                        Learning Center
                    </span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">
                        AI Lab
                    </span>
                </a>
                <a href="{{ route('admin.settings') }}" class="flex items-center px-3 py-2.5 rounded-xl {{ request()->routeIs('admin.settings*') ? 'bg-cyan-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }} transition">
                    <svg class="w-4 h-4 mr-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    AI &amp; System Settings
                </a>
                <a href="{{ route('admin.audit-logs') }}" class="flex items-center px-3 py-2.5 rounded-xl {{ request()->routeIs('admin.audit-logs*') ? 'bg-cyan-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }} transition">
                    <svg class="w-4 h-4 mr-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    Audit Logs
                </a>
                <a href="{{ route('admin.security') }}" class="flex items-center justify-between px-3 py-2.5 rounded-xl {{ request()->routeIs('admin.security*') ? 'bg-cyan-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }} transition">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-2.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        Security Center
                    </span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                        Fortress
                    </span>
                </a>
            </nav>
        </div>

        <!-- Bottom User Info & Exit Link -->
        <div class="p-4 border-t border-slate-800/80 space-y-3">
            <div class="flex items-center space-x-2 text-xs">
                <div class="w-6 h-6 rounded-full bg-cyan-500 text-slate-950 font-bold flex items-center justify-center">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="truncate">
                    <span class="text-white block font-medium truncate">{{ auth()->user()->name }}</span>
                    <span class="text-[10px] text-slate-500">Administrator</span>
                </div>
            </div>
            <a href="{{ route('dashboard') }}" class="flex items-center justify-center w-full px-3 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 transition">
                &larr; Exit to Trader App
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Navbar -->
        <header class="h-16 border-b border-slate-800/80 bg-slate-950/60 backdrop-blur px-6 flex items-center justify-between">
            <div class="flex items-center space-x-3 md:hidden">
                <a href="{{ route('admin.dashboard') }}" class="font-bold text-white">ChartSense Admin</a>
            </div>
            <div class="hidden sm:flex items-center space-x-2 text-xs text-slate-400">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>PostgreSQL 15 Connected &bull; Chartsense DB</span>
            </div>
            <div class="flex items-center space-x-3">
                <a href="https://one.exnessonelink.com/a/l4l2nsdkfb?source=app" target="_blank" rel="noopener noreferrer" class="text-xs text-amber-400 hover:text-amber-300 transition flex items-center space-x-1">
                    <span>Partner Link</span>
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline></svg>
                </a>
                <a href="{{ route('dashboard') }}" class="text-xs text-slate-300 hover:text-white transition">
                    Dashboard
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="p-6 pb-0">
            @if (session('success'))
                <div class="p-4 mb-4 rounded-xl bg-emerald-950/80 border border-emerald-800 text-emerald-200 text-xs font-medium">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('warning'))
                <div class="p-4 mb-4 rounded-xl bg-amber-950/80 border border-amber-800 text-amber-200 text-xs font-medium">
                    {{ session('warning') }}
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 mb-4 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-200 text-xs font-medium">
                    {{ session('error') }}
                </div>
            @endif
        </div>

        <main class="p-6 overflow-y-auto">
            @yield('content')
        </main>
    </div>
</body>
</html>
