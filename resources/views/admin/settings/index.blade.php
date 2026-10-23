@extends('layouts.admin')

@section('title', 'AI & System Settings - ChartSense Admin')

@section('content')
<div class="space-y-6 max-w-5xl">
    <!-- Header -->
    <div class="border-b border-slate-800 pb-4">
        <h1 class="text-2xl font-extrabold text-white tracking-tight">AI & System Settings</h1>
        <p class="text-xs text-slate-400 mt-1">Configure artificial intelligence providers, model parameters, and Exness referral gateway rules.</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf

        <!-- Exness Referral Configuration -->
        <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center space-x-2 text-amber-400">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                <h2 class="text-sm font-bold uppercase tracking-wider text-white">Exness Referral Gateway</h2>
            </div>
            <p class="text-xs text-slate-400">All scanner access is gated by this referral partnership link. No payment gateways or subscriptions are used.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Official Exness Referral URL <span class="text-rose-400">*</span></label>
                    <input type="url" name="exness_referral_url" value="{{ old('exness_referral_url', $settings['exness_referral_url']->value ?? 'https://one.exnessonelink.com/a/l4l2nsdkfb?source=app') }}" required class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white font-mono text-xs focus:outline-none focus:border-amber-400">
                    <p class="text-[11px] text-slate-500 mt-1">Partner ID: <code class="text-amber-400">l4l2nsdkfb</code></p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Verification Processing Mode <span class="text-rose-400">*</span></label>
                    @php $mode = old('referral_verification_mode', $settings['referral_verification_mode']->value ?? 'manual_review'); @endphp
                    <select name="referral_verification_mode" required class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs focus:outline-none focus:border-amber-400">
                        <option value="manual_review" {{ $mode === 'manual_review' ? 'selected' : '' }}>Manual Admin Review Queue (Screenshots/IDs)</option>
                        <option value="official_api" {{ $mode === 'official_api' ? 'selected' : '' }}>Official Exness API / Webhook Sync (Automated)</option>
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Traders submit their account ID and proof in the Referral Access module.</p>
                </div>
            </div>
        </div>

        <!-- AI Engine Configuration -->
        <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center space-x-2 text-cyan-400">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                <h2 class="text-sm font-bold uppercase tracking-wider text-white">AI Analysis Engine Providers</h2>
            </div>
            <p class="text-xs text-slate-400">Select which LLM provider parses chart structures, validates price action geometry, and generates trading plans.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Active AI Provider <span class="text-rose-400">*</span></label>
                    @php $provider = old('ai_provider', $settings['ai_provider']->value ?? 'gemini'); @endphp
                    <select name="ai_provider" required class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs focus:outline-none focus:border-cyan-400">
                        <option value="gemini" {{ $provider === 'gemini' ? 'selected' : '' }}>Google Gemini (Vision + Multimodal Engine)</option>
                        <option value="claude" {{ $provider === 'claude' ? 'selected' : '' }}>Anthropic Claude 3.5/3.7 Sonnet</option>
                        <option value="openai" {{ $provider === 'openai' ? 'selected' : '' }}>OpenAI GPT-4o (Vision)</option>
                        <option value="deepseek" {{ $provider === 'deepseek' ? 'selected' : '' }}>DeepSeek Chat / Reasoner</option>
                        <option value="deterministic" {{ $provider === 'deterministic' ? 'selected' : '' }}>Deterministic Algorithmic Rule Engine (Zero-API-Key Mode)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Model Name / Endpoint <span class="text-rose-400">*</span></label>
                    <input type="text" name="ai_model" value="{{ old('ai_model', $settings['ai_model']->value ?? 'gemini-2.5-flash') }}" required class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white font-mono text-xs focus:outline-none focus:border-cyan-400" placeholder="e.g. gemini-2.5-flash, gpt-4o, claude-3-7-sonnet">
                </div>
            </div>

            <!-- Multi-AI 8-Role Architecture Overview -->
            <div class="pt-4 border-t border-slate-800/80 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Multi-AI Coordinated Intelligence Team</h3>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-950 text-cyan-300 border border-cyan-800">8 Specialized Roles Active</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2.5 text-xs">
                    <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-cyan-400 block">AI 1: Price Action</span>
                        <p class="text-[11px] text-slate-400">Candlestick bodies, wicks, breakout &amp; retest validation.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-cyan-400 block">AI 2: Market Structure</span>
                        <p class="text-[11px] text-slate-400">Swing pivots, BOS, CHOCH, structural invalidations.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-cyan-400 block">AI 3: SMC Specialist</span>
                        <p class="text-[11px] text-slate-400">Order blocks, liquidity sweeps, fair-value gaps.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-cyan-400 block">AI 4: Technical Indicators</span>
                        <p class="text-[11px] text-slate-400">Deterministic RSI, MACD, Bollinger Bands, ATR.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-cyan-400 block">AI 5: Multi-Timeframe</span>
                        <p class="text-[11px] text-slate-400">Macro bias &amp; higher-timeframe alignment.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-purple-400 block">AI 6: Fundamental &amp; News</span>
                        <p class="text-[11px] text-slate-400">Economic surprises, central bank policy, macro sentiment.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-amber-400 block">AI 7: Trading Risk</span>
                        <p class="text-[11px] text-slate-400">Veto gatekeeper, R:R geometry, volatility buffers.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1">
                        <span class="text-[10px] uppercase font-bold text-emerald-400 block">AI 8: Final Decision Synthesis</span>
                        <p class="text-[11px] text-slate-400">Evidence weighting, conflict resolution &rarr; BUY, SELL, WAIT.</p>
                    </div>
                </div>
            </div>

            <!-- API Key Status Checklist -->
            <div class="pt-4 border-t border-slate-800/80">
                <h3 class="text-xs font-bold text-slate-300 mb-3 uppercase tracking-wider">Backend API Key Health (Environment)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-white">Google Gemini</div>
                            <div class="text-[10px] text-slate-500 font-mono">GEMINI_API_KEY</div>
                        </div>
                        @if(env('GEMINI_API_KEY'))
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">Set</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-950 text-amber-400 border border-amber-800">Fallback</span>
                        @endif
                    </div>

                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-white">Anthropic Claude</div>
                            <div class="text-[10px] text-slate-500 font-mono">ANTHROPIC_API_KEY</div>
                        </div>
                        @if(env('ANTHROPIC_API_KEY'))
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">Set</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">Optional</span>
                        @endif
                    </div>

                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-white">OpenAI GPT-4o</div>
                            <div class="text-[10px] text-slate-500 font-mono">OPENAI_API_KEY</div>
                        </div>
                        @if(env('OPENAI_API_KEY'))
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">Set</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">Optional</span>
                        @endif
                    </div>

                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-white">DeepSeek</div>
                            <div class="text-[10px] text-slate-500 font-mono">DEEPSEEK_API_KEY</div>
                        </div>
                        @if(env('DEEPSEEK_API_KEY'))
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">Set</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">Optional</span>
                        @endif
                    </div>
                </div>
                <p class="text-[11px] text-slate-500 mt-2">When external API keys are not supplied or rate-limited, ChartSense AI automatically utilizes its internal mathematical &amp; SMC algorithmic calculation engine.</p>
            </div>
        </div>

        <!-- News Risk Firewall & Global Intelligence Policy -->
        <div class="p-6 rounded-2xl bg-slate-900/80 border border-rose-900/30 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2 text-rose-400">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-white">News Risk Firewall &amp; Gatekeeper Policy</h2>
                </div>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-950 text-rose-300 border border-rose-800">Hard Veto Active</span>
            </div>
            <p class="text-xs text-slate-400">The News Risk Firewall protects traders from volatile slippage, spread blowouts, and erratic spikes around high-impact economic releases (NFP, CPI, FOMC, Rate Decisions). It acts as an absolute, non-overridable gatekeeper.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Firewall Gatekeeper Status</label>
                    @php $firewallOn = old('firewall_enabled', $settings['firewall_enabled']->value ?? 'true'); @endphp
                    <select name="firewall_enabled" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs focus:outline-none focus:border-rose-400 font-mono">
                        <option value="true" {{ in_array($firewallOn, ['true', '1']) ? 'selected' : '' }}>ACTIVE (Enforce Hard Veto Blocks)</option>
                        <option value="false" {{ in_array($firewallOn, ['false', '0']) ? 'selected' : '' }}>DISABLED (Advisory Warnings Only)</option>
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Converts BUY/SELL setups into conditional wait scenarios during danger windows.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">CRITICAL Pre-Event Window (Min)</label>
                    <input type="number" name="firewall_critical_block_minutes" value="{{ old('firewall_critical_block_minutes', $settings['firewall_critical_block_minutes']->value ?? '30') }}" min="5" max="180" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white font-mono text-xs focus:outline-none focus:border-rose-400">
                    <p class="text-[11px] text-slate-500 mt-1">Minutes before CPI, NFP, FOMC to freeze new trade entries.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">HIGH Pre-Event Window (Min)</label>
                    <input type="number" name="firewall_high_block_minutes" value="{{ old('firewall_high_block_minutes', $settings['firewall_high_block_minutes']->value ?? '15') }}" min="5" max="120" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white font-mono text-xs focus:outline-none focus:border-rose-400">
                    <p class="text-[11px] text-slate-500 mt-1">Minutes before Retail Sales, GDP, PMIs to freeze trade entries.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Post-Release Stabilization (Min)</label>
                    <input type="number" name="firewall_post_stabilization_minutes" value="{{ old('firewall_post_stabilization_minutes', $settings['firewall_post_stabilization_minutes']->value ?? '15') }}" min="5" max="120" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white font-mono text-xs focus:outline-none focus:border-rose-400">
                    <p class="text-[11px] text-slate-500 mt-1">Cooldown window after release for spread and order book to normalize.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-800/80">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Telegram Risk Notifications</label>
                    @php $tgOn = old('telegram_alerts_enabled', $settings['telegram_alerts_enabled']->value ?? 'true'); @endphp
                    <select name="telegram_alerts_enabled" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs focus:outline-none focus:border-cyan-400 font-mono">
                        <option value="true" {{ in_array($tgOn, ['true', '1']) ? 'selected' : '' }}>Enabled (Broadcast News Freezes &amp; Releases)</option>
                        <option value="false" {{ in_array($tgOn, ['false', '0']) ? 'selected' : '' }}>Disabled</option>
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Sends instant alerts with duplicate prevention and audit logging.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Telegram Integration Health</label>
                    <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between text-xs">
                        <span class="text-slate-400 font-mono">TELEGRAM_BOT_TOKEN</span>
                        @if(env('TELEGRAM_BOT_TOKEN'))
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">Connected</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-400">Mock &amp; Audit Log Mode</span>
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Alerts are always logged to the <code class="text-cyan-400">telegram_alert_logs</code> database table.</p>
                </div>
            </div>
        </div>

        <!-- Inter-Model Collaboration & AI Debate Policy -->
        <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center space-x-2 text-cyan-400">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <h2 class="text-sm font-bold uppercase tracking-wider text-white">Multi-AI Collaboration &amp; Debate Policy (One Team)</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Max Structured Debate Rounds</label>
                    <select name="collaboration_debate_rounds" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs focus:outline-none focus:border-cyan-400 font-mono">
                        <option value="1" {{ old('collaboration_debate_rounds', $settings['collaboration_debate_rounds']->value ?? '1') === '1' ? 'selected' : '' }}>1 Round (Standard &amp; Efficient)</option>
                        <option value="2" {{ old('collaboration_debate_rounds', $settings['collaboration_debate_rounds']->value ?? '') === '2' ? 'selected' : '' }}>2 Rounds (Deep Conflict Resolution)</option>
                        <option value="0" {{ old('collaboration_debate_rounds', $settings['collaboration_debate_rounds']->value ?? '') === '0' ? 'selected' : '' }}>0 Rounds (Cross-Review Only, No Debate)</option>
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Controls how many challenge-reassessment exchanges models can initiate before final synthesis.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Cross-Review Inter-Model Depth</label>
                    <select name="collaboration_cross_review_depth" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs focus:outline-none focus:border-cyan-400 font-mono">
                        <option value="standard" {{ old('collaboration_cross_review_depth', $settings['collaboration_cross_review_depth']->value ?? 'standard') === 'standard' ? 'selected' : '' }}>Standard (Structure &bull; SMC &bull; Fundamental &bull; Risk)</option>
                        <option value="deep" {{ old('collaboration_cross_review_depth', $settings['collaboration_cross_review_depth']->value ?? '') === 'deep' ? 'selected' : '' }}>Deep (Exhaustive Cross-Model Challenges)</option>
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Defines review pair matrix density between specialized analysts.</p>
                </div>
            </div>
        </div>

        <!-- System Notices -->
        <div class="p-6 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center space-x-2 text-indigo-400">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <h2 class="text-sm font-bold uppercase tracking-wider text-white">Broadcast Trader Notice</h2>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Global Dashboard Banner (Optional)</label>
                <textarea name="site_notice" rows="2" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-xs focus:outline-none focus:border-indigo-400" placeholder="e.g. Scheduled maintenance window or Exness partner promotion info...">{{ old('site_notice', $settings['site_notice']->value ?? '') }}</textarea>
                <p class="text-[11px] text-slate-500 mt-1">Displayed to all authenticated users on their dashboard.</p>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 text-slate-950 font-bold text-xs tracking-wide shadow-lg transition">
                Save System &amp; AI Settings
            </button>
        </div>
    </form>
</div>
@endsection
