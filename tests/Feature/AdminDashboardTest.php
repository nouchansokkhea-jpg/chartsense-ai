<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@chartsense.ai',
            'password' => Hash::make('AdminSecret123!'),
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $this->regularUser = User::create([
            'name' => 'Normal Trader',
            'email' => 'trader@example.com',
            'password' => Hash::make('TraderSecret123!'),
            'role' => 'user',
            'account_status' => 'active',
        ]);
    }

    public function test_regular_user_cannot_access_admin_portal(): void
    {
        $this->actingAs($this->regularUser);

        $response = $this->get('/admin');
        $response->assertStatus(403);

        $responseUsers = $this->get('/admin/users');
        $responseUsers->assertStatus(403);

        $responseSettings = $this->get('/admin/settings');
        $responseSettings->assertStatus(403);
    }

    public function test_admin_can_access_dashboard_overview(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Admin');
        $response->assertSee('Registered Traders');
        $response->assertSee('Pending Review Queue');
    }

    public function test_admin_can_view_and_toggle_user_roles(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('Normal Trader');

        // Toggle regularUser to admin
        $toggleResponse = $this->post(route('admin.users.toggle-role', $this->regularUser));
        $toggleResponse->assertSessionHas('success');
        $this->assertEquals('admin', $this->regularUser->fresh()->role);

        // Toggle back to user
        $this->post(route('admin.users.toggle-role', $this->regularUser));
        $this->assertEquals('user', $this->regularUser->fresh()->role);
    }

    public function test_admin_cannot_demote_or_suspend_their_own_account(): void
    {
        $this->actingAs($this->admin);

        // Demote self
        $toggleResponse = $this->post(route('admin.users.toggle-role', $this->admin));
        $toggleResponse->assertSessionHas('error');
        $this->assertEquals('admin', $this->admin->fresh()->role);

        // Suspend self
        $suspendResponse = $this->post(route('admin.users.suspend', $this->admin));
        $suspendResponse->assertSessionHas('error');
        $this->assertEquals('active', $this->admin->fresh()->account_status);
    }

    public function test_admin_can_suspend_and_restore_user(): void
    {
        $this->actingAs($this->admin);

        // Suspend user
        $this->post(route('admin.users.suspend', $this->regularUser));
        $this->assertEquals('suspended', $this->regularUser->fresh()->account_status);

        // Restore user
        $this->post(route('admin.users.restore', $this->regularUser));
        $this->assertEquals('active', $this->regularUser->fresh()->account_status);
    }

    public function test_admin_can_update_system_and_ai_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.settings.update'), [
            'exness_referral_url' => 'https://one.exnessonelink.com/a/l4l2nsdkfb?source=app',
            'ai_provider' => 'claude',
            'ai_model' => 'claude-3-5-sonnet-20241022',
            'referral_verification_mode' => 'manual_review',
            'site_notice' => 'System maintenance planned for Sunday 02:00 UTC.',
        ]);

        $response->assertRedirect(route('admin.settings'));
        $response->assertSessionHas('success');

        $this->assertEquals('claude', SystemSetting::get('ai_provider'));
        $this->assertEquals('claude-3-5-sonnet-20241022', SystemSetting::get('ai_model'));
        $this->assertEquals('System maintenance planned for Sunday 02:00 UTC.', SystemSetting::get('site_notice'));
    }

    public function test_admin_can_view_system_audit_logs(): void
    {
        AuditLog::log('test_security_event', 'Security', '101', ['detail' => 'Audit check'], $this->admin->id);

        $this->actingAs($this->admin);

        $response = $this->get('/admin/audit-logs');
        $response->assertStatus(200);
        $response->assertSee('test_security_event');
    }
}
