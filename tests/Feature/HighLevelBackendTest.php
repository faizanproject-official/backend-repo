<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class HighLevelBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_status_is_200_ok()
    {
        $response = $this->get('/');
        // Laravel default landing page or 404 is fine, we just want to ensure app doesn't crash 500
        $response->assertStatus(200); 
    }

    public function test_user_can_login_and_get_token()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);
    }

    public function test_deposit_is_idempotent_double_spending_prevention()
    {
        $user = User::factory()->create(['funding_balance' => 0]);
        Sanctum::actingAs($user);

        // Simulate a Payment Service Call (Since we can't mock Stripe easily in simple test without Mockery setup, 
        // we will test the SERVICE directly or the Transaction Model constraints)
        
        // Let's test the Service constraint via the Controller logic if possible, 
        // or better, verify the database constraint we added.

        // 1. Create a transaction with a reference
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => 100,
            'status' => 'completed',
            'transaction_reference' => 'STRIPE_TX_12345',
            'payment_gateway' => 'stripe'
        ]);

        // 2. Try to insert the EXACT SAME reference again
        try {
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => 100,
                'status' => 'completed',
                'transaction_reference' => 'STRIPE_TX_12345', // Duplicate!
                'payment_gateway' => 'stripe'
            ]);
            $this->fail("Database should have rejected duplicate transaction_reference");
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString('integrity constraint violation', $e->getMessage());
            // SQLite or MySQL error for unique constraint
        }
    }

    public function test_atomic_transfer_logic()
    {
        $user = User::factory()->create([
            'funding_balance' => 1000,
            'holding_balance' => 0
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/transfer', [
            'amount' => 100,
            'from' => 'funding',
            'to' => 'holding'
        ]);

        $response->assertStatus(200);
        
        // Verify Database State
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'funding_balance' => 900,
            'holding_balance' => 100
        ]);
    }
}
