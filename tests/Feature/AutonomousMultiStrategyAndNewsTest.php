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

class AutonomousMultiStrategyAndNewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;
    protected string $realChartPath;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->trader = User::create([
            'name' => 'Autonomous Trader',
            'email' => 'pro_trader@chartsense.ai',
            'password' => Hash::make('Password@123456'),
            'role' => 'user',
            'account_status' => 'active',
        ]);

        ReferralVerification::create([
            'user_id' => $this->trader->id,
            'exness_account_id' => '198273645',
            'status' => 'VERIFIED',
            'verification_method' => 'manual_review',
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now()->subHour(),
        ]);

        $this->realChartPath = 'C:/Users/nouch/.gemini/antigravity-ide/brain/74cdfdf1-d8ca-44f2-9184-82564b42fcb6/.user_uploaded/media_1792673906948.png';
    }

    public function test_scanner_auto_detects_pair_and_timeframe_and_includes_live_news_and_confluences(): void
    {
        $this->actingAs($this->trader);

        $chartContent = file_exists($this->realChartPath)
            ? file_get_contents($this->realChartPath)
            : file_get_contents('C:/Users/nouch/.gemini/antigravity-ide/brain/74cdfdf1-d8ca-44f2-9184-82564b42fcb6/.user_uploaded/media_1792671110055.png');

        $file = UploadedFile::fake()->createWithContent('tradingview_gold_chart.png', $chartContent);

        $response = $this->post(route('scans.store'), [
            'symbol' => 'AUTO',
            'timeframe' => 'AUTO',
            'strategy' => 'comprehensive',
            'input_type' => 'screenshot',
            'chart_image' => $file,
        ]);

        $scan = Scan::where('user_id', $this->trader->id)->latest('id')->first();
        $this->assertNotNull($scan);
        $response->assertRedirect(route('scans.show', $scan));

        // Verify auto-detected pair and timeframe
        $this->assertNotEmpty($scan->symbol);
        $this->assertNotEmpty($scan->timeframe);

        $result = $scan->result;
        $this->assertNotNull($result);

        // Verify Multi-Strategy Confluences are present
        $this->assertNotNull($result->strategy_confluences);
        $this->assertArrayHasKey('composite_score', $result->strategy_confluences);
        $this->assertArrayHasKey('strategies', $result->strategy_confluences);
        $this->assertArrayHasKey('smc', $result->strategy_confluences['strategies']);
        $this->assertArrayHasKey('price_action', $result->strategy_confluences['strategies']);
        $this->assertArrayHasKey('market_structure', $result->strategy_confluences['strategies']);
        $this->assertArrayHasKey('fibonacci', $result->strategy_confluences['strategies']);

        // Verify Live Macro News Context is present
        $this->assertNotNull($result->macro_news_context);
        $this->assertArrayHasKey('sentiment_bias', $result->macro_news_context);
        $this->assertArrayHasKey('macro_narrative', $result->macro_news_context);
        $this->assertArrayHasKey('headlines', $result->macro_news_context);

        // View Scan Show Page
        $showResponse = $this->get(route('scans.show', $scan));
        $showResponse->assertOk();
        $showResponse->assertSee('Multi-Strategy Confluence Matrix');
        $showResponse->assertSee('Smart Money / ICT');
        $showResponse->assertSee('Pure Price Action');
        $showResponse->assertSee('Fibonacci Matrix');
        $showResponse->assertSee('Real-Time World Macro News');

        // Test Printable PDF View
        $pdfResponse = $this->get(route('scans.pdf', $scan));
        $pdfResponse->assertOk();
        $pdfResponse->assertSee('Autonomous Multi-Strategy Confluence');
    }
}
