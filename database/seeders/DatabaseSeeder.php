<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PaymentSetting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(StockSeeder::class);

        // High Security Admin Setup
        // 1. Ensure the ONLY authorized admin exists
        User::updateOrCreate(
            ['email' => 'mandeepkumar.ltd@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => 'Nextstep@2766',
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // 2. SECURITY ENFORCEMENT: Revoke admin privileges from ALL other users
        // This ensures no one else can access the admin panel
        User::where('email', '!=', 'mandeepkumar.ltd@gmail.com')
            ->where('is_admin', true) // improved performance by targeting only admins
            ->update(['is_admin' => false]);

        // Payment Settings
        $settings = [
            ['method' => 'bank', 'key' => 'Bank Name', 'value' => 'Global Finance Bank'],
            ['method' => 'bank', 'key' => 'Account Number', 'value' => '1234567890'],
            ['method' => 'bank', 'key' => 'SWIFT Code', 'value' => 'GFBINTLXXXX'],
            ['method' => 'crypto', 'key' => 'BTC Address', 'value' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa'],
            ['method' => 'crypto', 'key' => 'ETH Address', 'value' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e'],
        ];

        foreach ($settings as $setting) {
            PaymentSetting::firstOrCreate(
                ['method' => $setting['method'], 'key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
