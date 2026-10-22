<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ReferralVerification;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Display the Admin Overview Dashboard.
     */
    public function index(Request $request): View
    {
        $totalUsers = User::count();
        $verifiedReferrals = ReferralVerification::where('status', 'VERIFIED')->count();
        $pendingReferrals = ReferralVerification::where('status', 'PENDING_VERIFICATION')->count();
        $rejectedReferrals = ReferralVerification::where('status', 'REJECTED')->count();
        $suspendedUsers = User::where('account_status', 'suspended')->count();

        $totalScans = Scan::count();
        $completedScans = Scan::where('status', 'completed')->count();
        $failedScans = Scan::where('status', 'failed')->count();

        $recentVerifications = ReferralVerification::with(['user', 'evidence'])
            ->where('status', 'PENDING_VERIFICATION')
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        $recentScans = Scan::with(['user', 'result'])
            ->latest('id')
            ->limit(5)
            ->get();

        $recentAuditLogs = AuditLog::with(['user', 'admin'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'verifiedReferrals',
            'pendingReferrals',
            'rejectedReferrals',
            'suspendedUsers',
            'totalScans',
            'completedScans',
            'failedScans',
            'recentVerifications',
            'recentScans',
            'recentAuditLogs'
        ));
    }
}
