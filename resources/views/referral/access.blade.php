@extends('layouts.app')

@section('title', 'Exness Referral Access Verification - ChartSense AI')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb / Header -->
    <div class="space-y-3 mb-8">
        <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-700/80 text-xs text-slate-300">
            <span class="w-1.5 h-1.5 rounded-full {{ $status === 'VERIFIED' ? 'bg-emerald-400' : ($status === 'PENDING_VERIFICATION' ? 'bg-amber-400 animate-pulse' : 'bg-cyan-400') }}"></span>
            <span>Exness Partner Access Gate</span>
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">Referral Verification Status</h1>
        <p class="text-sm text-slate-400 max-w-2xl">
            ChartSense AI provides institutional market scanning free of charge. Access is unlocked exclusively by joining Exness via our official referral link and completing verification.
        </p>
    </div>

    <!-- CURRENT STATUS CARD -->
    <div class="p-6 sm:p-8 rounded-2xl bg-slate-900/80 border {{ $status === 'VERIFIED' ? 'border-emerald-500/40 bg-gradient-to-br from-emerald-950/20 to-slate-900' : ($status === 'PENDING_VERIFICATION' ? 'border-amber-500/40 bg-gradient-to-br from-amber-950/20 to-slate-900' : ($status === 'REJECTED' || $status === 'SUSPENDED' ? 'border-rose-500/40 bg-gradient-to-br from-rose-950/20 to-slate-900' : 'border-slate-800')) }} shadow-2xl space-y-6 mb-10">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800/80 pb-6">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block mb-1">Your Access Status</span>
                <div class="flex items-center space-x-3">
                    @if($status === 'VERIFIED')
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                            <span class="text-2xl font-extrabold text-emerald-400">VERIFIED &bull; UNLOCKED</span>
                        </div>
                    @elseif($status === 'PENDING_VERIFICATION')
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-amber-400 animate-ping"></span>
                            <span class="text-2xl font-extrabold text-amber-400">PENDING VERIFICATION</span>
                        </div>
                    @elseif($status === 'REJECTED')
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-rose-400"></span>
                            <span class="text-2xl font-extrabold text-rose-400">VERIFICATION REJECTED</span>
                        </div>
                    @elseif($status === 'SUSPENDED')
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            <span class="text-2xl font-extrabold text-rose-500">ACCESS SUSPENDED</span>
                        </div>
                    @else
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-slate-500"></span>
                            <span class="text-2xl font-extrabold text-slate-200">NOT STARTED (LOCKED)</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Action Button -->
            <div>
                @if($status === 'VERIFIED')
                    <a href="{{ route('scanner') }}" class="inline-flex items-center px-6 py-3 rounded-xl text-sm font-semibold text-slate-950 bg-emerald-400 hover:bg-emerald-300 shadow-xl shadow-emerald-500/20 transition">
                        <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                        Launch AI Chart Scanner
                    </a>
                @else
                    <a href="{{ route('referral-access.click') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-6 py-3 rounded-xl text-sm font-semibold text-amber-300 bg-amber-500/10 border border-amber-500/40 hover:bg-amber-500/20 transition">
                        <span>Join Exness Official</span>
                        <svg class="w-4 h-4 ml-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    </a>
                @endif
            </div>
        </div>

        <!-- Details / Timelines -->
        @if($status === 'VERIFIED')
            <div class="p-4 rounded-xl bg-emerald-950/40 border border-emerald-800 text-xs text-emerald-200 space-y-1">
                <p class="font-semibold text-emerald-300">✓ Your scanner access is permanently active.</p>
                <p class="text-emerald-400/80">
                    Verified Exness Account ID: <code class="font-mono font-bold">{{ $verification?->exness_account_id }}</code>. You may scan unlimited chart screenshots and analyze real CSV market data anytime.
                </p>
            </div>
        @elseif($status === 'PENDING_VERIFICATION')
            <div class="p-4 rounded-xl bg-amber-950/40 border border-amber-800 text-xs text-amber-200 space-y-2">
                <div class="flex items-center space-x-2 font-semibold text-amber-300">
                    <svg class="w-4 h-4 animate-spin text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                    <span>Verification Request Submitted</span>
                </div>
                <p class="text-amber-300/80">
                    Submitted Account ID: <code class="font-mono font-bold">{{ $verification?->exness_account_id }}</code> &bull; Submitted on: {{ $verification?->submitted_at?->toDayDateTimeString() }}.
                </p>
                <p class="text-slate-400">
                    An administrator is reviewing your partner relationship with Exness. Once verified, this page and the AI Scanner will unlock automatically.
                </p>
            </div>
        @elseif($status === 'REJECTED')
            <div class="p-4 rounded-xl bg-rose-950/40 border border-rose-800 text-xs text-rose-200 space-y-2">
                <p class="font-semibold text-rose-300">Rejection Reason:</p>
                <p class="text-rose-200 bg-rose-950/70 p-2.5 rounded border border-rose-800/80 font-mono">
                    {{ $verification?->rejection_reason ?? 'Your Exness account ID could not be verified under our partner referral code. Please ensure you registered via the official link.' }}
                </p>
                <p class="text-slate-400">
                    You may correct your account details and upload updated evidence using the form below.
                </p>
            </div>
        @endif
    </div>

    <!-- SUBMISSION / RE-SUBMISSION WORKFLOW -->
    @if($status !== 'VERIFIED')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left: Instructions (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
                    <h3 class="text-base font-bold text-white flex items-center space-x-2">
                        <span class="w-6 h-6 rounded-full bg-cyan-950 text-cyan-400 border border-cyan-800 flex items-center justify-center text-xs">i</span>
                        <span>Verification Instructions</span>
                    </h3>
                    <ol class="space-y-3 text-xs text-slate-300 list-decimal pl-4 leading-relaxed">
                        <li>
                            Click the <strong>"Join Exness Official"</strong> button to open the authorized registration link:
                            <div class="mt-1.5 p-2 rounded bg-slate-950 border border-slate-800 break-all text-[11px] font-mono text-cyan-400">
                                {{ $exnessUrl }}
                            </div>
                        </li>
                        <li>
                            Complete your registration on Exness (or create a new trading account under the partner code <code class="text-amber-400">l4l2nsdkfb</code>).
                        </li>
                        <li>
                            Copy your Exness Account ID / Partner Client ID from your Exness Personal Area.
                        </li>
                        <li>
                            Submit your ID and optionally upload a screenshot of your Exness cabinet showing your account number for expedited approval.
                        </li>
                    </ol>
                </div>

                <!-- Security Assurance -->
                <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 text-xs text-slate-400 space-y-2">
                    <div class="flex items-center space-x-2 text-emerald-400 font-semibold">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span>Zero Passwords &bull; 100% Privacy</span>
                    </div>
                    <p class="text-[11px] leading-relaxed">
                        We never ask for your Exness password, trading PIN, or security credentials. Verification strictly confirms affiliate partner association so we can sponsor your scanner access.
                    </p>
                </div>
            </div>

            <!-- Right: Submission Form (7 cols) -->
            <div class="lg:col-span-7">
                <div class="p-6 sm:p-8 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-xl space-y-6">
                    <div class="border-b border-slate-800/80 pb-4">
                        <h3 class="text-lg font-bold text-white">Submit Verification Details</h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Enter your Exness information to request manual audit or automated verification.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('referral-access.submit') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf

                        <!-- Exness Account ID -->
                        <div>
                            <label for="exness_account_id" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                                Exness Account ID / Client Number <span class="text-rose-400">*</span>
                            </label>
                            <input id="exness_account_id" name="exness_account_id" type="text" value="{{ old('exness_account_id', $verification?->exness_account_id) }}" required placeholder="e.g. 19827402 or EX-849201" class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition font-mono @error('exness_account_id') border-rose-500 @enderror">
                            @error('exness_account_id')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-[11px] text-slate-500">Your trading account number visible inside your Exness Personal Area.</p>
                        </div>

                        <!-- Screenshot Evidence File Upload -->
                        <div>
                            <label for="evidence_file" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                                Verification Screenshot Proof <span class="text-slate-500">(Optional, recommended)</span>
                            </label>
                            <input id="evidence_file" name="evidence_file" type="file" accept="image/png,image/jpeg,image/webp,application/pdf" class="w-full px-4 py-2 rounded-xl bg-slate-950/70 border border-slate-700 text-slate-300 text-sm file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-cyan-400 hover:file:bg-slate-700 focus:outline-none transition @error('evidence_file') border-rose-500 @enderror">
                            @error('evidence_file')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-[11px] text-slate-500">Upload screenshot of your Exness Personal Area confirming account creation. Max 15MB (PNG, JPG, WebP, PDF).</p>
                        </div>

                        <!-- Notes / Details -->
                        <div>
                            <label for="notes" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                                Additional Notes <span class="text-slate-500">(Optional)</span>
                            </label>
                            <textarea id="notes" name="notes" rows="2" placeholder="e.g. Registered with email trader@example.com today" class="w-full px-4 py-2 rounded-xl bg-slate-950/70 border border-slate-700 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" class="w-full py-3 px-4 rounded-xl text-sm font-semibold text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-xl shadow-cyan-500/25 transition">
                                {{ $status === 'PENDING_VERIFICATION' ? 'Update Verification Submission' : 'Submit for Exness Referral Verification' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
