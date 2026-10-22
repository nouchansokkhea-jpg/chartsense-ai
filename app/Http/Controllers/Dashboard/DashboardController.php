<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display user trading dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $totalScans = Scan::where('user_id', $user->id)->count();
        $recentScans = Scan::where('user_id', $user->id)
            ->with(['result', 'input'])
            ->latest('id')
            ->limit(5)
            ->get();

        $buyCount = Scan::where('user_id', $user->id)
            ->whereHas('result', fn($q) => $q->where('direction', 'BUY'))
            ->count();

        $sellCount = Scan::where('user_id', $user->id)
            ->whereHas('result', fn($q) => $q->where('direction', 'SELL'))
            ->count();

        $waitCount = Scan::where('user_id', $user->id)
            ->whereHas('result', fn($q) => $q->whereIn('direction', ['WAIT', 'NO VALID SETUP']))
            ->count();

        $referralStatus = $user->referralStatus();
        $canScan = $user->canAccessScanner();

        return view('dashboard.index', compact(
            'user',
            'totalScans',
            'recentScans',
            'buyCount',
            'sellCount',
            'waitCount',
            'referralStatus',
            'canScan'
        ));
    }
}
