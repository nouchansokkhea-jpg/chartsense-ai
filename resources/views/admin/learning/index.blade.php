@extends('layouts.admin')

@section('title', 'Global Market Learning Center — Autonomous AI & Quantitative Lab')

@section('content')
<div class="space-y-8">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 p-6 rounded-2xl border border-slate-800 shadow-xl">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-400 border border-indigo-500/40">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse mr-1.5"></span>
                    AUTONOMOUS LEARNING ACTIVE
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                    Zero Look-Ahead Engine v1.0.0
                </span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center space-x-2.5">
                <svg class="w-7 h-7 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>
                <span>Global Market Learning Center</span>
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                3-year rolling historical ingestion, vision ground-truth calibration, multi-model registry, and automated strategy research.
            </p>
        </div>

        <!-- Run Daily Cycle Action Button -->
        <div class="flex items-center space-x-3 shrink-0">
            <form action="{{ route('admin.learning.run-cycle') }}" method="POST">
                @csrf
                <button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-xs bg-indigo-600 hover:bg-indigo-500 text-white transition flex items-center space-x-2 cursor-pointer shadow-lg shadow-indigo-600/30">
                    <svg class="w-4 h-4 text-cyan-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    <span>Trigger Daily Learning Cycle</span>
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

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Historical Candles Ingested</span>
            <div class="text-3xl font-black text-white mt-2">{{ number_format($totalCandles) }}</div>
            <span class="text-xs text-emerald-400 flex items-center mt-2">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5"></span>
                Rolling 3-Year Dataset
            </span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">AI Model Registry</span>
            <div class="text-3xl font-black text-cyan-400 mt-2">{{ $models->count() }} Models</div>
            <span class="text-xs text-slate-400 mt-2 block">12 Specialized Analytical Roles</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Screenshot Ground Truth Match</span>
            <div class="text-3xl font-black text-indigo-400 mt-2">{{ $matchRate }}%</div>
            <span class="text-xs text-slate-400 mt-2 block">Avg Price Error: {{ round($avgPriceError, 2) }}%</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Learning Runs Executed</span>
            <div class="text-3xl font-black text-amber-400 mt-2">{{ $learningRuns->count() }} Cycles</div>
            <span class="text-xs text-slate-400 mt-2 block">11 Stages per Daily Cycle</span>
        </div>
    </div>

    <!-- Section A: Historical Data Center -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center space-x-2">
                    <svg class="w-5 h-5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>A. Historical Market Data Center</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">3-year rolling OHLCV dataset with zero look-ahead bias and gap detection.</p>
            </div>

            <!-- Manual Sync Form -->
            <form action="{{ route('admin.learning.sync') }}" method="POST" class="flex flex-wrap items-center gap-2">
                @csrf
                <select name="symbol" class="bg-slate-950 border border-slate-700 text-xs text-white rounded-lg px-3 py-2">
                    <option value="EURUSD">EURUSD</option>
                    <option value="GBPUSD">GBPUSD</option>
                    <option value="USDJPY">USDJPY</option>
                    <option value="AUDUSD">AUDUSD</option>
                    <option value="USDCAD">USDCAD</option>
                    <option value="USDCHF">USDCHF</option>
                    <option value="NZDUSD">NZDUSD</option>
                    <option value="XAUUSD">XAUUSD</option>
                    <option value="BTCUSD">BTCUSD</option>
                    <option value="SPX500">SPX500</option>
                </select>

                <select name="timeframe" class="bg-slate-950 border border-slate-700 text-xs text-white rounded-lg px-3 py-2">
                    <option value="D1">D1 (Daily)</option>
                    <option value="H4">H4 (4-Hour)</option>
                    <option value="H1">H1 (1-Hour)</option>
                    <option value="M15">M15 (15-Min)</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs rounded-lg transition">
                    Sync Ingestion
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950 text-slate-400 uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="p-3">Symbol</th>
                        <th class="p-3">Timeframe</th>
                        <th class="p-3">Candles Stored</th>
                        <th class="p-3">Quality Score</th>
                        <th class="p-3">Data Source</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($instruments as $inst)
                        <tr class="hover:bg-slate-800/40">
                            <td class="p-3 font-bold text-white">{{ $inst->symbol }}</td>
                            <td class="p-3"><span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300">{{ $inst->timeframe }}</span></td>
                            <td class="p-3">{{ number_format($inst->count) }}</td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $inst->avg_quality >= 80 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }}">
                                    {{ round($inst->avg_quality) }}/100
                                </span>
                            </td>
                            <td class="p-3 text-slate-400">PostgreSQL Rolling Partition</td>
                            <td class="p-3"><span class="text-emerald-400 font-semibold">Synchronized</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-4 text-center text-slate-500">No historical candles ingested yet. Use the sync form above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section B: AI Model Registry -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
        <div class="border-b border-slate-800 pb-4">
            <h2 class="text-lg font-bold text-white flex items-center space-x-2">
                <svg class="w-5 h-5 text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
                <span>B. Capability-Aware AI Model Registry</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Specialized analytical roles, vision capabilities, and strictly verified fine-tuning support.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($models as $model)
                <div class="bg-slate-950 border border-slate-800 rounded-xl p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-indigo-400">{{ $model->provider }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $model->evaluation_status === 'active' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-400' }}">
                            {{ strtoupper($model->evaluation_status) }}
                        </span>
                    </div>
                    <div class="text-base font-bold text-white">{{ $model->display_name }}</div>
                    <div class="text-xs text-slate-400 font-mono">{{ $model->model_id }}</div>

                    <!-- Modalities & Capabilities -->
                    <div class="flex flex-wrap gap-1.5 pt-2">
                        @if($model->supports_vision)
                            <span class="px-2 py-0.5 rounded text-[10px] bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">Vision OCR</span>
                        @endif
                        @if($model->supports_fine_tuning)
                            <span class="px-2 py-0.5 rounded text-[10px] bg-purple-500/20 text-purple-400 border border-purple-500/30">Fine-Tuning Capable</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-400">Mechanism A/B Only</span>
                        @endif
                        <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300">{{ number_format($model->context_window_tokens / 1000) }}k Context</span>
                        <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300">{{ $model->avg_latency_ms }}ms</span>
                    </div>

                    <!-- Assigned Roles -->
                    <div class="pt-2 border-t border-slate-850">
                        <span class="text-[10px] text-slate-400 uppercase font-semibold block mb-1">Assigned Analytical Roles:</span>
                        <div class="flex flex-wrap gap-1">
                            @foreach($model->assigned_roles ?? [] as $role)
                                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-850 text-slate-300">{{ $role }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Section C: Screenshot Intelligence Lab -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
        <div class="border-b border-slate-800 pb-4">
            <h2 class="text-lg font-bold text-white flex items-center space-x-2">
                <svg class="w-5 h-5 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
                <span>C. Screenshot Intelligence Lab</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Computer-vision evaluation benchmarked against verified historical OHLCV data.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950 text-slate-400 uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="p-3">Scan ID</th>
                        <th class="p-3">Detected Symbol & TF</th>
                        <th class="p-3">Ground Truth Matched</th>
                        <th class="p-3">Price Extraction Error</th>
                        <th class="p-3">Structure Recall</th>
                        <th class="p-3">Confidence Score</th>
                        <th class="p-3">Evaluation Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($screenshotEvaluations as $eval)
                        <tr class="hover:bg-slate-800/40">
                            <td class="p-3 font-mono text-cyan-400">#{{ $eval->scan_id ?? $eval->id }}</td>
                            <td class="p-3 font-semibold text-white">{{ $eval->detected_symbol ?? 'N/A' }} ({{ $eval->detected_timeframe ?? 'N/A' }})</td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $eval->ground_truth_matched ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' }}">
                                    {{ $eval->ground_truth_matched ? 'VERIFIED MATCH' : 'UNVERIFIED' }}
                                </span>
                            </td>
                            <td class="p-3">{{ $eval->price_extraction_error_pct !== null ? $eval->price_extraction_error_pct . '%' : 'N/A' }}</td>
                            <td class="p-3">{{ $eval->structure_recall_score !== null ? $eval->structure_recall_score . '%' : 'N/A' }}</td>
                            <td class="p-3 font-bold text-white">{{ $eval->confidence_score }}%</td>
                            <td class="p-3 text-slate-400 truncate max-w-xs">{{ $eval->evaluation_notes }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-slate-500">No screenshot evaluations recorded yet. Run chart scans through the scanner to calibrate.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section D: Strategy Research Lab -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
        <div class="border-b border-slate-800 pb-4">
            <h2 class="text-lg font-bold text-white flex items-center space-x-2">
                <svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
                <span>D. Strategy Research Lab & Knowledge Base</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Centralized strategy definitions, versions, and candidate research discoveries.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Active Strategies -->
            <div class="space-y-3">
                <h3 class="text-sm font-bold text-slate-300">Registered Methodologies ({{ $strategies->count() }})</h3>
                <div class="space-y-2 max-h-72 overflow-y-auto pr-2">
                    @foreach($strategies as $st)
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="font-bold text-white text-xs block">{{ $st->name }}</span>
                                <span class="text-[10px] text-slate-400">{{ $st->category_name }}</span>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $st->is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-400' }}">
                                {{ $st->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Approved Research Feeds & Discoveries -->
            <div class="space-y-3">
                <h3 class="text-sm font-bold text-slate-300">Approved Research Sources & Candidates</h3>
                <div class="space-y-2 max-h-72 overflow-y-auto pr-2">
                    @foreach($researchDocuments as $doc)
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                            <span class="font-semibold text-white text-xs block">{{ $doc->title }}</span>
                            <span class="text-[10px] text-slate-400 block">{{ $doc->author }} &bull; Credibility: {{ $doc->extracted_rules['credibility_score'] ?? 90 }}/100</span>
                            <p class="text-[11px] text-slate-300">{{ $doc->extracted_summary }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Section E & F: Autonomous Learning Monitor & Model Evaluation Center -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
        <div class="border-b border-slate-800 pb-4">
            <h2 class="text-lg font-bold text-white flex items-center space-x-2">
                <svg class="w-5 h-5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                <span>E & F. Autonomous Daily Learning Monitor & Evaluation Center</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Audit log of daily 11-stage cycles, holdout validation, and candidate promotions.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950 text-slate-400 uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="p-3">Run Date</th>
                        <th class="p-3">Candles Ingested</th>
                        <th class="p-3">Features Computed</th>
                        <th class="p-3">Discoveries</th>
                        <th class="p-3">Proposals Promoted</th>
                        <th class="p-3">Duration</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($learningRuns as $run)
                        <tr class="hover:bg-slate-800/40">
                            <td class="p-3 font-semibold text-white">{{ $run->run_date->format('Y-m-d') }}</td>
                            <td class="p-3">{{ $run->candles_ingested }}</td>
                            <td class="p-3">{{ $run->features_calculated }}</td>
                            <td class="p-3">{{ $run->discoveries_made }}</td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $run->proposals_promoted > 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-400' }}">
                                    {{ $run->proposals_promoted }} Promoted
                                </span>
                            </td>
                            <td class="p-3 text-slate-400">{{ $run->execution_seconds }}s</td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400">
                                    {{ strtoupper($run->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-slate-500">No learning runs executed yet. Trigger a daily cycle above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
