<?php

namespace Database\Seeders;

use App\Models\ReferralVerification;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Administrator Account
        $admin = User::firstOrCreate(
            ['email' => 'admin@chartsense.ai'],
            [
                'name' => 'ChartSense Admin',
                'password' => Hash::make('Password@123456'),
                'role' => 'admin',
                'account_status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // 2. Verified Trader Account (for immediate scanner testing)
        $verifiedTrader = User::firstOrCreate(
            ['email' => 'trader@chartsense.ai'],
            [
                'name' => 'Demo Verified Trader',
                'password' => Hash::make('Password@123456'),
                'role' => 'user',
                'account_status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        ReferralVerification::firstOrCreate(
            ['user_id' => $verifiedTrader->id],
            [
                'status' => 'VERIFIED',
                'verification_method' => 'test_verified',
                'exness_account_id' => 'EX-19827402',
                'submitted_at' => now()->subDay(),
                'reviewed_at' => now()->subHours(12),
                'reviewed_by' => $admin->id,
                'admin_notes' => 'Pre-verified test account for development and demonstration.',
            ]
        );

        // 3. New Unverified Trader Account (to demonstrate referral lock gate)
        $newTrader = User::firstOrCreate(
            ['email' => 'newtrader@chartsense.ai'],
            [
                'name' => 'Demo New Trader',
                'password' => Hash::make('Password@123456'),
                'role' => 'user',
                'account_status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        ReferralVerification::firstOrCreate(
            ['user_id' => $newTrader->id],
            [
                'status' => 'NOT_STARTED',
                'verification_method' => 'manual_review',
                'submitted_at' => null,
            ]
        );

        // 4. Default System Settings
        $defaultSettings = [
            [
                'key' => 'exness_referral_url',
                'value' => 'https://one.exnessonelink.com/a/l4l2nsdkfb?source=app',
                'type' => 'string',
                'description' => 'Official Exness referral registration link for scanner access verification.',
                'is_public' => true,
            ],
            [
                'key' => 'referral_verification_mode',
                'value' => 'manual_review',
                'type' => 'string',
                'description' => 'Verification mode: manual_review or official_api.',
                'is_public' => false,
            ],
            [
                'key' => 'ai_provider',
                'value' => 'gemini',
                'type' => 'string',
                'description' => 'Active AI engine provider: gemini, claude, or openai.',
                'is_public' => false,
            ],
            [
                'key' => 'ai_model',
                'value' => 'gemini-2.5-flash',
                'type' => 'string',
                'description' => 'Active AI model name for vision and structured trading analysis.',
                'is_public' => false,
            ],
            [
                'key' => 'site_notice',
                'value' => 'ChartSense AI analyzes technical structures and risk/reward setups. Trade responsibly.',
                'type' => 'string',
                'description' => 'Announcement bar notice text displayed on the site.',
                'is_public' => true,
            ],
        ];

        foreach ($defaultSettings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
