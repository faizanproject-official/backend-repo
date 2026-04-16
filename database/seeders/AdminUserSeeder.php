<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin exists
        $admin = User::where('email', 'admin@example.com')->first();
        
        if (!$admin) {
            User::create([
                'name' => 'Admin User',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@example.com',
                'password' => 'password', // Casts will handle hashing
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);
            $this->command->info('Admin user created: admin@example.com / password');
        } else {
            // Update password to be sure
            $admin->password = 'password';
            $admin->is_admin = true;
            $admin->save();
            $this->command->info('Admin user updated: admin@example.com / password');
        }

        // Create a regular user for testing
        $user = User::where('email', 'user@example.com')->first();
        if (!$user) {
            User::create([
                'name' => 'Test User',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'user@example.com',
                'password' => 'password',
                'is_admin' => false,
                'email_verified_at' => now(),
            ]);
            $this->command->info('Test user created: user@example.com / password');
        } else {
            $user->password = 'password';
            $user->save();
            $this->command->info('Test user updated: user@example.com / password');
        }
    }
}
