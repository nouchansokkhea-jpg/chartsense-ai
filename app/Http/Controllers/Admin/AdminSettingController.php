<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingController extends Controller
{
    /**
     * Show system settings view.
     */
    public function index(): View
    {
        $settings = SystemSetting::all()->keyBy('key');
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update system settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exness_referral_url' => ['required', 'url'],
            'ai_provider' => ['required', 'string', 'in:gemini,claude,openai,deepseek,deterministic'],
            'ai_model' => ['required', 'string', 'max:100'],
            'referral_verification_mode' => ['required', 'string', 'in:manual_review,official_api'],
            'collaboration_debate_rounds' => ['nullable', 'integer', 'min:0', 'max:3'],
            'collaboration_cross_review_depth' => ['nullable', 'string', 'in:standard,deep'],
            'firewall_enabled' => ['nullable', 'string', 'in:true,false,1,0'],
            'firewall_critical_block_minutes' => ['nullable', 'integer', 'min:5', 'max:180'],
            'firewall_high_block_minutes' => ['nullable', 'integer', 'min:5', 'max:120'],
            'firewall_post_stabilization_minutes' => ['nullable', 'integer', 'min:5', 'max:120'],
            'telegram_alerts_enabled' => ['nullable', 'string', 'in:true,false,1,0'],
            'site_notice' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set($key, $value);
        }

        AuditLog::log('system_settings_updated', 'SystemSetting', null, $validated, null, $request->user()->id);

        return redirect()->route('admin.settings')->with('success', 'System, AI, and News Risk Firewall settings updated successfully.');
    }

    /**
     * Display system audit logs.
     */
    public function auditLogs(Request $request): View
    {
        $query = AuditLog::with(['user', 'admin'])->latest('id');

        if ($request->filled('action')) {
            $query->where('action', 'ilike', '%' . trim($request->action) . '%');
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }
}
