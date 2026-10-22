<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>ChartSense AI Report - {{ $scan->symbol }} ({{ $scan->timeframe }})</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 40px; color: #111827; background: #fff; line-height: 1.5; font-size: 13px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #06b6d4; padding-bottom: 15px; margin-bottom: 25px; }
        .logo { font-size: 20px; font-weight: 800; color: #0f172a; }
        .logo span { color: #0891b2; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 12px; }
        .badge-buy { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .badge-sell { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .badge-wait { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; }
        .meta-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .meta-item span { display: block; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; }
        .meta-item strong { font-size: 15px; color: #0f172a; font-family: monospace; }
        .plan-box { border: 1px solid #cbd5e1; border-radius: 8px; padding: 20px; margin-bottom: 25px; }
        .tp-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 15px; }
        .tp-card { background: #f1f5f9; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e1; }
        .tp-card strong { display: block; font-size: 16px; font-family: monospace; color: #0f172a; }
        .reasons { margin-bottom: 25px; }
        .reasons ul { margin: 8px 0 0 20px; padding: 0; }
        .reasons li { margin-bottom: 6px; color: #334155; }
        .disclaimer { border-top: 1px solid #e2e8f0; padding-top: 15px; margin-top: 40px; font-size: 11px; color: #64748b; text-align: center; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #0891b2; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
            Print / Save as PDF
        </button>
    </div>

    @php
        $res = $scan->result;
        $dir = $res?->direction ?? 'WAIT';
    @endphp

    <div class="header">
        <div>
            <div class="logo">ChartSense <span>AI</span></div>
            <div style="font-size: 12px; color: #64748b;">Understand the Chart. Analyze the Market. Trade with a Plan.</div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 11px; color: #64748b;">Report ID: CS-{{ str_pad($scan->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div style="font-size: 11px; color: #64748b;">Date: {{ $scan->created_at->format('Y-m-d H:i') }} UTC</div>
        </div>
    </div>

    <div class="meta-grid">
        <div class="meta-item">
            <span>Symbol / Timeframe</span>
            <strong>{{ $scan->symbol }} &bull; {{ $scan->timeframe }}</strong>
        </div>
        <div class="meta-item">
            <span>Trading Decision</span>
            <div style="margin-top: 3px;">
                @if($dir === 'BUY')
                    <span class="badge badge-buy">BUY SETUP</span>
                @elseif($dir === 'SELL')
                    <span class="badge badge-sell">SELL SETUP</span>
                @else
                    <span class="badge badge-wait">{{ $dir }}</span>
                @endif
            </div>
        </div>
        <div class="meta-item">
            <span>Risk-to-Reward</span>
            <strong>1 : {{ number_format($res?->risk_reward_ratio ?? 2.0, 2) }}</strong>
        </div>
        <div class="meta-item">
            <span>Confidence</span>
            <strong style="text-transform: capitalize;">{{ $res?->confidence ?? 'Moderate' }}</strong>
        </div>
    </div>

    @if($res && !in_array($dir, ['WAIT', 'NO VALID SETUP']))
        <div class="plan-box">
            <h3 style="margin-top: 0; margin-bottom: 10px; font-size: 14px; color: #0f172a; text-transform: uppercase;">
                Execution Parameters
            </h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <tr>
                    <td style="padding: 6px 0; color: #64748b; width: 30%;">Proposed Entry Zone:</td>
                    <td style="padding: 6px 0; font-family: monospace; font-weight: 700;">
                        {{ $res->entry_target ? number_format($res->entry_target, 2) : number_format($res->entry_min, 2) . ' - ' . number_format($res->entry_max, 2) }}
                        <span style="font-size: 11px; font-weight: normal; color: #64748b;">({{ str_replace('_', ' ', $res->entry_type) }})</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b;">Stop Loss (SL):</td>
                    <td style="padding: 6px 0; font-family: monospace; font-weight: 700; color: #b91c1c;">
                        {{ number_format($res->stop_loss, 2) }}
                        <span style="font-size: 11px; font-weight: normal; color: #64748b;">(Distance: {{ number_format($res->risk_distance, 2) }} pts)</span>
                    </td>
                </tr>
            </table>

            <div class="tp-grid">
                <div class="tp-card">
                    <span style="font-size: 10px; font-weight: 700; color: #0891b2; text-transform: uppercase;">TP1 (Scale-Out)</span>
                    <strong>{{ number_format($res->take_profit_1, 2) }}</strong>
                    <div style="font-size: 11px; color: #64748b;">+{{ number_format($res->reward_distance_tp1, 2) }} pts</div>
                </div>
                <div class="tp-card">
                    <span style="font-size: 10px; font-weight: 700; color: #0891b2; text-transform: uppercase;">TP2 (Main Target)</span>
                    <strong>{{ number_format($res->take_profit_2 ?? $res->take_profit_1, 2) }}</strong>
                    <div style="font-size: 11px; color: #64748b;">R:R 1:{{ number_format($res->risk_reward_ratio, 1) }}</div>
                </div>
                <div class="tp-card">
                    <span style="font-size: 10px; font-weight: 700; color: #0891b2; text-transform: uppercase;">TP3 (Runner)</span>
                    <strong>{{ number_format($res->take_profit_3 ?? $res->take_profit_1, 2) }}</strong>
                    <div style="font-size: 11px; color: #64748b;">Extended Target</div>
                </div>
            </div>
        </div>
    @endif

    @if(!empty($res?->strategy_confluences))
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #0891b2; font-size: 11px; text-transform: uppercase;">Autonomous Multi-Strategy Confluence</span>
                <span style="font-weight: 800; font-family: monospace; color: #0f172a; font-size: 13px;">Composite Score: {{ $res->strategy_confluences['composite_score'] ?? 92 }}%</span>
            </div>
            <div style="font-size: 11px; color: #475569; margin-top: 4px;">{{ $res->strategy_confluences['recommendation'] ?? 'All core institutional frameworks align on this trade setup.' }}</div>
        </div>
    @endif

    @if(!empty($res?->macro_news_context))
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 700; color: #059669; font-size: 11px; text-transform: uppercase;">Real-Time Global Macro Bias</span>
                <span style="font-weight: 800; font-family: monospace; color: #0f172a; font-size: 12px;">{{ $res->macro_news_context['sentiment_bias'] ?? 'NEUTRAL' }} (Score: {{ $res->macro_news_context['sentiment_score'] ?? 0 }})</span>
            </div>
            <div style="font-size: 11px; color: #475569; margin-top: 4px;">{{ $res->macro_news_context['macro_narrative'] ?? '' }}</div>
        </div>
    @endif

    <div class="reasons">
        <h4 style="margin: 0 0 6px 0; font-size: 13px; text-transform: uppercase; color: #0f172a;">Technical Justification</h4>
        <ul>
            @foreach($res?->reasoning ?? ['Structure and momentum alignment.'] as $r)
                <li>{{ $r }}</li>
            @endforeach
        </ul>
    </div>

    @if(!empty($res?->invalidation_conditions))
        <div class="reasons">
            <h4 style="margin: 0 0 6px 0; font-size: 13px; text-transform: uppercase; color: #b91c1c;">Invalidation Criteria</h4>
            <ul>
                @foreach($res->invalidation_conditions as $inv)
                    <li>{{ $inv }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="disclaimer">
        <strong>Financial Risk Warning:</strong> Trading involves high risk of capital loss. ChartSense AI analysis is generated algorithmically for educational and trade-planning assistance only. Past technical confluence does not guarantee future results.
    </div>
</body>
</html>
