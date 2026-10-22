@extends('layouts.app')

@section('title', 'Terms of Service - ChartSense AI')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="p-8 sm:p-10 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-6 text-slate-300 text-sm leading-relaxed">
        <div class="border-b border-slate-800 pb-4">
            <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">User Agreement</span>
            <h1 class="text-3xl font-extrabold text-white mt-1">Terms of Service</h1>
            <p class="text-xs text-slate-500 mt-1">Last Updated: October 2026</p>
        </div>

        <section class="space-y-3">
            <h2 class="text-base font-bold text-white">1. Acceptance of Terms</h2>
            <p>
                By creating an account or accessing ChartSense AI, you agree to be bound by these Terms of Service. If you do not agree to all terms, you may not use the platform.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-bold text-white">2. Zero-Billing Business Model & Referral Verification</h2>
            <p>
                ChartSense AI does not charge users or process financial payments. Access to the scanner requires:
            </p>
            <ul class="list-disc pl-5 space-y-1 text-slate-400 text-xs">
                <li>Registering with our official Exness affiliate/partner link: <a href="https://one.exnessonelink.com/a/l4l2nsdkfb?source=app" class="text-cyan-400 underline" target="_blank" rel="noopener">https://one.exnessonelink.com/a/l4l2nsdkfb?source=app</a>.</li>
                <li>Submitting your Exness trading account ID for verification.</li>
                <li>Receiving approval from an authorized administrator or system verification check.</li>
            </ul>
            <p>
                Access may be suspended or revoked at any time if referral eligibility is invalidated, if abusive scraping occurs, or if false verification evidence is submitted.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-bold text-white">3. Intellectual Property & Acceptable Use</h2>
            <p>
                All algorithmic structures, technical analysis algorithms, and proprietary prompts belong to ChartSense AI. You agree not to reverse engineer, scrape, automatedly flood, or distribute unauthorized clones of the platform.
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-base font-bold text-white">4. Disclaimer of Warranties</h2>
            <p>
                The software is provided "AS IS" without warranty of any kind, either express or implied, including warranties of merchantability, fitness for a particular purpose, or trading win rates. ChartSense AI makes no guarantee of profit or prevention of loss.
            </p>
        </section>

        <div class="pt-4 border-t border-slate-800 flex justify-between items-center text-xs text-slate-500">
            <a href="{{ url('/') }}" class="text-cyan-400 hover:text-cyan-300">&larr; Back to Home</a>
            <a href="{{ url('/privacy') }}" class="text-slate-400 hover:text-slate-300">Privacy Policy &rarr;</a>
        </div>
    </div>
</div>
@endsection
