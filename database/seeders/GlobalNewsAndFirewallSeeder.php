<?php

namespace Database\Seeders;

use App\Models\EventAssetMapping;
use App\Models\NewsProvider;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class GlobalNewsAndFirewallSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed News Providers
        $providers = [
            [
                'code' => 'forexfactory',
                'name' => 'Forex Factory Live Real-Time News & Release Wire',
                'type' => 'forexfactory',
                'base_url' => 'https://www.forexfactory.com/news',
                'is_active' => true,
                'rate_limit_per_minute' => 30,
                'health_status' => 'healthy',
                'last_sync_at' => now(),
            ],
            [
                'code' => 'forexlive',
                'name' => 'ForexLive Global Macro Feed',
                'type' => 'rss',
                'base_url' => 'https://www.forexlive.com/feed/news',
                'is_active' => true,
                'rate_limit_per_minute' => 60,
                'health_status' => 'healthy',
                'last_sync_at' => now(),
            ],
            [
                'code' => 'coindesk',
                'name' => 'CoinDesk Crypto & Liquidity Feed',
                'type' => 'rss',
                'base_url' => 'https://www.coindesk.com/arc/outboundfeeds/rss/',
                'is_active' => true,
                'rate_limit_per_minute' => 60,
                'health_status' => 'healthy',
                'last_sync_at' => now(),
            ],
            [
                'code' => 'calendar_feed',
                'name' => 'Global Economic Calendar Wire',
                'type' => 'calendar',
                'base_url' => 'https://nfs.faireconomy.media/ff_calendar_thisweek.json',
                'is_active' => true,
                'rate_limit_per_minute' => 30,
                'health_status' => 'healthy',
                'last_sync_at' => now(),
            ],
            [
                'code' => 'fed_reserve',
                'name' => 'Federal Reserve / US Official Wires',
                'type' => 'official',
                'base_url' => 'https://www.federalreserve.gov/feeds/press_all.xml',
                'is_active' => true,
                'rate_limit_per_minute' => 30,
                'health_status' => 'healthy',
                'last_sync_at' => now(),
            ],
            [
                'code' => 'ecb_official',
                'name' => 'European Central Bank Wire',
                'type' => 'official',
                'base_url' => 'https://www.ecb.europa.eu/rss/press.html',
                'is_active' => true,
                'rate_limit_per_minute' => 30,
                'health_status' => 'healthy',
                'last_sync_at' => now(),
            ],
            [
                'code' => 'boj_official',
                'name' => 'Bank of Japan Official Wire',
                'type' => 'official',
                'base_url' => 'https://www.boj.or.jp/en/rss/press.xml',
                'is_active' => true,
                'rate_limit_per_minute' => 30,
                'health_status' => 'healthy',
                'last_sync_at' => now(),
            ],
        ];

        foreach ($providers as $p) {
            NewsProvider::updateOrCreate(['code' => $p['code']], $p);
        }

        // 2. Seed Event-Asset Mappings
        $mappings = [
            [
                'event_pattern' => 'CPI',
                'currency' => 'USD',
                'affected_symbols' => ['XAUUSD', 'EURUSD', 'GBPUSD', 'USDJPY', 'USDCAD', 'USDCHF', 'US30', 'NAS100', 'SPX500', 'USOIL'],
                'pre_event_block_minutes' => 30,
                'post_event_stabilization_minutes' => 20,
            ],
            [
                'event_pattern' => 'Nonfarm',
                'currency' => 'USD',
                'affected_symbols' => ['XAUUSD', 'EURUSD', 'GBPUSD', 'USDJPY', 'USDCAD', 'US30', 'NAS100', 'SPX500'],
                'pre_event_block_minutes' => 30,
                'post_event_stabilization_minutes' => 25,
            ],
            [
                'event_pattern' => 'FOMC',
                'currency' => 'USD',
                'affected_symbols' => ['XAUUSD', 'EURUSD', 'GBPUSD', 'USDJPY', 'USDCAD', 'USDCHF', 'US30', 'NAS100', 'SPX500', 'BTCUSD'],
                'pre_event_block_minutes' => 45,
                'post_event_stabilization_minutes' => 30,
            ],
            [
                'event_pattern' => 'Interest Rate',
                'currency' => 'USD',
                'affected_symbols' => ['XAUUSD', 'EURUSD', 'GBPUSD', 'USDJPY', 'US30', 'NAS100'],
                'pre_event_block_minutes' => 45,
                'post_event_stabilization_minutes' => 30,
            ],
            [
                'event_pattern' => 'ECB',
                'currency' => 'EUR',
                'affected_symbols' => ['EURUSD', 'EURJPY', 'EURGBP', 'EURCAD', 'EURAUD', 'GER40'],
                'pre_event_block_minutes' => 30,
                'post_event_stabilization_minutes' => 20,
            ],
            [
                'event_pattern' => 'BOJ',
                'currency' => 'JPY',
                'affected_symbols' => ['USDJPY', 'EURJPY', 'GBPJPY', 'AUDJPY', 'CADJPY'],
                'pre_event_block_minutes' => 30,
                'post_event_stabilization_minutes' => 25,
            ],
            [
                'event_pattern' => 'Bank of England',
                'currency' => 'GBP',
                'affected_symbols' => ['GBPUSD', 'GBPJPY', 'EURGBP', 'GBPAUD', 'UK100'],
                'pre_event_block_minutes' => 30,
                'post_event_stabilization_minutes' => 20,
            ],
            [
                'event_pattern' => 'OPEC',
                'currency' => 'USD',
                'affected_symbols' => ['USOIL', 'UKOIL', 'USDCAD'],
                'pre_event_block_minutes' => 30,
                'post_event_stabilization_minutes' => 30,
            ],
        ];

        foreach ($mappings as $m) {
            EventAssetMapping::updateOrCreate(
                ['event_pattern' => $m['event_pattern'], 'currency' => $m['currency']],
                $m
            );
        }

        // 3. Seed System Settings for News Firewall
        $settings = [
            [
                'key' => 'firewall_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Master switch for News Risk Firewall pre-event trade blocking.',
                'is_public' => true,
            ],
            [
                'key' => 'firewall_critical_block_minutes',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Minutes before CRITICAL events (FOMC, CPI, NFP) to block new trade entries.',
                'is_public' => true,
            ],
            [
                'key' => 'firewall_high_block_minutes',
                'value' => '15',
                'type' => 'integer',
                'description' => 'Minutes before HIGH events (PPI, PMI, GDP) to block new trade entries.',
                'is_public' => true,
            ],
            [
                'key' => 'firewall_post_stabilization_minutes',
                'value' => '15',
                'type' => 'integer',
                'description' => 'Minutes to hold post-release stabilization restriction before restoring normal entry eligibility.',
                'is_public' => true,
            ],
            [
                'key' => 'telegram_notifications_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Global broadcast of high-impact events and trade restrictions to Telegram.',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $s) {
            SystemSetting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
