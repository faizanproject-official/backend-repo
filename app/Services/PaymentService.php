<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Create a Stripe Payment Intent.
     */
    public function createIntent($user, float $amount, string $currency = 'usd')
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $amountInCents = (int) round($amount * 100);

        return PaymentIntent::create([
            'amount' => $amountInCents,
            'currency' => strtolower($currency),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);
    }

    /**
     * Verify Stripe Payment Intent Status.
     */
    public function verifyIntent(string $paymentIntentId)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        return PaymentIntent::retrieve($paymentIntentId);
    }
}
