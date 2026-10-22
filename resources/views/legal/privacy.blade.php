@extends('layouts.app')

@section('title', 'Privacy Policy - ChartSense AI')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="p-8 sm:p-10 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-6 text-slate-300 text-sm leading-relaxed">
        <div class="border-b border-slate-800 pb-4">
            <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">Data Protection</span>
            <h1 class="text-3xl font-extrabold text-white mt-1">Privacy Policy</h1>
            <p class="text-xs text-slate-500 mt-1">Last Updated: October 2026</p>
        </div>

        <section class="space-y-3">
            <h2 class="text-base font-bold text-white">1. Information We Collect</h2>
            <p>
                We collect your name, email address, securely hashed passwords, and your submitted Exness trading account identifier for referral verification.
            </p>
            <p>
                <strong>We NEVER collect your Exness account password, trading PIN, OTP codes, or banking credentials.</strong>
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-bold text-white">2. Chart and Market Data Privacy</h2>
            <p>
                Chart screenshots and CSV files uploaded to ChartSense AI are stored privately in server-side storage and processed solely for your generated trading analysis. We do not sell your personal trading data or share your private scans with third parties.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-bold text-white">3. Third-Party AI Providers</h2>
            <p>
                When you initiate a scan, sanitized chart visual data and calculated technical indicators are sent via server-side encrypted API requests to our configured vision/AI inference providers (such as Google Gemini, Anthropic Claude, or OpenAI) to generate structured analysis. AI provider API keys remain exclusively on our backend and are never exposed to browser clients.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-bold text-white">4. User Rights and Data Deletion</h2>
            <p>
                You may request deletion of your account and scan history at any time through your Profile Settings or by contacting an administrator.
            </p>
        </section>

        <div class="pt-4 border-t border-slate-800 flex justify-between items-center text-xs text-slate-500">
            <a href="{{ url('/') }}" class="text-cyan-400 hover:text-cyan-300">&larr; Back to Home</a>
            <a href="{{ url('/risk-disclosure') }}" class="text-slate-400 hover:text-slate-300">Risk Disclosure &rarr;</a>
        </div>
    </div>
</div>
@endsection
