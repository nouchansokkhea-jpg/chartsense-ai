@extends('layouts.app')

@section('title', 'ChartSense AI - AI-Powered Trading Chart Scanner & Market Structure Analysis')

@section('content')
<div class="relative overflow-hidden">
    <!-- Ambient Background Lighting -->
    <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-gradient-to-b from-cyan-500/15 via-blue-600/10 to-transparent blur-3xl pointer-events-none -z-10"></div>
    <div class="absolute top-1/3 -right-40 w-[500px] h-[500px] bg-indigo-500/10 blur-3xl pointer-events-none -z-10"></div>

    <!-- HERO SECTION -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-20 lg:pt-24 lg:pb-28">
        <div class="text-center max-w-3xl mx-auto space-y-6">
            <!-- Badge -->
            <div class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-slate-900/90 border border-slate-700/80 text-xs text-slate-300 shadow-xl backdrop-blur">
                <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                <span class="font-medium">AI-Powered Trading Chart Analysis &bull; Made Simple</span>
            </div>

            <!-- Main Headline -->
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-[1.15]">
                Understand the Chart.<br />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-blue-400 to-indigo-300">
                    Analyze the Market.
                </span><br />
                Trade with a Plan.
            </h1>

            <!-- Description -->
            <p class="text-base sm:text-lg text-slate-300 leading-relaxed font-normal max-w-2xl mx-auto">
                Upload your trading chart screenshots or provide real OHLCV market data. ChartSense AI evaluates market structure, institutional liquidity, support &amp; resistance, and generates structured trading plans with clear Entry, SL, and TP targets.
            </p>

            <!-- Zero In-App Purchases Callout -->
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 text-left max-w-xl mx-auto shadow-2xl flex items-start space-x-3.5">
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
                <div class="text-xs space-y-1">
                    <div class="font-semibold text-slate-200 flex items-center space-x-2">
                        <span>No Subscriptions &bull; No In-App Billing Required</span>
                        <span class="px-1.5 py-0.2 rounded text-[10px] bg-emerald-950 text-emerald-400 border border-emerald-800 font-bold">100% EXNESS SPONSORED</span>
                    </div>
                    <p class="text-slate-400 leading-normal">
                        To access the scanner, simply join Exness using our official referral link and submit your trading account for quick verification. No credit card or scan credits needed.
                    </p>
                </div>
            </div>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5 pt-2">
                @auth
                    @if(auth()->user()->canAccessScanner())
                        <a href="{{ url('/scanner') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 rounded-xl text-base font-semibold text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-xl shadow-cyan-500/20 hover:scale-[1.02] transition duration-200">
                            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                            Launch AI Scanner
                        </a>
                    @else
                        <a href="{{ url('/referral-access') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 rounded-xl text-base font-semibold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 shadow-xl shadow-amber-500/20 hover:scale-[1.02] transition duration-200">
                            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            Verify Exness Account to Unlock
                        </a>
                    @endif
                @else
                    <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 rounded-xl text-base font-semibold text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-xl shadow-cyan-500/25 hover:scale-[1.02] transition duration-200">
                        Get Started Free
                    </a>
                    <button type="button" onclick="if(window.loginWithFirebaseGoogle){ window.loginWithFirebaseGoogle(this); } else { window.location.href='{{ route('auth.google') }}'; }" style="background-color: #ffffff !important; color: #0f172a !important;" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2.5 px-6 py-3.5 rounded-xl font-bold text-sm shadow-xl shadow-white/10 hover:opacity-95 transition duration-200 border border-slate-200 cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                        <span style="color: #0f172a !important;">Continue with Google</span>
                    </button>
                    <a href="https://one.exnessonelink.com/a/l4l2nsdkfb?source=app" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 rounded-xl text-base font-semibold text-slate-200 bg-slate-900 border border-slate-700 hover:bg-slate-800 hover:text-white transition duration-200">
                        <span>Join Exness Official</span>
                        <svg class="w-4 h-4 ml-2 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline></svg>
                    </a>
                @endauth
            </div>
        </div>

        <!-- LIVE ANALYSIS MOCKUP / SHOWCASE -->
        <div class="mt-16 lg:mt-20 max-w-5xl mx-auto">
            <div class="rounded-2xl border border-slate-800/80 bg-slate-900/60 p-2 sm:p-4 shadow-2xl backdrop-blur-xl">
                <!-- Mock Header Bar -->
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-3 px-3">
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-full bg-rose-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                        <span class="text-xs text-slate-400 font-mono pl-3">ChartSense Scanner Report &bull; XAUUSD (Gold) H1</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">
                            VALID SETUP
                        </span>
                        <span class="text-xs text-slate-500">Deterministically Verified</span>
                    </div>
                </div>

                <!-- Structured Setup Preview Body -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 p-4 sm:p-6">
                    <!-- Left: Direction & Main Plan (5 cols) -->
                    <div class="lg:col-span-5 space-y-4">
                        <!-- Direction Card -->
                        <div class="p-5 rounded-xl bg-gradient-to-br from-emerald-950/40 via-slate-900 to-slate-900 border border-emerald-500/30">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Trading Decision</span>
                                <span class="text-xs font-semibold text-emerald-400 bg-emerald-950 px-2 py-0.5 rounded border border-emerald-800">High Confidence</span>
                            </div>
                            <div class="mt-3 flex items-baseline space-x-3">
                                <h3 class="text-3xl font-extrabold text-emerald-400 tracking-tight">BUY SETUP</h3>
                                <span class="text-sm font-medium text-slate-300">Bullish Order Block</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-400 leading-relaxed">
                                Market structure formed a M15 CHOCH and retested a 4H Bullish FVG with institutional volume confirmation.
                            </p>
                        </div>

                        <!-- Trade Metrics Grid -->
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800">
                                <span class="text-slate-400 block mb-1">Proposed Entry Zone</span>
                                <span class="text-base font-bold text-white font-mono">2,734.50 &ndash; 2,736.00</span>
                                <span class="text-[11px] text-cyan-400 block mt-0.5">Retest Limit Zone</span>
                            </div>
                            <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800">
                                <span class="text-slate-400 block mb-1">Stop Loss (SL)</span>
                                <span class="text-base font-bold text-rose-400 font-mono">2,726.80</span>
                                <span class="text-[11px] text-rose-400/80 block mt-0.5">-8.20 pts Invalidation</span>
                            </div>
                        </div>

                        <!-- Risk to Reward Summary -->
                        <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="text-xs text-slate-400 block">Risk-to-Reward Ratio</span>
                                <span class="text-lg font-bold text-cyan-400 font-mono">1 : 3.20 Avg</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-slate-400 block">Trend Alignment</span>
                                <span class="text-xs font-semibold text-emerald-400">H4 &bull; H1 Bullish</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Take-Profit Targets & Confluence (7 cols) -->
                    <div class="lg:col-span-7 space-y-4">
                        <!-- Take Profit Targets Table -->
                        <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 space-y-2.5">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Take-Profit Targets</span>
                            <div class="space-y-2 text-xs">
                                <div class="flex items-center justify-between p-2 rounded-lg bg-slate-800/40 border border-slate-800">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-1.5 py-0.5 rounded font-bold text-[10px] bg-cyan-950 text-cyan-300 border border-cyan-800">TP1</span>
                                        <span class="font-mono text-white font-semibold">2,748.00</span>
                                    </div>
                                    <span class="text-slate-400">+13.50 pts (R:R 1:1.64)</span>
                                    <span class="text-slate-500 hidden sm:inline">Internal Liquidity</span>
                                </div>
                                <div class="flex items-center justify-between p-2 rounded-lg bg-slate-800/40 border border-slate-800">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-1.5 py-0.5 rounded font-bold text-[10px] bg-cyan-950 text-cyan-300 border border-cyan-800">TP2</span>
                                        <span class="font-mono text-white font-semibold">2,760.00</span>
                                    </div>
                                    <span class="text-slate-400">+25.50 pts (R:R 1:3.10)</span>
                                    <span class="text-slate-500 hidden sm:inline">Prior Daily Equal Highs</span>
                                </div>
                                <div class="flex items-center justify-between p-2 rounded-lg bg-slate-800/40 border border-slate-800">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-1.5 py-0.5 rounded font-bold text-[10px] bg-cyan-950 text-cyan-300 border border-cyan-800">TP3</span>
                                        <span class="font-mono text-white font-semibold">2,778.00</span>
                                    </div>
                                    <span class="text-slate-400">+43.50 pts (R:R 1:5.30)</span>
                                    <span class="text-slate-500 hidden sm:inline">Major Resistance Extension</span>
                                </div>
                            </div>
                        </div>

                        <!-- Technical Confluence & Invalidation -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 space-y-1">
                                <span class="font-semibold text-emerald-400 flex items-center space-x-1">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <span>Supporting Confluence</span>
                                </span>
                                <ul class="text-slate-400 space-y-1 pl-4 list-disc text-[11px]">
                                    <li>H4 Bullish Structure Break (BOS)</li>
                                    <li>M15 RSI Bullish Momentum shift</li>
                                    <li>Unmitigated 15m Fair Value Gap</li>
                                </ul>
                            </div>
                            <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 space-y-1">
                                <span class="font-semibold text-amber-400 flex items-center space-x-1">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line></svg>
                                    <span>Setup Invalidation Level</span>
                                </span>
                                <p class="text-slate-400 text-[11px] leading-relaxed">
                                    Any 15-minute close below <code class="text-rose-400 font-mono">2,726.80</code> completely invalidates the bullish order flow.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS SECTION -->
    <section id="how-it-works" class="py-20 border-t border-slate-800/80 bg-slate-950/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto space-y-3 mb-16">
                <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">Simple 3-Step Verification</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">How to Get 100% Free Access</h2>
                <p class="text-sm text-slate-400">
                    We never charge fees, sell subscription keys, or ask for payment receipts. Access is unlocked exclusively through our official Exness referral relationship.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4 hover:border-slate-700 transition">
                    <div class="w-12 h-12 rounded-xl bg-cyan-950 border border-cyan-800 flex items-center justify-center text-cyan-400 font-bold text-lg">
                        1
                    </div>
                    <h3 class="text-lg font-bold text-white">Create Your Account</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Register your ChartSense AI profile with your name and email. No billing details or credit card information is ever requested.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="p-6 rounded-2xl bg-slate-900/60 border border-amber-500/30 space-y-4 hover:border-amber-500/50 transition">
                    <div class="w-12 h-12 rounded-xl bg-amber-950 border border-amber-800 flex items-center justify-center text-amber-400 font-bold text-lg">
                        2
                    </div>
                    <h3 class="text-lg font-bold text-white">Join Exness via Partner Link</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Open your Exness trading account using our official partner link (<code class="text-amber-400">l4l2nsdkfb</code>) and submit your Exness account ID on the verification page.
                    </p>
                    <a href="https://one.exnessonelink.com/a/l4l2nsdkfb?source=app" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-xs font-semibold text-amber-400 hover:text-amber-300">
                        <span>Open Exness Official</span>
                        <svg class="w-3.5 h-3.5 ml-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline></svg>
                    </a>
                </div>

                <!-- Step 3 -->
                <div class="p-6 rounded-2xl bg-slate-900/60 border border-emerald-500/30 space-y-4 hover:border-emerald-500/50 transition">
                    <div class="w-12 h-12 rounded-xl bg-emerald-950 border border-emerald-800 flex items-center justify-center text-emerald-400 font-bold text-lg">
                        3
                    </div>
                    <h3 class="text-lg font-bold text-white">Scan &amp; Trade with a Plan</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Once your referral verification is confirmed by our administrators or verified partner integration, scanner access is permanently unlocked for your account.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- KEY FEATURES SECTION -->
    <section id="features" class="py-20 border-t border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto space-y-3 mb-16">
                <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">Engine Capabilities</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-white tracking-tight">Built for Serious Trading Discipline</h2>
                <p class="text-sm text-slate-400">
                    Never enter a position on intuition alone. ChartSense AI pairs deterministic mathematical calculations with advanced vision analysis.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-950 text-cyan-400 flex items-center justify-center">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    </div>
                    <h3 class="text-base font-bold text-white">Dual Input Methods</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Upload chart screenshots from TradingView or MetaTrader 4/5, or upload structured numerical CSV records with timestamps and OHLCV bars.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-950 text-emerald-400 flex items-center justify-center">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                    </div>
                    <h3 class="text-base font-bold text-white">Smart Money &amp; Price Action</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Identifies Break of Structure (BOS), Change of Character (CHOCH), Order Blocks, Fair Value Gaps, and liquidity sweeps with technical evidence.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-950 text-blue-400 flex items-center justify-center">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    </div>
                    <h3 class="text-base font-bold text-white">Multi-Timeframe Confluence</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Align high-timeframe context (H4/D1) with entry-timeframe confirmation (M15/M5) to avoid counter-trend traps and low-probability trades.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-950 text-amber-400 flex items-center justify-center">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </div>
                    <h3 class="text-base font-bold text-white">Unforced Signals: WAIT Allowed</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Unlike generic bots that force bad signals, ChartSense AI returns WAIT or NO VALID SETUP when market conditions lack clean risk-to-reward edge.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-950 text-indigo-400 flex items-center justify-center">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                    </div>
                    <h3 class="text-base font-bold text-white">PostgreSQL &amp; pgAdmin 4</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Enterprise relational architecture powered by PostgreSQL, fully manageable via pgAdmin 4, with complete audit logs and scan history isolation.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-950 text-purple-400 flex items-center justify-center">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    </div>
                    <h3 class="text-base font-bold text-white">Downloadable Analysis Reports</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Export structured PDF summaries and CSV data provenance for trading journals, personal reviews, and post-trade accountability.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- BOTTOM CTA BANNER -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="p-8 sm:p-12 rounded-3xl bg-gradient-to-r from-cyan-950 via-slate-900 to-blue-950 border border-cyan-800/50 text-center space-y-6 shadow-2xl relative overflow-hidden">
            <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                Ready to Analyze Your Next Trading Setup?
            </h2>
            <p class="text-sm text-slate-300 max-w-xl mx-auto leading-relaxed">
                Join our community of disciplined traders. Connect via the official Exness referral portal to unlock full chart scanning access today.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                <a href="{{ Route::has('register') ? route('register') : url('/register') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl text-base font-semibold text-slate-950 bg-cyan-400 hover:bg-cyan-300 shadow-xl shadow-cyan-500/20 transition">
                    Create Free Account
                </a>
                <a href="https://one.exnessonelink.com/a/l4l2nsdkfb?source=app" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto px-7 py-3.5 rounded-xl text-base font-semibold text-amber-300 bg-amber-500/10 border border-amber-500/30 hover:bg-amber-500/20 transition">
                    Join Exness Partner
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
