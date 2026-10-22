<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_google_sign_in_and_hides_quick_demo_credentials(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign in with Google');
        $response->assertDontSee('Quick Demo Credentials (Testing)');
    }

    public function test_register_page_shows_get_started_with_google(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Get started with Google');
    }

    public function test_google_redirect_route_redirects_to_account_chooser_when_no_keys(): void
    {
        config(['services.google.client_id' => '']);
        config(['services.google.client_secret' => '']);

        $response = $this->get('/auth/google');
        $response->assertRedirect(route('auth.google.select'));

        $selectResponse = $this->get(route('auth.google.select'));
        $selectResponse->assertStatus(200);
        $selectResponse->assertSee('Choose an account');
        $selectResponse->assertSee('to continue to');
        $selectResponse->assertSee('nouchansokhea0@gmail.com');
    }

    public function test_new_user_can_register_via_google_email(): void
    {
        $response = $this->post('/auth/google/dev-mock', [
            'email' => 'newtrader.google@gmail.com',
            'name' => 'Google Trader',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'newtrader.google@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Google Trader', $user->name);
        $this->assertNotNull($user->google_id);
        $this->assertNull($user->password);
        $this->assertEquals('NOT_STARTED', $user->referralStatus());
    }

    public function test_existing_user_can_sign_in_via_google_email(): void
    {
        $user = User::create([
            'name' => 'Existing Trader',
            'email' => 'existing@gmail.com',
            'password' => Hash::make('Secret123456!'),
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $response = $this->post('/auth/google/dev-mock', [
            'email' => 'existing@gmail.com',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertNotNull($user->google_id);
    }

    public function test_user_can_authenticate_via_firebase_google_session(): void
    {
        $response = $this->postJson(route('auth.firebase.session'), [
            'uid' => 'firebase_google_uid_12345',
            'email' => 'firebase.trader@gmail.com',
            'name' => 'Firebase Trader',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'redirect_url' => route('dashboard'),
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'firebase.trader@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Firebase Trader', $user->name);
        $this->assertEquals('firebase_google_uid_12345', $user->google_id);
        $this->assertEquals('https://example.com/avatar.jpg', $user->avatar);
        $this->assertEquals('NOT_STARTED', $user->referralStatus());
    }

    public function test_existing_user_linking_via_firebase_google_session(): void
    {
        $user = User::create([
            'name' => 'Existing Firebase Trader',
            'email' => 'existing.firebase@gmail.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user',
            'account_status' => 'active',
        ]);

        $response = $this->postJson(route('auth.firebase.session'), [
            'uid' => 'firebase_linked_uid_999',
            'email' => 'existing.firebase@gmail.com',
            'name' => 'Existing Firebase Trader',
            'avatar' => 'https://example.com/new_avatar.jpg',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertEquals('firebase_linked_uid_999', $user->google_id);
    }
}
