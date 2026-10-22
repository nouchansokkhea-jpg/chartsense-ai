<?php

namespace Tests\Feature;

use App\Models\ReferralEvidence;
use App\Models\ReferralVerification;
use App\Models\Scan;
use App\Models\ScanInput;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_all_responses(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_scan_screenshot_endpoint_prevents_unauthorized_user_access(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $attacker = User::factory()->create(['role' => 'user']);

        ReferralVerification::create([
            'user_id' => $attacker->id,
            'status' => 'VERIFIED',
        ]);

        $scan = Scan::create([
            'user_id' => $owner->id,
            'symbol' => 'XAUUSD',
            'timeframe' => 'M15',
            'input_type' => 'screenshot',
            'strategy' => 'smc',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($attacker)->get(route('scans.screenshot', $scan));
        $response->assertStatus(403);
    }

    public function test_scan_screenshot_endpoint_prevents_arbitrary_file_read_outside_storage(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $scan = Scan::create([
            'user_id' => $user->id,
            'symbol' => 'BTCUSD',
            'timeframe' => 'H1',
            'input_type' => 'screenshot',
            'strategy' => 'price_action',
            'status' => 'completed',
        ]);

        // Malicious entry pointing outside allowed storage
        ScanInput::create([
            'scan_id' => $scan->id,
            'input_type' => 'screenshot',
            'screenshot_path' => base_path('.env'),
        ]);

        $response = $this->actingAs($user)->get(route('scans.screenshot', $scan));
        $response->assertStatus(403);
    }
}
