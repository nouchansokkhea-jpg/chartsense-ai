<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'ChartSense AI - AI-Powered Trading Chart Scanner' }}</title>
    <meta name="description" content="ChartSense AI: Understand the Chart. Analyze the Market. Trade with a Plan. AI-assisted market structure, entry zones, stop loss, and take profit plans with Exness referral verification.">

    <!-- OpenGraph Meta -->
    <meta property="og:title" content="{{ $title ?? 'ChartSense AI - AI-Powered Trading Chart Scanner' }}">
    <meta property="og:description" content="Understand the Chart. Analyze the Market. Trade with a Plan. Institutional SMC, price action, and technical analysis engine.">
    <meta property="og:type" content="website">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#0B0F19] text-slate-100 font-sans min-h-screen flex flex-col selection:bg-cyan-500 selection:text-slate-950 antialiased">
    <!-- Announcement Bar -->
    <div class="bg-gradient-to-r from-cyan-950 via-slate-900 to-blue-950 border-b border-cyan-900/40 text-xs py-2 px-4 text-center text-slate-300 flex items-center justify-center space-x-2">
        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 uppercase tracking-wider">Zero Billing Access</span>
        <span>100% Free Scanner unlocked via official Exness Referral.</span>
        <a href="https://one.exnessonelink.com/a/l4l2nsdkfb?source=app" target="_blank" rel="noopener noreferrer" class="font-semibold text-cyan-400 hover:text-cyan-300 underline underline-offset-2 ml-1 inline-flex items-center space-x-1">
            <span>Join Exness Partner</span>
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline></svg>
        </a>
    </div>

    <!-- Navigation Header -->
    @include('layouts.navigation')

    <!-- Flash Alerts -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full mt-4">
        @if ($errors->any())
            <div class="p-4 mb-4 rounded-xl bg-rose-950/90 border border-rose-800 text-rose-200 flex items-start space-x-3 shadow-lg">
                <svg class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <div class="text-sm">
                    <p class="font-bold mb-1">Please check your inputs:</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="p-4 mb-4 rounded-xl bg-emerald-950/80 border border-emerald-800 text-emerald-200 flex items-start space-x-3 shadow-lg">
                <svg class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <div class="text-sm font-medium">{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 mb-4 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-200 flex items-start space-x-3 shadow-lg">
                <svg class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                <div class="text-sm font-medium">{{ session('error') }}</div>
            </div>
        @endif

        @if (session('warning'))
            <div class="p-4 mb-4 rounded-xl bg-amber-950/80 border border-amber-800 text-amber-200 flex items-start space-x-3 shadow-lg">
                <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                <div class="text-sm font-medium">{{ session('warning') }}</div>
            </div>
        @endif

        @if (session('info'))
            <div class="p-4 mb-4 rounded-xl bg-cyan-950/80 border border-cyan-800 text-cyan-200 flex items-start space-x-3 shadow-lg">
                <svg class="w-5 h-5 text-cyan-400 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <div class="text-sm font-medium">{{ session('info') }}</div>
            </div>
        @endif
    </div>

    <!-- Main Content Area -->
    <main class="flex-grow">
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </main>

    <!-- Footer -->
    @include('layouts.footer')
</body>
</html>
