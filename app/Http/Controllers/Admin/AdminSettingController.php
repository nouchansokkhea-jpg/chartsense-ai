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
            'ai_provider' => ['required', 'string', 'in:gemini,claude,openai'],
            'ai_model' => ['required', 'string', 'max:100'],
            'referral_verification_mode' => ['required', 'string', 'in:manual_review,official_api'],
            'site_notice' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::set($key, $value);
        }

        AuditLog::log('system_settings_updated', 'SystemSetting', null, $validated, null, $request->user()->id);

        return redirect()->route('admin.settings')->with('success', 'System and AI settings updated successfully.');
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
