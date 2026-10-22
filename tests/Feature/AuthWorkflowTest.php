<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_and_register_pages(): void
    {
        $this->get('/login')->assertStatus(200)->assertSee('Sign In');
        $this->get('/register')->assertStatus(200)->assertSee('Create your account');
    }

    public function test_user_can_register_and_is_initialized_with_not_started_referral(): void
    {
        $response = $this->post('/register', [
            'name' => 'Alex Trader',
            'email' => 'alex@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => 'on',
        ]);

        $response->assertRedirect(route('referral-access'));
        $this->assertAuthenticated();

        $user = User::where('email', 'alex@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('user', $user->role);
        $this->assertEquals('active', $user->account_status);
        $this->assertEquals('NOT_STARTED', $user->referralStatus());
        $this->assertFalse($user->canAccessScanner());
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $response = $this->post('/register', [
            'name' => 'Alex Trader',
            'email' => 'alex@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertSessionHasErrors(['terms']);
        $this->assertGuest();
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::create([
            'name' => 'Trader John',
            'email' => 'john@example.com',
            'password' => Hash::make('Secret123456!'),
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'Secret123456!',
        ]);

        $this->assertAuthenticatedAs($user);
        // Unverified user is directed to referral access
        $response->assertRedirect(route('referral-access'));
    }

    public function test_user_cannot_log_in_with_invalid_credentials(): void
    {
        User::create([
            'name' => 'Trader John',
            'email' => 'john@example.com',
            'password' => Hash::make('Secret123456!'),
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'WrongPassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_suspended_user_is_prevented_from_logging_in(): void
    {
        User::create([
            'name' => 'Suspended Trader',
            'email' => 'suspended@example.com',
            'password' => Hash::make('Secret123456!'),
            'role' => 'user',
            'account_status' => 'suspended',
        ]);

        $response = $this->post('/login', [
            'email' => 'suspended@example.com',
            'password' => 'Secret123456!',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::create([
            'name' => 'Trader John',
            'email' => 'john@example.com',
            'password' => Hash::make('Secret123456!'),
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/logout');
        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_user_can_update_profile_and_password(): void
    {
        $user = User::create([
            'name' => 'Trader John',
            'email' => 'john@example.com',
            'password' => Hash::make('OldPassword123!'),
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $this->actingAs($user);

        // Update name
        $response = $this->put('/settings/profile', [
            'name' => 'Trader John Updated',
            'email' => 'john@example.com',
        ]);
        $response->assertRedirect(route('profile.edit'));
        $this->assertEquals('Trader John Updated', $user->fresh()->name);

        // Update password
        $response = $this->put('/settings/profile/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);
        $response->assertRedirect(route('profile.edit'));
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->fresh()->password));
    }
}
