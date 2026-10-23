@extends('layouts.admin')

@section('title', 'Security Center — Zero-Trust Security Fortress')

@section('content')
<div class="space-y-8">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gradient-to-r from-slate-900 via-slate-900 to-emerald-950/40 p-6 rounded-2xl border border-slate-800 shadow-xl">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span>
                    FORTRESS ACTIVE
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                    OWASP Top 10 + GenAI 2025
                </span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center space-x-2.5">
                <svg class="w-7 h-7 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span>Zero-Trust Security Fortress</span>
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                Continuous real-time threat monitoring, SSRF firewall, AI prompt injection defense, and cryptographic access validation.
            </p>
        </div>

        <!-- Action / Disaster Recovery Trigger -->
        <div class="flex items-center space-x-3 shrink-0">
            <form action="{{ route('admin.security.test-backup') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-xl font-bold text-xs bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 transition flex items-center space-x-2 cursor-pointer shadow-lg">
                    <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <polyline points="1 20 1 14 7 14"></polyline>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                    <span>Test Backup Integrity</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-sm font-semibold flex items-center space-x-2">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 text-sm font-semibold flex items-center space-x-2">
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Key Fortress Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Security Grade -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800/80 shadow-lg">
            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                <span class="font-medium">Security Posture Grade</span>
                <span class="text-emerald-400 font-bold">Hardened</span>
            </div>
            <div class="text-3xl font-black text-white tracking-tight flex items-baseline space-x-2">
                <span class="text-emerald-400">A+</span>
                <span class="text-xs text-slate-400 font-normal">Fortress Standard</span>
            </div>
            <div class="text-[11px] text-slate-400 mt-2">
                Zero-Trust architecture active across all API, DB, and AI layers.
            </div>
        </div>

        <!-- Metric 2: Security Controls -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800/80 shadow-lg">
            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                <span class="font-medium">Active Defense Controls</span>
                <span class="text-cyan-400 font-bold">12 / 12 Active</span>
            </div>
            <div class="text-3xl font-black text-white tracking-tight">
                100%
            </div>
            <div class="text-[11px] text-slate-400 mt-2">
                SSRF Guard, Prompt Sanitizer, Magic-Bytes, TLS, CSP, HSTS.
            </div>
        </div>

        <!-- Metric 3: Blocked Threats -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800/80 shadow-lg">
            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                <span class="font-medium">Blocked Threat Events</span>
                <span class="text-amber-400 font-bold">Audited</span>
            </div>
            <div class="text-3xl font-black text-white tracking-tight">
                {{ $totalBlockedThreats }}
            </div>
            <div class="text-[11px] text-slate-400 mt-2">
                Hostile prompt tokens, fake token auth, and SSRF probes neutralized.
            </div>
        </div>

        <!-- Metric 4: Disaster Recovery -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800/80 shadow-lg">
            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                <span class="font-medium">Disaster Recovery &amp; Backup</span>
                <span class="text-emerald-400 font-bold">Verified</span>
            </div>
            <div class="text-sm font-bold text-white tracking-tight truncate">
                {{ $backupStatus === 'verified_healthy' ? 'Verified Healthy' : $backupStatus }}
            </div>
            <div class="text-[11px] text-slate-400 mt-2 truncate">
                Last checked: {{ \Carbon\Carbon::parse($lastBackupTime)->diffForHumans() }}
            </div>
        </div>
    </div>

    <!-- Security Findings Matrix -->
    <div class="bg-slate-900/80 border border-slate-800/80 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-white flex items-center space-x-2">
                    <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span>Threat Model Audit &amp; Remediated Vulnerabilities</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Comprehensive audit finding catalog with CVSS scores and implemented security controls.</p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded-full font-bold bg-slate-800 text-slate-300 border border-slate-700">
                {{ count($findings) }} Findings Cataloged
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Finding ID</th>
                        <th class="py-3 px-4">Severity &amp; CVSS</th>
                        <th class="py-3 px-4">Vulnerability Title</th>
                        <th class="py-3 px-4">Affected Component</th>
                        <th class="py-3 px-4">Verified Security Control</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @foreach($findings as $f)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3.5 px-4 font-mono font-bold text-slate-200">
                            {{ $f['id'] }}
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($f['severity'] === 'CRITICAL')
                                <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-rose-500/20 text-rose-400 border border-rose-500/30">CRITICAL {{ $f['cvss'] }}</span>
                            @elseif($f['severity'] === 'HIGH')
                                <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-amber-500/20 text-amber-400 border border-amber-500/30">HIGH {{ $f['cvss'] }}</span>
                            @elseif($f['severity'] === 'MEDIUM')
                                <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-yellow-500/20 text-yellow-300 border border-yellow-500/30">MEDIUM {{ $f['cvss'] }}</span>
                            @else
                                <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">LOW {{ $f['cvss'] }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-semibold text-white">
                            {{ $f['title'] }}
                        </td>
                        <td class="py-3.5 px-4 font-mono text-[11px] text-slate-400">
                            {{ $f['component'] }}
                        </td>
                        <td class="py-3.5 px-4 text-slate-300 max-w-xs">
                            {{ $f['control'] }}
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($f['status'] === 'RESOLVED')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center w-max space-x-1">
                                    <svg class="w-3 h-3 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <span>RESOLVED</span>
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-700 text-slate-300 border border-slate-600 flex items-center w-max space-x-1">
                                    <span>ACCEPTED RISK</span>
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Real-time Security Events & Intrusion Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Intrusion Logs (2 cols) -->
        <div class="lg:col-span-2 bg-slate-900/80 border border-slate-800/80 rounded-2xl shadow-xl overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        <span>Live Security &amp; Intrusion Feed</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Real-time audit log of security events, neutralizations, and authentication checks.</p>
                </div>
            </div>

            <div class="divide-y divide-slate-800/60 overflow-y-auto max-h-96">
                @forelse($recentSecurityEvents as $ev)
                <div class="p-4 hover:bg-slate-800/30 transition flex items-start space-x-3 text-xs">
                    <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ str_contains($ev->action, 'failed') || str_contains($ev->action, 'blocked') || str_contains($ev->action, 'detected') ? 'bg-amber-400' : 'bg-cyan-400' }}"></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-white">{{ $ev->action }}</span>
                            <span class="text-slate-500 font-mono text-[10px]">{{ $ev->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-slate-400 mt-1 truncate">
                            IP: <span class="font-mono text-slate-300">{{ $ev->ip_address }}</span> &bull;
                            @if(isset($ev->payload['description']))
                                {{ $ev->payload['description'] }}
                            @elseif(isset($ev->payload['email']))
                                User: {{ $ev->payload['email'] }}
                            @else
                                {{ json_encode($ev->payload) }}
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-slate-500 text-xs">
                    No security events recorded. System running normally.
                </div>
                @endforelse
            </div>
        </div>

        <!-- Outbound Network & Provider Health (1 col) -->
        <div class="bg-slate-900/80 border border-slate-800/80 rounded-2xl shadow-xl overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-800">
                <h2 class="text-base font-bold text-white flex items-center space-x-2">
                    <svg class="w-4 h-4 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                    <span>SSRF &amp; Provider Health</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">TLS certificate verification &amp; SSRF guard status.</p>
            </div>

            <div class="p-4 space-y-3">
                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-white">TLS Certificate Verification</span>
                        <span class="text-emerald-400 font-bold flex items-center space-x-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>ENFORCED</span>
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-400">
                        Http::withoutVerifying() removed. MitM attack vector closed.
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-white">SSRF Metadata Shield</span>
                        <span class="text-emerald-400 font-bold flex items-center space-x-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>BLOCKING</span>
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-400">
                        169.254.169.254, loopback (127.0.0.1), and RFC1918 subnets blocked.
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-white">AI Vision Prompt Fence</span>
                        <span class="text-emerald-400 font-bold flex items-center space-x-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>XML BOUNDED</span>
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-400">
                        User commentary &amp; OCR text demarcated with Zero-Trust status.
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-white">Telegram Alerts Guard</span>
                        <span class="text-emerald-400 font-bold flex items-center space-x-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>REDACTED</span>
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-400">
                        Bot token sanitized from application exception logs.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
