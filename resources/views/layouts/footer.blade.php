<footer class="border-t border-slate-800/80 bg-[#070A12] text-slate-400 text-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <!-- Top Section: Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 lg:gap-12 pb-12 border-b border-slate-800/60">
            <!-- Brand & Mission -->
            <div class="md:col-span-2 space-y-4">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-cyan-600 via-blue-600 to-indigo-500 p-0.5">
                        <div class="w-full h-full bg-[#0B0F19] rounded-[6px] flex items-center justify-center">
                            <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v4M6 17v4M4 7h4v10H4z"/><path d="M18 5v2M18 19v2M16 7h4v12h-4z"/></svg>
                        </div>
                    </div>
                    <span class="text-lg font-bold text-white">ChartSense <span class="text-cyan-400">AI</span></span>
                </div>
                <p class="text-slate-400 text-sm leading-relaxed max-w-md">
                    "Understand the Chart. Analyze the Market. Trade with a Plan."
                    An AI-powered institutional market-structure scanner combining deterministic technical analysis with multi-modal reasoning.
                </p>

                <!-- Zero Purchases Guarantee Badge -->
                <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-emerald-950/40 border border-emerald-800/60 text-emerald-300 text-xs font-medium">
                    <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
                    <span>100% Free Access via Exness Referral &bull; No Subscriptions &bull; No In-App Billing</span>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="space-y-3">
                <h4 class="text-xs font-semibold text-slate-200 tracking-wider uppercase">Platform</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ url('/') }}" class="hover:text-cyan-400 transition">Home</a></li>
                    <li><a href="{{ url('/#features') }}" class="hover:text-cyan-400 transition">Market Features</a></li>
                    <li><a href="{{ url('/#how-it-works') }}" class="hover:text-cyan-400 transition">How It Works</a></li>
                    <li><a href="{{ url('/referral-access') }}" class="hover:text-cyan-400 transition">Exness Referral Gate</a></li>
                    <li><a href="{{ url('/scanner') }}" class="hover:text-cyan-400 transition">AI Chart Scanner</a></li>
                </ul>
            </div>

            <!-- Legal & Official Partner -->
            <div class="space-y-3">
                <h4 class="text-xs font-semibold text-slate-200 tracking-wider uppercase">Official Partner & Legal</h4>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="https://one.exnessonelink.com/a/l4l2nsdkfb?source=app" target="_blank" rel="noopener noreferrer" class="text-amber-400 hover:text-amber-300 flex items-center space-x-1.5 transition">
                            <span>Official Exness Portal</span>
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        </a>
                    </li>
                    <li><a href="{{ url('/terms') }}" class="hover:text-cyan-400 transition">Terms of Service</a></li>
                    <li><a href="{{ url('/privacy') }}" class="hover:text-cyan-400 transition">Privacy Policy</a></li>
                    <li><a href="{{ url('/risk-disclosure') }}" class="hover:text-cyan-400 transition">Risk Disclosure</a></li>
                </ul>
            </div>
        </div>

        <!-- High-Impact Risk Warning -->
        <div class="mt-8 p-4 rounded-xl bg-slate-900/60 border border-slate-800 text-xs text-slate-400 leading-relaxed space-y-2">
            <div class="flex items-center space-x-2 text-amber-400 font-semibold uppercase tracking-wider text-[11px]">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                <span>Financial Risk & Operational Disclaimer</span>
            </div>
            <p>
                <strong>Trading financial contracts (Forex, CFDs, Gold, Commodities, and Crypto) involves substantial risk of loss and is not suitable for every investor.</strong>
                ChartSense AI is an algorithmic education and market-structure analytical assistant designed to assist manual traders with risk management and structured setup formulation. It does not provide automated trading, guaranteed profits, or personalized financial advice.
            </p>
            <p class="text-slate-500">
                Past performance of technical indicators or price action scenarios is not indicative of future market returns. Always practice disciplined risk management, never risk money you cannot afford to lose, and conduct your own independent market validation.
            </p>
        </div>

        <!-- Bottom Copyright -->
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 space-y-4 sm:space-y-0">
            <div>
                &copy; {{ date('Y') }} ChartSense AI. All rights reserved. Powered by Laravel &amp; PostgreSQL.
            </div>
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 border border-slate-700">
                    PostgreSQL 15 Managed
                </span>
                <span>Referral ID: <code class="text-cyan-400">l4l2nsdkfb</code></span>
            </div>
        </div>
    </div>
</footer>
