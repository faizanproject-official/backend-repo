<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\PaymentController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1'); // Limit: 5 attempts per minute
Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'reset']);
Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'store']);

Route::get('/user', function (Request $request) {
    return $request->user()->load('kycRecord');
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/kyc/submit', [KycController::class, 'submit']);
    Route::get('/kyc/status', [KycController::class, 'status']);
    Route::post('/payment/create-intent', [PaymentController::class, 'createPaymentIntent']);

    Route::post('/payment/confirm', [PaymentController::class, 'confirmPayment']);

    // Public Stocks Route for Authenticated Users
    Route::get('/stocks', [\App\Http\Controllers\StockController::class, 'index']);
    
    // Balance Transfer
    Route::post('/transfer', [AdminController::class, 'transferBalance']);

    // Authenticated user transaction history
    Route::get('/transactions', [AdminController::class, 'userTransactions']);
    
    // Deposit Request (User)
    Route::post('/deposit-request', [AdminController::class, 'storeDepositRequest']);

    // Support Ticket Routes
    Route::get('/support/tickets', [\App\Http\Controllers\SupportController::class, 'index']);
    Route::post('/support/tickets', [\App\Http\Controllers\SupportController::class, 'store']);
    Route::get('/support/tickets/{id}', [\App\Http\Controllers\SupportController::class, 'show']);
    Route::post('/support/tickets/{id}/reply', [\App\Http\Controllers\SupportController::class, 'reply']);
    Route::post('/support/tickets/{id}/close', [\App\Http\Controllers\SupportController::class, 'close']);

    Route::middleware('admin')->group(function () {
        Route::get('/admin/contacts', [\App\Http\Controllers\ContactController::class, 'index']);
        Route::get('/admin/me', [AdminController::class, 'me']);
        Route::get('/admin/users', [AdminController::class, 'indexUsers']);
        Route::get('/admin/stocks', [AdminController::class, 'indexStocks']);
        Route::get('/admin/kyc-pending', [AdminController::class, 'pendingKyc']);
        Route::post('/admin/kyc-update/{id}', [AdminController::class, 'updateKycStatus']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);
        Route::post('/admin/transfer', [AdminController::class, 'transferBalance']);
        Route::get('/admin/transactions', [AdminController::class, 'indexTransactions']);
        Route::get('/admin/payment-settings', [AdminController::class, 'getPaymentSettings']);
        // Route::post('/admin/deposit-request', [AdminController::class, 'storeDepositRequest']); // Moved to user routes
        Route::post('/admin/transaction-update/{id}', [AdminController::class, 'updateTransactionStatus']);
        
        // Admin Support Routes
        Route::get('/admin/support/tickets', [\App\Http\Controllers\SupportController::class, 'adminIndex']);
        Route::get('/admin/support/tickets/{id}', [\App\Http\Controllers\SupportController::class, 'adminShow']);
        Route::post('/admin/support/tickets/{id}/reply', [\App\Http\Controllers\SupportController::class, 'adminReply']);
        Route::post('/admin/support/tickets/{id}/close', [\App\Http\Controllers\SupportController::class, 'adminClose']);
    });
    
    
    // Withdrawal Routes (Authenticated Users)
    Route::post('/withdraw', [AdminController::class, 'withdraw']);
});

