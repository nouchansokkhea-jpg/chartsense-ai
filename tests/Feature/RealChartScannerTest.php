<?php

namespace Tests\Feature;

use App\Models\ReferralVerification;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RealChartScannerTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;
    protected string $realUserImagePath;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->trader = User::create([
            'name' => 'Institutional Trader',
            'email' => 'trader@chartsense.ai',
            'password' => Hash::make('Password@123456'),
            'role' => 'user',
            'account_status' => 'active',
        ]);

        ReferralVerification::create([
            'user_id' => $this->trader->id,
            'status' => 'VERIFIED',
            'verification_method' => 'manual_review',
            'exness_account_id' => 'EX-99882211',
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now()->subHour(),
        ]);

        $this->realUserImagePath = 'C:/Users/nouch/.gemini/antigravity-ide/brain/74cdfdf1-d8ca-44f2-9184-82564b42fcb6/.user_uploaded/media_1792671110055.png';
    }

    public function test_scanner_analyzes_real_xauusd_chart_and_generates_institutional_sell_plan(): void
    {
        $this->assertTrue(file_exists($this->realUserImagePath), 'The user-uploaded chart screenshot must exist on the system.');

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'xauusd_1h_tradingview.png',
            file_get_contents($this->realUserImagePath)
        );

        $this->actingAs($this->trader);

        $response = $this->post(route('scans.store'), [
            'symbol' => 'XAUUSD',
            'timeframe' => 'H1',
            'strategy' => 'smc',
            'input_type' => 'screenshot',
            'chart_image' => $uploadedFile,
            'notes' => 'Bearish reaction at 4196-4211 supply order block with FVG target.',
        ]);

        $scan = Scan::where('user_id', $this->trader->id)->latest('id')->first();
        $this->assertNotNull($scan, 'Scan should be created in database.');
        $response->assertRedirect(route('scans.show', $scan));

        $this->assertEquals('completed', $scan->status);
        $this->assertEquals('XAUUSD', $scan->symbol);
        $this->assertEquals('H1', $scan->timeframe);

        $result = $scan->result;
        $this->assertNotNull($result, 'Scan result should be generated.');

        // Validate Direction
        $this->assertEquals('SELL', $result->direction);

        // Validate Key Structural Levels
        $this->assertEquals(4211.55, (float)$result->stop_loss);
        $this->assertEquals(4177.61, (float)$result->entry_target);
        $this->assertEquals(4140.00, (float)$result->take_profit_1);
        $this->assertEquals(4099.93, (float)$result->take_profit_2);
        $this->assertEquals(4065.40, (float)$result->take_profit_3);

        // Validate Geometry: SL > Entry > TP1 > TP2 > TP3
        $this->assertGreaterThan((float)$result->entry_target, (float)$result->stop_loss);
        $this->assertLessThan((float)$result->entry_target, (float)$result->take_profit_1);
        $this->assertLessThan((float)$result->take_profit_1, (float)$result->take_profit_2);
        $this->assertLessThan((float)$result->take_profit_2, (float)$result->take_profit_3);

        // Validate Risk-Reward Ratio (TP1 conservative ratio >= 1.1, full TP3 ratio >= 3.0)
        $this->assertEquals(1.11, (float)$result->risk_reward_ratio);
        $fullRr = round(((float)$result->entry_target - (float)$result->take_profit_3) / (float)$result->risk_distance, 2);
        $this->assertGreaterThanOrEqual(3.0, $fullRr);

        // Verify the user view page loads cleanly with all trade setup data
        $showResponse = $this->get(route('scans.show', $scan));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('SELL');
        $showResponse->assertSee('4,211.55');
        $showResponse->assertSee('4,177.61');
        $showResponse->assertSee('4,140.00');
        $showResponse->assertSee('4,065.40');
        $showResponse->assertSee('Fair Value Gap');
        $showResponse->assertSee('Order Block');
    }

    public function test_scanner_analyzes_clean_tradingview_breakdown_chart_with_real_prices(): void
    {
        $cleanChartPath = 'C:/Users/nouch/.gemini/antigravity-ide/brain/74cdfdf1-d8ca-44f2-9184-82564b42fcb6/.user_uploaded/media_1792673906948.png';
        if (!file_exists($cleanChartPath)) {
            $this->markTestSkipped('Clean chart test media not found.');
        }

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'xauusd_m15_breakdown.png',
            file_get_contents($cleanChartPath)
        );

        $this->actingAs($this->trader);

        $response = $this->post(route('scans.store'), [
            'symbol' => 'XAUUSD',
            'timeframe' => 'M15',
            'strategy' => 'smc',
            'input_type' => 'screenshot',
            'chart_image' => $uploadedFile,
        ]);

        $scan = Scan::where('user_id', $this->trader->id)->latest('id')->first();
        $this->assertNotNull($scan);
        $response->assertRedirect(route('scans.show', $scan));

        $result = $scan->result;
        $this->assertNotNull($result);

        // Direction must be SELL (real downtrend, NOT fake 2735 buy!)
        $this->assertEquals('SELL', $result->direction);

        // Real market prices extracted from chart (around 4,085 - 4,120)
        $this->assertGreaterThan(4000.0, (float)$result->entry_target);
        $this->assertGreaterThan(4000.0, (float)$result->stop_loss);
        $this->assertGreaterThan((float)$result->entry_target, (float)$result->stop_loss);
        $this->assertLessThan((float)$result->entry_target, (float)$result->take_profit_1);

        // Verify show page renders with the real prices
        $showResponse = $this->get(route('scans.show', $scan));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('SELL');
        $showResponse->assertSee('4,090');
    }
}
