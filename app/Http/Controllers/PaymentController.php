<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
        ]);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // Use integer math to avoid floating point issues and attach user metadata
            $amountInCents = (int) round($request->amount * 100);

            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => strtolower($request->currency),
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'user_id' => optional($request->user())->id,
                ],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function confirmPayment(Request $request, \App\Services\PaymentService $paymentService, \App\Services\TransactionService $transactionService)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        try {
            // 1. Verify with Stripe
            $intent = $paymentService->verifyIntent($request->payment_intent_id);

            if ($intent->status !== 'succeeded') {
                return response()->json(['error' => 'Payment not successful'], 400);
            }

            $user = $request->user();
            
            // 2. Security Check: Ownership
            $intentUserId = $intent->metadata->user_id ?? null;
            if (!$intentUserId || (int) $intentUserId !== (int) $user->id) {
                Log::warning('Payment confirmation user mismatch', [
                    'intent_user_id' => $intentUserId,
                    'auth_user_id' => $user->id,
                ]);
                return response()->json(['error' => 'Unauthorized payment intent'], 403);
            }

            $amount = $intent->amount / 100;

            // 3. Process Deposit Atomically & Idempotently
            // The TransactionService checks if this payment_intent_id was already processed
            $transaction = $transactionService->handleDeposit(
                $user, 
                $amount, 
                $intent->id, // Use Stripe ID as unique reference
                'stripe'
            );

            if (!$transaction) {
                 return response()->json(['message' => 'Transaction already processed'], 200);
            }

            return response()->json([
                'message' => 'Payment confirmed and balance updated',
                'balance' => $user->fresh()->funding_balance
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe confirmPayment failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Payment confirmation failed'], 500);
        }
    }
}
