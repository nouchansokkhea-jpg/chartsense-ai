@extends('layouts.app')

@section('title', 'Risk Disclosure - ChartSense AI')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="p-8 sm:p-10 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-6 text-slate-300 text-sm leading-relaxed">
        <div class="border-b border-slate-800 pb-4">
            <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">Mandatory Financial Warning</span>
            <h1 class="text-3xl font-extrabold text-white mt-1">Risk Disclosure Statement</h1>
            <p class="text-xs text-slate-500 mt-1">Last Updated: October 2026</p>
        </div>

        <div class="p-4 rounded-xl bg-amber-950/30 border border-amber-800/60 text-amber-200 text-xs">
            <strong>Important Notice:</strong> Trading foreign exchange (Forex), commodities (such as Gold/XAUUSD), equity indices, cryptocurrencies, and contracts for difference (CFDs) carries a high level of risk and may not be suitable for all traders or investors.
        </div>

        <section class="space-y-3">
            <h2 class="text-base font-bold text-white">1. Nature of the Service</h2>
            <p>
                ChartSense AI is an artificial intelligence-assisted educational and chart-structure analytical software tool. The proposed trade setups, including direction (BUY, SELL, WAIT), entry levels, stop loss, and take profit targets, represent mathematical models and algorithmic scenario assessments based on provided user data.
            </p>
            <p>
                <strong>ChartSense AI is NOT a registered financial advisor, broker, or asset management firm.</strong> The service does NOT execute trades, does NOT manage funds, and does NOT provide guaranteed signals or investment advice.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-bold text-white">2. High Degree of Risk and Leverage</h2>
            <p>
                High leverage can work against you as well as for you. Before deciding to trade foreign exchange or any financial market, you should carefully consider your investment objectives, level of experience, and risk appetite. The possibility exists that you could sustain a loss of some or all of your initial investment.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-bold text-white">3. Uncertainty and Market Volatility</h2>
            <p>
                Financial markets are subject to sudden macroeconomic announcements, liquidity gaps, slippage, and volatility spikes. AI models interpret historical patterns and price structures, but cannot anticipate unpredictable world events or guarantee that price will respect technical support or resistance levels.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-bold text-white">4. Exness Partnership & Referral Access</h2>
            <p>
                ChartSense AI does not charge subscription fees, sell access keys, or collect payment receipts. Access is provided to eligible users who register through our official Exness partner link (<a href="https://one.exnessonelink.com/a/l4l2nsdkfb?source=app" class="text-cyan-400 underline" target="_blank" rel="noopener">https://one.exnessonelink.com/a/l4l2nsdkfb?source=app</a>) and complete verification. Trading through Exness or any brokerage carries financial risk governed by that broker's regulatory terms.
            </p>
        </section>

        <div class="pt-4 border-t border-slate-800 flex justify-between items-center text-xs text-slate-500">
            <a href="{{ url('/') }}" class="text-cyan-400 hover:text-cyan-300">&larr; Back to Home</a>
            <a href="{{ url('/terms') }}" class="text-slate-400 hover:text-slate-300">View Terms of Service &rarr;</a>
        </div>
    </div>
</div>
@endsection
