<?php

namespace App\Http\Controllers\Referral;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Referral\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralAccessController extends Controller
{
    public function __construct(
        protected ReferralService $referralService
    ) {}

    /**
     * Display the Exness Referral Access status & onboarding page.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $verification = $user->latestReferralVerification;
        $status = $user->referralStatus();

        $exnessUrl = config('services.exness.referral_url', env('EXNESS_REFERRAL_URL', 'https://one.exnessonelink.com/a/l4l2nsdkfb?source=app'));

        return view('referral.access', compact('user', 'verification', 'status', 'exnessUrl'));
    }

    /**
     * Handle submission of referral verification data.
     */
    public function submit(Request $request): RedirectResponse
    {
        $request->validate([
            'exness_account_id' => ['required', 'string', 'max:100'],
            'evidence_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,pdf', 'max:15360'], // 15MB max
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'exness_account_id.required' => 'Please provide your Exness account ID or wallet number.',
            'evidence_file.mimes' => 'The evidence file must be an image (PNG, JPG, WebP) or PDF document.',
            'evidence_file.max' => 'The evidence file cannot exceed 15MB.',
        ]);

        $this->referralService->submitVerification(
            $request->user(),
            $request->input('exness_account_id'),
            $request->file('evidence_file'),
            $request->input('notes')
        );

        return redirect()->route('referral-access')->with('success', 'Your Exness referral verification request has been submitted for review! Our team will audit your partner account shortly.');
    }

    /**
     * Log referral link click audit event when user clicks "Join Exness".
     */
    public function recordLinkClick(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            AuditLog::log('exness_referral_link_clicked', 'User', (string)$user->id, [
                'official_url' => env('EXNESS_REFERRAL_URL', 'https://one.exnessonelink.com/a/l4l2nsdkfb?source=app'),
            ], $user->id);
        }

        $exnessUrl = env('EXNESS_REFERRAL_URL', 'https://one.exnessonelink.com/a/l4l2nsdkfb?source=app');
        return redirect()->away($exnessUrl);
    }
}
