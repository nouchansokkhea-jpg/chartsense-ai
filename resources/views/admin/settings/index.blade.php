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
                        <option value="claude" {{ $provider === 'claude' ? 'selected' : '' }}>Anthropic Claude 3.5 Sonnet</option>
                        <option value="openai" {{ $provider === 'openai' ? 'selected' : '' }}>OpenAI GPT-4o (Vision)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Model Name / Endpoint <span class="text-rose-400">*</span></label>
                    <input type="text" name="ai_model" value="{{ old('ai_model', $settings['ai_model']->value ?? 'gemini-1.5-pro') }}" required class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white font-mono text-xs focus:outline-none focus:border-cyan-400" placeholder="e.g. gemini-1.5-pro, claude-3-5-sonnet-20241022">
                </div>
            </div>

            <!-- API Key Status Checklist -->
            <div class="pt-4 border-t border-slate-800/80">
                <h3 class="text-xs font-bold text-slate-300 mb-3 uppercase tracking-wider">Backend API Key Health (Environment)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
                </div>
                <p class="text-[11px] text-slate-500 mt-2">When no external AI key is supplied, ChartSense AI automatically utilizes its internal mathematical &amp; SMC algorithmic calculation engine.</p>
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
