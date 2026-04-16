<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Events\BalanceUpdated; // We will create this next
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Process a balance transfer between accounts atomically.
     * Prevents Race Conditions using pessimistic locking.
     */
    public function transferBalance(User $user, string $fromAccount, string $toAccount, float $amount)
    {
        return DB::transaction(function () use ($user, $fromAccount, $toAccount, $amount) {
            // 1. Lock the user row for update to prevent concurrent modifications
            $lockedUser = User::lockForUpdate()->find($user->id);

            $fromField = $fromAccount . '_balance';
            $toField = $toAccount . '_balance';

            // 2. Validate Balance on the LOCKED row
            if ($lockedUser->$fromField < $amount) {
                throw new \Exception("Insufficient balance in {$fromAccount} account.");
            }

            // 3. Perform Updates
            $lockedUser->$fromField -= $amount;
            $lockedUser->$toField += $amount;
            $lockedUser->save();

            // 4. Create Transaction Record
            $transaction = Transaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'transfer',
                'amount' => $amount,
                'from_account' => $fromAccount,
                'to_account' => $toAccount,
                'status' => 'completed',
                'payment_gateway' => 'system',
                'transaction_reference' => 'TRX-' . uniqid() . '-' . time(),
            ]);

            // 5. Broadcast Real-Time Event
            broadcast(new BalanceUpdated($lockedUser));

            return [
                'user' => $lockedUser,
                'transaction' => $transaction
            ];
        });
    }

    /**
     * Process a confirmed deposit.
     * Idempotent: Checks for duplicate transaction references.
     */
    public function handleDeposit(User $user, float $amount, string $referenceId, string $gateway = 'stripe')
    {
        return DB::transaction(function () use ($user, $amount, $referenceId, $gateway) {
            // 1. Idempotency Check
            if (Transaction::where('transaction_reference', $referenceId)->exists()) {
                 Log::info("Duplicate deposit attempt blocked: {$referenceId}");
                 return null; // Or throw specific exception
            }

            // 2. Lock User
            $lockedUser = User::lockForUpdate()->find($user->id);

            // 3. Update Balance
            $lockedUser->funding_balance += $amount;
            $lockedUser->save();

            // 4. Create Record
            $transaction = Transaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'deposit',
                'amount' => $amount,
                'from_account' => 'external',
                'to_account' => 'funding',
                'status' => 'completed',
                'payment_gateway' => $gateway,
                'transaction_reference' => $referenceId,
            ]);

            // 5. Broadcast Event
            broadcast(new BalanceUpdated($lockedUser));

            return $transaction;
        });
    }
    /**
     * Process a withdrawal request atomically.
     */
    public function handleWithdrawal(User $user, float $amount, string $method, array $details)
    {
        // Enforce KYC Check
        if (!$user->kycRecord || $user->kycRecord->status !== 'approved') {
            throw new \Exception("KYC verification required for withdrawals. Please complete your KYC verification first.");
        }

        return DB::transaction(function () use ($user, $amount, $method, $details) {
            // 1. Lock User
            $lockedUser = User::lockForUpdate()->find($user->id);

            // 2. Validate Balance
            // We usually withdraw from 'funding_balance' or 'holding_balance'. Assuming 'funding_balance' for now.
            if ($lockedUser->funding_balance < $amount) {
                throw new \Exception("Insufficient funding balance.");
            }

            // 3. Deduct Balance
            $lockedUser->funding_balance -= $amount;
            $lockedUser->save();

            // 4. Create Transaction Record (Pending Approval)
            $transaction = Transaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'withdraw',
                'amount' => $amount,
                'from_account' => 'funding',
                'to_account' => 'external',
                'payment_gateway' => $method, // 'bank', 'crypto'
                'status' => 'pending', // High-level security: always manual approval for large withdrawals
                'transaction_reference' => 'WD-' . uniqid() . '-' . time(),
                // In a real app, we would store $details (bank info) in a separate table or meta column
            ]);

            // 5. Broadcast Event
            broadcast(new BalanceUpdated($lockedUser));

            return $transaction;
        });
    }
}
