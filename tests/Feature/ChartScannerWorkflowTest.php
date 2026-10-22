<?php

namespace Tests\Feature;

use App\Models\ReferralVerification;
use App\Models\Scan;
use App\Models\User;
use App\Services\MarketData\MarketDataParser;
use App\Services\TechnicalAnalysis\TechnicalAnalysisService;
use App\Services\TechnicalAnalysis\TradingPlanValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChartScannerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $verifiedTrader;
    protected User $otherTrader;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        // Verified Trader
        $this->verifiedTrader = User::create([
            'name' => 'Verified Trader',
            'email' => 'trader@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user',
            'account_status' => 'active',
        ]);

        ReferralVerification::create([
            'user_id' => $this->verifiedTrader->id,
            'status' => 'VERIFIED',
            'verification_method' => 'manual_review',
            'exness_account_id' => 'EX-12345678',
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now()->subHours(10),
        ]);

        // Other Trader (also verified, for isolation testing)
        $this->otherTrader = User::create([
            'name' => 'Other Trader',
            'email' => 'other@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'user',
            'account_status' => 'active',
        ]);

        ReferralVerification::create([
            'user_id' => $this->otherTrader->id,
            'status' => 'VERIFIED',
            'verification_method' => 'manual_review',
            'exness_account_id' => 'EX-87654321',
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now()->subHours(10),
        ]);
    }

    /**
     * Generate synthetic CSV OHLCV candle data with an uptrend structure.
     */
    protected function generateSyntheticCsvContent(int $bars = 40): string
    {
        $csv = "timestamp,open,high,low,close,volume\n";
        $basePrice = 2000.0;
        $time = strtotime('2026-03-01 00:00:00');

        for ($i = 0; $i < $bars; $i++) {
            $open = $basePrice + ($i * 1.5) + (sin($i) * 2.0);
            $high = $open + 4.0;
            $low = $open - 2.0;
            $close = $open + 2.5;
            $vol = 1500 + ($i * 10);
            $date = date('Y-m-d H:i:s', $time + ($i * 3600));

            $csv .= "{$date},{$open},{$high},{$low},{$close},{$vol}\n";
        }

        return $csv;
    }

    public function test_technical_analysis_engine_calculates_all_core_indicators(): void
    {
        $csvContent = $this->generateSyntheticCsvContent(50);
        $parser = new MarketDataParser();
        $parsed = $parser->parseRawText($csvContent);
        $candles = $parsed['candles'];

        $this->assertCount(50, $candles);

        $taEngine = new TechnicalAnalysisService();
        $metrics = $taEngine->analyze($candles);

        $this->assertArrayHasKey('indicators', $metrics);
        $this->assertArrayHasKey('rsi_14', $metrics['indicators']);
        $this->assertArrayHasKey('macd', $metrics['indicators']);
        $this->assertArrayHasKey('atr_14', $metrics['indicators']);
        $this->assertArrayHasKey('ema_9', $metrics['indicators']);
        $this->assertArrayHasKey('ema_21', $metrics['indicators']);
        $this->assertArrayHasKey('ema_50', $metrics['indicators']);

        $this->assertArrayHasKey('market_structure', $metrics);
        $this->assertArrayHasKey('break_of_structure', $metrics['market_structure']);
        $this->assertArrayHasKey('change_of_character', $metrics['market_structure']);

        $this->assertArrayHasKey('support_resistance', $metrics);
        $this->assertArrayHasKey('signal', $metrics);
    }

    public function test_trading_plan_validator_enforces_price_geometry_and_rr_rules(): void
    {
        $validator = new TradingPlanValidator();

        // 1. Valid BUY Setup: SL < Entry < TP1 < TP2 < TP3
        $validBuy = [
            'direction' => 'BUY',
            'entry_price' => 2000.0,
            'stop_loss' => 1990.0,
            'take_profit_1' => 2020.0,
            'take_profit_2' => 2040.0,
            'take_profit_3' => 2060.0,
            'risk_reward_ratio' => 2.0,
            'warnings' => [],
        ];
        $validated = $validator->validateAndSanitize($validBuy);
        $this->assertEquals('BUY', $validated['direction']);
        $this->assertGreaterThan(1.0, $validated['risk_reward_ratio']);

        // 2. Invalid BUY Setup where Stop Loss is ABOVE Entry (Physical impossibility for long)
        $invalidBuy = [
            'direction' => 'BUY',
            'entry_price' => 2000.0,
            'stop_loss' => 2050.0, // SL above entry
            'take_profit_1' => 2020.0,
            'risk_reward_ratio' => 1.5,
            'warnings' => [],
        ];
        $sanitized = $validator->validateAndSanitize($invalidBuy);
        $this->assertEquals('NO VALID SETUP', $sanitized['direction']);
        $this->assertNotEmpty($sanitized['warnings']);

        // 3. Invalid SELL Setup where TP is HIGHER than entry
        $invalidSell = [
            'direction' => 'SELL',
            'entry_price' => 2000.0,
            'stop_loss' => 2010.0,
            'take_profit_1' => 2020.0, // TP above entry for short
            'risk_reward_ratio' => 2.0,
            'warnings' => [],
        ];
        $sanitizedSell = $validator->validateAndSanitize($invalidSell);
        $this->assertEquals('NO VALID SETUP', $sanitizedSell['direction']);
    }

    public function test_verified_user_can_submit_csv_scan_and_view_results(): void
    {
        $this->actingAs($this->verifiedTrader);

        $csvString = $this->generateSyntheticCsvContent(40);
        $file = UploadedFile::fake()->createWithContent('xauusd_h1.csv', $csvString);

        $response = $this->post(route('scans.store'), [
            'symbol' => 'XAUUSD',
            'timeframe' => 'H1',
            'strategy' => 'comprehensive',
            'input_type' => 'csv',
            'csv_file' => $file,
        ]);

        $scan = Scan::where('user_id', $this->verifiedTrader->id)->latest('id')->first();
        $this->assertNotNull($scan);
        $response->assertRedirect(route('scans.show', $scan));

        $this->assertEquals('completed', $scan->status);
        $this->assertNotNull($scan->result);
        $this->assertContains($scan->result->direction, ['BUY', 'SELL', 'WAIT', 'NO VALID SETUP']);

        // Verify scan report page renders
        $showResponse = $this->get(route('scans.show', $scan));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('XAUUSD');
        $showResponse->assertSee($scan->result->direction);
    }

    public function test_user_cannot_view_or_delete_another_users_private_scan(): void
    {
        $scan = Scan::create([
            'user_id' => $this->verifiedTrader->id,
            'symbol' => 'EURUSD',
            'timeframe' => 'M15',
            'status' => 'completed',
        ]);

        // Attempt viewing as otherTrader
        $this->actingAs($this->otherTrader);
        $this->get(route('scans.show', $scan))->assertStatus(403);
        $this->delete(route('scans.destroy', $scan))->assertStatus(403);
    }

    public function test_user_can_export_scan_csv_data(): void
    {
        $this->actingAs($this->verifiedTrader);

        $scan = Scan::create([
            'user_id' => $this->verifiedTrader->id,
            'symbol' => 'BTCUSD',
            'timeframe' => 'H4',
            'status' => 'completed',
        ]);

        $response = $this->get(route('scans.csv', $scan));
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_scan_show_page_renders_gracefully_when_scan_result_is_null(): void
    {
        $this->actingAs($this->verifiedTrader);

        // Create scan with completed or pending status but no scan_result relation
        $scan = Scan::create([
            'user_id' => $this->verifiedTrader->id,
            'symbol' => 'XAUUSD',
            'timeframe' => 'H1',
            'input_type' => 'screenshot',
            'data_source' => 'screenshot_upload',
            'strategy' => 'smc',
            'status' => 'completed',
        ]);

        $this->assertNull($scan->result);

        // Access the show view directly - must return 200 and not crash with 500
        $response = $this->get(route('scans.show', $scan));
        $response->assertStatus(200);
        $response->assertSee('XAUUSD');
        $response->assertSee('ChartSense AI Trading Plan');

        // Also test PDF/print route
        $pdfResponse = $this->get(route('scans.pdf', $scan));
        $pdfResponse->assertStatus(200);
    }
}
