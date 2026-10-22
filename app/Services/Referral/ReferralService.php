<?php

namespace App\Services\Referral;

use App\Models\AuditLog;
use App\Models\ReferralEvidence;
use App\Models\ReferralVerification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReferralService
{
    /**
     * Submit an Exness referral verification request for a user.
     */
    public function submitVerification(User $user, string $exnessAccountId, ?UploadedFile $evidenceFile = null, ?string $notes = null): ReferralVerification
    {
        return DB::transaction(function () use ($user, $exnessAccountId, $evidenceFile, $notes) {
            $verification = ReferralVerification::firstOrNew(['user_id' => $user->id]);

            $verification->status = 'PENDING_VERIFICATION';
            $verification->verification_method = 'manual_review';
            $verification->exness_account_id = trim($exnessAccountId);
            $verification->submitted_at = now();
            $verification->rejection_reason = null; // Clear any previous rejection
            $verification->save();

            // Store evidence file in private storage if supplied
            if ($evidenceFile && $evidenceFile->isValid()) {
                $path = $evidenceFile->store('referral_evidence', 'local');

                ReferralEvidence::create([
                    'referral_verification_id' => $verification->id,
                    'file_path' => $path,
                    'file_name' => $evidenceFile->getClientOriginalName(),
                    'file_size' => $evidenceFile->getSize(),
                    'mime_type' => $evidenceFile->getMimeType() ?: 'application/octet-stream',
                    'description' => $notes ?: 'Exness cabinet account confirmation evidence',
                ]);
            }

            AuditLog::log('referral_verification_submitted', 'ReferralVerification', (string)$verification->id, [
                'exness_account_id' => $exnessAccountId,
                'has_evidence' => $evidenceFile !== null,
            ], $user->id);

            return $verification;
        });
    }

    /**
     * Approve verification (Admin action).
     */
    public function approveVerification(ReferralVerification $verification, User $admin, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($verification, $admin, $notes) {
            $verification->status = 'VERIFIED';
            $verification->reviewed_at = now();
            $verification->reviewed_by = $admin->id;
            if ($notes) {
                $verification->admin_notes = $notes;
            }
            $verification->save();

            AuditLog::log('referral_approved', 'ReferralVerification', (string)$verification->id, [
                'user_id' => $verification->user_id,
                'exness_account_id' => $verification->exness_account_id,
                'admin_notes' => $notes,
            ], $verification->user_id, $admin->id);

            return true;
        });
    }

    /**
     * Reject verification (Admin action).
     */
    public function rejectVerification(ReferralVerification $verification, User $admin, string $reason, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($verification, $admin, $reason, $notes) {
            $verification->status = 'REJECTED';
            $verification->reviewed_at = now();
            $verification->reviewed_by = $admin->id;
            $verification->rejection_reason = $reason;
            if ($notes) {
                $verification->admin_notes = $notes;
            }
            $verification->save();

            AuditLog::log('referral_rejected', 'ReferralVerification', (string)$verification->id, [
                'user_id' => $verification->user_id,
                'reason' => $reason,
                'admin_notes' => $notes,
            ], $verification->user_id, $admin->id);

            return true;
        });
    }

    /**
     * Suspend previously approved scanner access.
     */
    public function suspendAccess(ReferralVerification $verification, User $admin, string $reason): bool
    {
        return DB::transaction(function () use ($verification, $admin, $reason) {
            $verification->status = 'SUSPENDED';
            $verification->reviewed_at = now();
            $verification->reviewed_by = $admin->id;
            $verification->rejection_reason = $reason;
            $verification->save();

            AuditLog::log('referral_suspended', 'ReferralVerification', (string)$verification->id, [
                'user_id' => $verification->user_id,
                'reason' => $reason,
            ], $verification->user_id, $admin->id);

            return true;
        });
    }
}
