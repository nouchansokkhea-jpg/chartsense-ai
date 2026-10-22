<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralEvidence;
use App\Models\ReferralVerification;
use App\Services\Referral\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminReferralController extends Controller
{
    public function __construct(
        protected ReferralService $referralService
    ) {}

    /**
     * Display list of referral verifications.
     */
    public function index(Request $request): View
    {
        $query = ReferralVerification::with(['user', 'reviewer', 'evidence'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('exness_account_id', 'ilike', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'ilike', "%{$search}%")
                         ->orWhere('name', 'ilike', "%{$search}%");
                  });
            });
        }

        $verifications = $query->paginate(15)->withQueryString();

        return view('admin.referrals.index', compact('verifications'));
    }

    /**
     * Approve verification request.
     */
    public function approve(Request $request, ReferralVerification $verification): RedirectResponse
    {
        $notes = $request->input('notes', 'Approved by administrator.');
        $this->referralService->approveVerification($verification, $request->user(), $notes);

        return redirect()->back()->with('success', "Referral for user [{$verification->user->email}] approved! Scanner access is now UNLOCKED.");
    }

    /**
     * Reject verification request.
     */
    public function reject(Request $request, ReferralVerification $verification): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'reason.required' => 'Please provide a clear reason for rejecting this verification.',
        ]);

        $this->referralService->rejectVerification(
            $verification,
            $request->user(),
            $request->input('reason'),
            $request->input('notes')
        );

        return redirect()->back()->with('warning', "Referral for user [{$verification->user->email}] has been REJECTED.");
    }

    /**
     * Suspend scanner access for an approved verification.
     */
    public function suspend(Request $request, ReferralVerification $verification): RedirectResponse
    {
        $reason = $request->input('reason', 'Access suspended by administrator audit.');
        $this->referralService->suspendAccess($verification, $request->user(), $reason);

        return redirect()->back()->with('warning', "Scanner access for user [{$verification->user->email}] has been SUSPENDED.");
    }

    /**
     * Securely download or view private evidence file.
     */
    public function downloadEvidence(Request $request, ReferralEvidence $evidence): BinaryFileResponse
    {
        if (!Storage::disk('local')->exists($evidence->file_path)) {
            abort(404, 'Evidence file not found.');
        }

        $fullPath = Storage::disk('local')->path($evidence->file_path);
        $realPath = realpath($fullPath);
        $allowedBase = realpath(storage_path('app'));
        if (!$realPath || !str_starts_with($realPath, $allowedBase)) {
            abort(403, 'Unauthorized file location.');
        }

        return response()->download(
            $realPath,
            $evidence->file_name
        );
    }
}
