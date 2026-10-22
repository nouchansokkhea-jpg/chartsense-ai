@extends('layouts.app')

@section('title', 'AI Chart Scanner - ChartSense AI')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{
    tab: 'screenshot',
    symbol: 'AUTO',
    timeframe: 'AUTO',
    strategy: 'comprehensive',
    imagePreview: null,
    isSubmitting: false,
    handleFileDrop(e) {
        const file = e.dataTransfer ? e.dataTransfer.files[0] : (e.target.files ? e.target.files[0] : null);
        if (!file) return;

        const input = document.getElementById('chart_image');
        if (e.dataTransfer && input) {
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
        }

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (ev) => this.imagePreview = ev.target.result;
            reader.readAsDataURL(file);
        }
    },
    removeImage() {
        this.imagePreview = null;
        const input = document.getElementById('chart_image');
        if (input) input.value = '';
    }
}">
    <!-- Header -->
    <div class="space-y-3 mb-8">
        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-cyan-950/60 border border-cyan-800/80 text-xs text-cyan-300">
            <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
            <span>AI Multi-Modal Analysis Engine</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">AI Trading Chart Scanner</h1>
        <p class="text-sm text-slate-400 max-w-2xl">
            Upload chart screenshots from TradingView, MT4/MT5, or supply real OHLCV market data. Our dual-engine extracts price action, market structure, and formulates high-probability trade setups.
        </p>
    </div>

    <!-- Scanner Workflow Form -->
    <form method="POST" action="{{ route('scans.store') }}" enctype="multipart/form-data" @submit="isSubmitting = true" class="space-y-8">
        @csrf
        <input type="hidden" name="input_type" :value="tab">

        <!-- SECTION 1: INPUT METHOD SELECTOR TABS -->
        <div class="p-6 sm:p-8 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-6">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-cyan-400">Step 1</span>
                    <h2 class="text-lg font-bold text-white">Provide Chart or Market Data</h2>
                </div>
                <div class="flex rounded-xl bg-slate-950 p-1 border border-slate-800 text-xs">
                    <button type="button" @click="tab = 'screenshot'" :class="tab === 'screenshot' ? 'bg-cyan-500 text-slate-950 font-bold shadow' : 'text-slate-400 hover:text-white'" class="px-3.5 py-1.5 rounded-lg transition">
                        Screenshot
                    </button>
                    <button type="button" @click="tab = 'csv'" :class="tab === 'csv' ? 'bg-cyan-500 text-slate-950 font-bold shadow' : 'text-slate-400 hover:text-white'" class="px-3.5 py-1.5 rounded-lg transition">
                        Real CSV Data
                    </button>
                    <button type="button" @click="tab = 'raw_text'" :class="tab === 'raw_text' ? 'bg-cyan-500 text-slate-950 font-bold shadow' : 'text-slate-400 hover:text-white'" class="px-3.5 py-1.5 rounded-lg transition">
                        Paste OHLCV
                    </button>
                </div>
            </div>

            <!-- TAB A: SCREENSHOT UPLOAD -->
            <div x-show="tab === 'screenshot'" class="space-y-4">
                <div
                    @dragover.prevent="$el.classList.add('border-cyan-500', 'bg-cyan-950/10')"
                    @dragleave.prevent="$el.classList.remove('border-cyan-500', 'bg-cyan-950/10')"
                    @drop.prevent="$el.classList.remove('border-cyan-500', 'bg-cyan-950/10'); handleFileDrop($event)"
                    class="border-2 border-dashed border-slate-700/80 rounded-2xl p-8 text-center hover:border-slate-600 transition relative"
                >
                    <!-- Permanent input in DOM so files are never lost on preview toggle -->
                    <input id="chart_image" name="chart_image" type="file" accept="image/png,image/jpeg,image/webp" @change="handleFileDrop($event)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" :class="imagePreview ? 'pointer-events-none' : ''">

                    <!-- Default Prompt when no image selected -->
                    <div x-show="!imagePreview" class="space-y-3">
                        <div class="w-14 h-14 rounded-2xl bg-slate-800/80 border border-slate-700 mx-auto flex items-center justify-center text-cyan-400">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-white">Drag &amp; drop your chart screenshot here, or <span class="text-cyan-400 underline">browse</span></p>
                            <p class="text-xs text-slate-400">Supports PNG, JPG, JPEG, or WebP from TradingView, MT4, MT5 (Max 15MB)</p>
                        </div>
                    </div>

                    <!-- Preview of dropped/selected image -->
                    <div x-show="imagePreview" style="display: none;" class="space-y-4">
                        <div class="relative max-h-80 overflow-hidden rounded-xl border border-slate-700 mx-auto max-w-xl">
                            <img :src="imagePreview" alt="Chart Preview" class="w-full h-auto object-contain">
                            <button type="button" @click.stop="removeImage()" class="absolute top-3 right-3 p-1.5 rounded-lg bg-slate-900/90 text-rose-400 hover:bg-rose-950/80 border border-slate-700 transition z-20">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </div>
                        <p class="text-xs text-emerald-400 font-semibold">✓ Chart screenshot ready for multi-modal analysis</p>
                    </div>
                </div>
                @error('chart_image')
                    <p class="text-xs text-rose-400 font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- TAB B: REAL CSV UPLOAD -->
            <div x-show="tab === 'csv'" style="display: none;" class="space-y-4">
                <div class="border-2 border-dashed border-slate-700/80 rounded-2xl p-8 text-center hover:border-slate-600 transition relative">
                    <div class="w-14 h-14 rounded-2xl bg-slate-800/80 border border-slate-700 mx-auto flex items-center justify-center text-blue-400 mb-3">
                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    </div>
                    <p class="text-sm font-semibold text-white">Select a CSV file with historical candle records</p>
                    <p class="text-xs text-slate-400 mt-1">Requires columns: <code class="text-cyan-400">timestamp</code>, <code class="text-cyan-400">open</code>, <code class="text-cyan-400">high</code>, <code class="text-cyan-400">low</code>, <code class="text-cyan-400">close</code>, optional <code class="text-cyan-400">volume</code></p>
                    <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt" class="mt-4 block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-cyan-500 file:text-slate-950 hover:file:bg-cyan-400 cursor-pointer">
                </div>
            </div>

            <!-- TAB C: PASTE RAW OHLCV -->
            <div x-show="tab === 'raw_text'" style="display: none;" class="space-y-4">
                <label for="raw_text" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Paste Candlestick Data (CSV or JSON)</label>
                <textarea id="raw_text" name="raw_text" rows="5" placeholder="date,open,high,low,close,volume&#10;2026-10-22 14:00,2730.50,2735.00,2728.00,2734.20,1542&#10;..." class="w-full px-4 py-3 rounded-xl bg-slate-950/70 border border-slate-700 text-white font-mono text-xs focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition"></textarea>
            </div>
        </div>

        <!-- SECTION 2: CONFIGURATION & PARAMETERS -->
        <div class="p-6 sm:p-8 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-6">
            <div class="border-b border-slate-800/80 pb-4">
                <span class="text-xs font-semibold uppercase tracking-wider text-cyan-400">Step 2</span>
                <h2 class="text-lg font-bold text-white">Configure Analysis Parameters</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Symbol -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label for="symbol" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Trading Symbol</label>
                        <span class="text-[10px] text-cyan-400 font-semibold uppercase">AI Auto-Scan</span>
                    </div>
                    <input id="symbol" name="symbol" type="text" x-model="symbol" placeholder="AUTO (e.g. XAUUSD, BTCUSD, EURUSD)" class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white font-mono text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 uppercase transition">
                    <!-- Quick Chips -->
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        <button type="button" @click="symbol = 'AUTO'" :class="symbol === 'AUTO' ? 'bg-cyan-500 text-slate-950 font-bold' : 'bg-slate-800 hover:bg-slate-700 text-cyan-400'" class="px-2 py-0.5 rounded text-[11px] font-mono transition">⚡ AUTO</button>
                        <button type="button" @click="symbol = 'XAUUSD'" class="px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono">XAUUSD</button>
                        <button type="button" @click="symbol = 'BTCUSD'" class="px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono">BTCUSD</button>
                        <button type="button" @click="symbol = 'EURUSD'" class="px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono">EURUSD</button>
                        <button type="button" @click="symbol = 'GBPUSD'" class="px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono">GBPUSD</button>
                        <button type="button" @click="symbol = 'USDJPY'" class="px-2 py-0.5 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono">USDJPY</button>
                    </div>
                    <p class="text-[11px] text-slate-400">AI automatically scans the pair name from the screenshot header if left on <code class="text-cyan-400 font-mono">AUTO</code>.</p>
                </div>

                <!-- Timeframe -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label for="timeframe" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Chart Timeframe</label>
                        <span class="text-[10px] text-cyan-400 font-semibold uppercase">Auto-Detected</span>
                    </div>
                    <select id="timeframe" name="timeframe" x-model="timeframe" class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                        <option value="AUTO" selected>⚡ Auto-Detect Timeframe (from Chart)</option>
                        <option value="M1">M1 (1 Minute - Ultra Scalp)</option>
                        <option value="M5">M5 (5 Minutes - Scalp Entry)</option>
                        <option value="M15">M15 (15 Minutes - Intraday Setup)</option>
                        <option value="M30">M30 (30 Minutes)</option>
                        <option value="H1">H1 (1 Hour - Trend &amp; Setup)</option>
                        <option value="H4">H4 (4 Hours - Swing Context)</option>
                        <option value="D1">D1 (Daily - Macro Structure)</option>
                    </select>
                    <p class="text-[11px] text-slate-400">Scans time tags (15m, 1h, 4h, D) directly from chart scale and watermark.</p>
                </div>

                <!-- Strategy -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label for="strategy" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Analysis Strategy</label>
                        <span class="text-[10px] text-emerald-400 font-semibold uppercase">Multi-Confluence</span>
                    </div>
                    <select id="strategy" name="strategy" x-model="strategy" class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">
                        <option value="comprehensive" selected>⚡ Autonomous Multi-Strategy AI (SMC + Price Action + Trend + Fibonacci)</option>
                        <option value="smc">Smart Money Concepts &amp; ICT (BOS/CHOCH/FVG)</option>
                        <option value="price_action">Classic Candlestick &amp; Price Action Dynamics</option>
                        <option value="trend_following">Market Structure &amp; Dynamic Trendlines</option>
                        <option value="support_resistance">Key Support &amp; Resistance Retests</option>
                    </select>
                    <p class="text-[11px] text-slate-400">Evaluates SMC, Price Action, Trend, and Fibonacci confluences simultaneously.</p>
                </div>
            </div>

            <!-- Live Real News Intelligence Notice Banner -->
            <div class="p-3.5 rounded-xl bg-cyan-950/30 border border-cyan-800/40 flex items-start gap-3 text-xs text-slate-300">
                <span class="text-cyan-400 text-base">🌐</span>
                <div>
                    <span class="font-bold text-white block">Autonomous Macro News &amp; World Catalysts Ingestion</span>
                    <span class="text-slate-400">ChartSense AI continuously monitors live 24/7 global financial news feeds (ForexLive &amp; CoinDesk) to detect central bank rate shifts, inflation data releases, and high-impact macro volatility events affecting your pair.</span>
                </div>
            </div>

            <!-- Optional Trader Notes / Confluence Guidance -->
            <div class="space-y-2 pt-2 border-t border-slate-800/80">
                <label for="notes" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Trader Notes &amp; Confluence Context (Optional)</label>
                <textarea id="notes" name="notes" rows="2" placeholder="e.g. Price rejecting 1H supply zone around 4211, targeting 4065 FVG, seeking short confirmation..." class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white placeholder-slate-500 text-xs focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition"></textarea>
                <p class="text-[11px] text-slate-500">Provide any specific broker levels, news sentiment, or chart cues you want the AI scanner to consider.</p>
            </div>
        </div>

        <!-- SECTION 3: SUBMIT ACTION BUTTON -->
        <div class="pt-2">
            <button
                type="submit"
                :disabled="isSubmitting"
                class="w-full py-4 px-6 rounded-2xl text-base font-bold text-slate-950 bg-gradient-to-r from-cyan-400 via-blue-500 to-indigo-500 hover:from-cyan-300 hover:to-blue-400 shadow-2xl shadow-cyan-500/25 hover:scale-[1.01] active:scale-[0.99] transition duration-200 flex items-center justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <template x-if="!isSubmitting">
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
                        <span>Analyze Chart &bull; Generate Trading Plan</span>
                    </span>
                </template>
                <template x-if="isSubmitting">
                    <span class="flex items-center space-x-2">
                        <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                        <span>Validating Data &bull; Calculating Indicators &bull; Generating Plan...</span>
                    </span>
                </template>
            </button>
        </div>
    </form>
</div>
@endsection
