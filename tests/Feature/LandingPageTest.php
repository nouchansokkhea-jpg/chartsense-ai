<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /**
     * Test the landing page returns 200 and contains ChartSense branding.
     */
    public function test_landing_page_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('ChartSense');
        $response->assertSee('Understand the Chart');
        $response->assertSee('https://one.exnessonelink.com/a/l4l2nsdkfb?source=app', false);
    }

    /**
     * Test legal policy pages render successfully.
     */
    public function test_legal_pages_render_successfully(): void
    {
        $this->get('/terms')->assertStatus(200)->assertSee('Terms of Service');
        $this->get('/privacy')->assertStatus(200)->assertSee('Privacy Policy');
        $this->get('/risk-disclosure')->assertStatus(200)->assertSee('Risk Disclosure Statement');
    }

    /**
     * Test that seeded database contains admin and users.
     */
    public function test_database_contains_seeded_accounts(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@chartsense.ai')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->canAccessScanner());

        $verifiedTrader = User::where('email', 'trader@chartsense.ai')->first();
        $this->assertNotNull($verifiedTrader);
        $this->assertEquals('VERIFIED', $verifiedTrader->referralStatus());
        $this->assertTrue($verifiedTrader->canAccessScanner());

        $newTrader = User::where('email', 'newtrader@chartsense.ai')->first();
        $this->assertNotNull($newTrader);
        $this->assertEquals('NOT_STARTED', $newTrader->referralStatus());
        $this->assertFalse($newTrader->canAccessScanner());
    }
}
