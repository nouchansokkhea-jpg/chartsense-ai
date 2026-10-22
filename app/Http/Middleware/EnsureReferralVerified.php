<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReferralVerified
{
    /**
     * Handle an incoming request.
     * Ensure the user has an active, verified Exness referral before accessing the scanner.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->guest(route('login'));
        }

        if ($user->isSuspended()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Your account has been suspended. Please contact support.');
        }

        if (!$user->canAccessScanner()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Scanner access is locked. Exness referral verification required.',
                    'referral_status' => $user->referralStatus(),
                    'redirect_url' => route('referral-access'),
                ], 403);
            }

            return redirect()->route('referral-access')->with('warning', 'Scanner access is locked. Please join Exness via our official referral link and complete verification to unlock the scanner.');
        }

        return $next($request);
    }
}
