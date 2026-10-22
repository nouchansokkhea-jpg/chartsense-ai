<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ReferralEvidence;
use App\Models\ReferralVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReferralVerificationGateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $trader;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@chartsense.ai',
            'password' => Hash::make('AdminSecret123!'),
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $this->trader = User::create([
            'name' => 'Trader Bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('BobSecret123!'),
            'role' => 'user',
            'account_status' => 'active',
        ]);

        ReferralVerification::create([
            'user_id' => $this->trader->id,
            'status' => 'NOT_STARTED',
            'verification_method' => 'manual_review',
        ]);
    }

    public function test_unverified_trader_is_blocked_from_scanner_and_redirected_to_referral_access(): void
    {
        $this->actingAs($this->trader);

        // Attempt to visit protected scanner page
        $response = $this->get('/scanner');

        $response->assertRedirect(route('referral-access'));
        $response->assertSessionHas('warning');
    }

    public function test_trader_can_submit_referral_verification_with_screenshot_evidence(): void
    {
        $this->actingAs($this->trader);

        $fakeFile = UploadedFile::fake()->image('exness_dashboard.png', 800, 600);

        $response = $this->post('/referral-access/submit', [
            'exness_account_id' => 'EX-8829104',
            'evidence_file' => $fakeFile,
            'notes' => 'Registered through partner link l4l2nsdkfb yesterday.',
        ]);

        $response->assertRedirect(route('referral-access'));
        $response->assertSessionHas('success');

        $verification = ReferralVerification::where('user_id', $this->trader->id)->first();
        $this->assertEquals('PENDING_VERIFICATION', $verification->status);
        $this->assertEquals('EX-8829104', $verification->exness_account_id);
        $this->assertNotNull($verification->submitted_at);

        // Verify evidence was stored
        $this->assertDatabaseHas('referral_evidence', [
            'referral_verification_id' => $verification->id,
            'file_name' => 'exness_dashboard.png',
        ]);

        $evidence = ReferralEvidence::where('referral_verification_id', $verification->id)->first();
        Storage::disk('local')->assertExists($evidence->file_path);
    }

    public function test_admin_can_approve_verification_which_unlocks_scanner_for_trader(): void
    {
        // 1. Trader submits verification
        $verification = ReferralVerification::where('user_id', $this->trader->id)->first();
        $verification->update([
            'status' => 'PENDING_VERIFICATION',
            'exness_account_id' => 'EX-8829104',
            'submitted_at' => now(),
        ]);

        // 2. Admin logs in and approves verification
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.referrals.approve', $verification), [
            'notes' => 'Partner ID confirmed on Exness partner backend.',
        ]);

        $response->assertSessionHas('success');

        $verification->refresh();
        $this->assertEquals('VERIFIED', $verification->status);
        $this->assertEquals($this->admin->id, $verification->reviewed_by);
        $this->assertNotNull($verification->reviewed_at);

        // 3. Trader is now VERIFIED and can access scanner
        $this->actingAs($this->trader);
        $scannerResponse = $this->get('/scanner');
        $scannerResponse->assertStatus(200);
        $scannerResponse->assertSee('Chart Scanner');
    }

    public function test_admin_can_reject_verification(): void
    {
        $verification = ReferralVerification::where('user_id', $this->trader->id)->first();
        $verification->update([
            'status' => 'PENDING_VERIFICATION',
            'exness_account_id' => 'INVALID-ID-999',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('admin.referrals.reject', $verification), [
            'reason' => 'Account ID not found under partner link l4l2nsdkfb.',
            'notes' => 'Please re-register using our official referral URL.',
        ]);

        $response->assertSessionHas('warning');

        $verification->refresh();
        $this->assertEquals('REJECTED', $verification->status);
        $this->assertEquals('Account ID not found under partner link l4l2nsdkfb.', $verification->rejection_reason);

        // Trader remains blocked
        $this->actingAs($this->trader);
        $this->assertFalse($this->trader->fresh()->canAccessScanner());
        $this->get('/scanner')->assertRedirect(route('referral-access'));
    }

    public function test_admin_can_suspend_previously_verified_access(): void
    {
        $verification = ReferralVerification::where('user_id', $this->trader->id)->first();
        $verification->update([
            'status' => 'VERIFIED',
            'exness_account_id' => 'EX-8829104',
            'reviewed_at' => now(),
            'reviewed_by' => $this->admin->id,
        ]);

        $this->assertTrue($this->trader->fresh()->canAccessScanner());

        // Admin suspends
        $this->actingAs($this->admin);
        $response = $this->post(route('admin.referrals.suspend', $verification), [
            'reason' => 'Exness partner account was unlinked or closed.',
        ]);

        $response->assertSessionHas('warning');

        $verification->refresh();
        $this->assertEquals('SUSPENDED', $verification->status);
        $this->assertFalse($this->trader->fresh()->canAccessScanner());

        // Trader is re-locked from scanner
        $this->actingAs($this->trader);
        $this->get('/scanner')->assertRedirect(route('referral-access'));
    }

    public function test_clicking_referral_link_logs_audit_trail_and_redirects_away(): void
    {
        $this->actingAs($this->trader);

        $response = $this->get('/referral-access/click');

        $response->assertRedirect('https://one.exnessonelink.com/a/l4l2nsdkfb?source=app');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->trader->id,
            'action' => 'exness_referral_link_clicked',
        ]);
    }
}
