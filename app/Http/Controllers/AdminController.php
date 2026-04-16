<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\KycRecord;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * List all registered users.
     */
    public function indexUsers()
    {
        $users = User::with('kycRecord')->orderBy('created_at', 'desc')->get();
        return response()->json($users);
    }

    /**
     * List all KYC submissions (Pending first).
     */
    public function pendingKyc()
    {
        $allRecords = KycRecord::with('user')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($allRecords);
    }

    /**
     * Update KYC status (Approve or Reject).
     */
    public function updateKycStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string|nullable',
        ]);

        $kyc = KycRecord::findOrFail($id);
        $kyc->update([
            'status' => $request->status,
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'message' => "KYC status updated to {$request->status} successfully.",
            'data' => $kyc
        ]);
    }

    /**
     * Delete a user (optional but useful for admins).
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function transferBalance(Request $request, \App\Services\TransactionService $transactionService)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'from' => 'required|in:funding,holding',
            'to' => 'required|in:funding,holding|different:from',
        ]);

        try {
            $result = $transactionService->transferBalance(
                $request->user(),
                $request->from,
                $request->to,
                (float)$request->amount
            );

            return response()->json([
                'message' => 'Transfer successful',
                'user' => $result['user']
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function indexTransactions()
    {
        $transactions = Transaction::with('user')->orderBy('created_at', 'desc')->get();
        return response()->json($transactions);
    }

    /**
     * List transactions for the currently authenticated user (for client deposit history).
     */
    public function userTransactions(Request $request)
    {
        $user = $request->user();

        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($transactions);
    }

    /**
     * Get payment settings for bank and crypto.
     */
    public function getPaymentSettings()
    {
        $settings = \App\Models\PaymentSetting::all()->groupBy('method');
        return response()->json($settings);
    }

    /**
     * Store a pending deposit request.
     */
    public function storeDepositRequest(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string',
        ]);

        $user = $request->user();

        // Professional Crypto Address Logic
        $cryptoDetails = null;
        if ($request->method === 'crypto') {
            // Generate professional-looking simulated addresses
            // In production, integrate with Coinbase Commerce or BlockCypher
            $btcAddress = 'bc1q' . substr(hash('sha256', $user->id . time() . 'btc_salt'), 0, 38);
            $ethAddress = '0x' . substr(hash('sha256', $user->id . time() . 'eth_salt'), 0, 40);
            
            $cryptoDetails = [
                'btc_address' => $btcAddress,
                'eth_address' => $ethAddress,
                'network' => 'Native SegWit (BTC) / ERC-20 (ETH)',
                'expiry_minutes' => 60
            ];
        }

        $transaction = \App\Models\Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => $request->amount,
            'from_account' => $request->method, 
            'to_account' => 'funding',
            'status' => 'pending',
            'payment_gateway' => $request->method === 'crypto' ? 'crypto_gateway' : 'system',
            'transaction_reference' => 'DEP-' . strtoupper(uniqid()) . '-' . date('dmY'),
        ]);

        return response()->json([
             'message' => 'Deposit initiated. Please complete payment.',
             'transaction' => $transaction,
             'crypto_details' => $cryptoDetails,
             'status' => 'pending_payment'
        ]);
    }

    /**
     * Update transaction status (Approve/Reject).
     */
    public function updateTransactionStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:completed,rejected',
        ]);

        $transaction = Transaction::findOrFail($id);
        
        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'Transaction is already processed.'], 422);
        }

        $transaction->status = $request->status;
        $transaction->save();

        if ($request->status === 'completed') {
            if ($transaction->type === 'deposit') {
                 // Deposit approved: Just mark as completed. Admin manages balance manually.
                 // $user = $transaction->user;
                 // $user->funding_balance += $transaction->amount;
                 // $user->save();
            }
            // If type is 'withdraw', balance was already deducted on creation.
        } elseif ($request->status === 'rejected') {
             if ($transaction->type === 'withdraw') {
                 // Withdrawal rejected: Just mark as rejected. Admin manages refund manually if needed.
                 // $user = $transaction->user;
                 // $user->funding_balance += $transaction->amount;
                 // $user->save();
             }
             // If type is 'deposit' and rejected, we just mark it rejected. No balance change.
        }

        return response()->json([
            'message' => "Transaction {$request->status} successfully.",
            'transaction' => $transaction
        ]);
    }
    public function indexStocks()
    {
        $stocks = \App\Models\Stock::all();
        return response()->json($stocks);
    }
    public function withdraw(Request $request, \App\Services\TransactionService $transactionService)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10',
            'method' => 'required|string|in:bank,crypto',
            'details' => 'required|array',
        ]);

        $user = $request->user();

        // 1. CRITICAL: Enforce KYC Approval
        // "10 withdrawals required 10 KYCs" - Validation
        if (!$user->kycRecord || $user->kycRecord->status !== 'approved') {
             return response()->json([
                'message' => 'KYC verification required. Identity verification is one-time use per withdrawal. Please verify again.'
            ], 403);
        }

        try {
            $transaction = $transactionService->handleWithdrawal(
                $user,
                (float) $request->amount,
                $request->method,
                $request->details
            );

            // 2. CRITICAL: Reset KYC status
            // This consumes the KYC approval, forcing re-verification for the NEXT withdrawal.
            $user->kycRecord->update([
                'status' => 'expired', // Forces user to resubmit KYC
                'rejection_reason' => 'Previous verification used for withdrawal #' . $transaction->id . '. Please verify again.'
            ]);

            return response()->json([
                'message' => 'Withdrawal submitted successfully. Please complete KYC again for your next withdrawal.',
                'transaction' => $transaction,
                'balance' => $user->fresh()->funding_balance
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
