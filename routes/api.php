<?php

use App\Services\DisbursementService;
use App\Services\LoanScheduleServiceVersionTwo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoanDecisionController;

use App\Http\Controllers\BillerController;

use App\Http\Controllers\PaymentCallbackController;


use App\Http\Controllers\LukuCallbackController;

use App\Http\Controllers\Api\BillingController;

use App\Http\Controllers\LukuGatewayController;

use App\Models\RoleMenuAction;

use App\Http\Controllers\Api\TransactionProcessingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('test', [\App\Http\Controllers\TestController::class, 'test']);

Route::post('testApi',[DisbursementService::class,'testAPi'])->name('api.loan_test');

Route::middleware('auth:sanctum')->get('/user', [\App\Http\Controllers\TestController::class, 'getUser']);

Route::post('institution-product-info',[\App\Http\Controllers\InstitutionInformationApi::class,'getInstitution'])->name('institution-info');
Route::post('bank_funds_transfer_request',[\App\Http\Controllers\InstitutionInformationApi::class,'internalBankTransfer'])->name('institution-request');
//Route::get('bank_funds_transfer_request', function (){
//    return 123;
//});



Route::post('/loan-decision', [LoanDecisionController::class, 'processLoanDecision']);




// Route::prefix('billers')->group(function () {
//     Route::get('/', [BillerController::class, 'index']);
//     Route::get('/category/{category}', [BillerController::class, 'byCategory']);
// });


Route::post('/nbc/payment/callback', [PaymentCallbackController::class, 'handlePaymentCallback'])->name('nbc.payment.callback');


Route::post('/luku/callback', [LukuCallbackController::class, 'handleCallback'])->name('luku.callback');


// // GEPG Routes
// Route::prefix('gepg')->group(function () {
//     Route::get('/payment', \App\Http\Livewire\GepgPaymentProcessor::class)->name('gepg.payment');
//     Route::post('/callback', [\App\Http\Controllers\GepgCallbackController::class, 'handleCallback'])->name('gepg.callback');
// });

// NBC Payment Callback
Route::post('v1/nbc-payments/callback', [App\Http\Controllers\Api\V1\NbcPaymentCallbackController::class, 'handle'])
    ->name('api.v1.nbc-payments.callback');

Route::prefix('billing')->group(function () {
    Route::post('/inquiry', [BillingController::class, 'inquiry']);
    Route::post('/payment-notify', [BillingController::class, 'paymentNotification']);
    Route::post('/status-check', [BillingController::class, 'status']); // updated
});

// Luku Gateway Routes
Route::prefix('luku-gateway')->group(function () {
    Route::post('/meter/lookup', [LukuGatewayController::class, 'meterLookup']);
    Route::post('/payment', [LukuGatewayController::class, 'processPayment']);
    Route::post('/token/status', [LukuGatewayController::class, 'checkTokenStatus']);
    Route::post('/callback', [LukuGatewayController::class, 'paymentCallback']);
});

Route::middleware(['auth:sanctum'])->post('/check-menu-action', [\App\Http\Controllers\TestController::class, 'checkMenuAction']);

// Account Setup Routes
Route::post('/accounts/setup', [App\Http\Controllers\Api\AccountSetupController::class, 'setupAccounts'])
    ->name('api.accounts.setup');

// Account Details API Routes
Route::prefix('v1')->group(function () {
    Route::post('/account-details', [App\Http\Controllers\Api\V1\AccountDetailsController::class, 'getAccountDetails']);
    Route::get('/account-details/test', [App\Http\Controllers\Api\V1\AccountDetailsController::class, 'testConnectivity']);
    Route::get('/account-details/stats', [App\Http\Controllers\Api\V1\AccountDetailsController::class, 'getStatistics']);
});

// Secure API Routes with Authentication and IP Whitelisting
Route::middleware(['api.key', 'ip.whitelist', 'security.headers'])->prefix('secure')->group(function () {
    // Transaction Processing API
    Route::post('/transactions/process', [TransactionProcessingController::class, 'process'])
        ->name('api.secure.transactions.process');
    
    // Transaction Status API
    Route::get('/transactions/{reference}/status', [TransactionProcessingController::class, 'getStatus'])
        ->name('api.secure.transactions.status');
    
    // Transaction History API
    Route::get('/transactions', [TransactionProcessingController::class, 'getHistory'])
        ->name('api.secure.transactions.history');
});

// API Key Management Routes (requires web authentication)
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::apiResource('api-keys', \App\Http\Controllers\Api\ApiKeyController::class);
    Route::post('/api-keys/{id}/regenerate', [\App\Http\Controllers\Api\ApiKeyController::class, 'regenerate'])
        ->name('api.admin.api-keys.regenerate');
    Route::get('/api-keys/{id}/stats', [\App\Http\Controllers\Api\ApiKeyController::class, 'stats'])
        ->name('api.admin.api-keys.stats');
});

// Legacy route (deprecated - use secure route above)
Route::post('/transactions/process', [TransactionProcessingController::class, 'process'])
    ->middleware(['api.key', 'ip.whitelist'])
    ->name('api.transactions.process');

// AI Agent Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ai-agent/ask', [App\Http\Controllers\AiAgentController::class, 'ask']);
    Route::post('/ai-agent/chat', [App\Http\Controllers\AiAgentController::class, 'chat']);
});

// Loan Disbursement API Routes
Route::middleware(['api.key', 'ip.whitelist', 'security.headers'])->prefix('v1/loans')->group(function () {
    // Simplified automatic loan creation and disbursement (only requires client_number and amount)
    Route::post('/auto-disburse', [App\Http\Controllers\Api\LoanDisbursementController::class, 'autoDisburse'])
        ->name('api.v1.loans.auto-disburse');
    
    // Single loan disbursement
    Route::post('/disburse', [App\Http\Controllers\Api\LoanDisbursementController::class, 'disburse'])
        ->name('api.v1.loans.disburse');
    
    // Bulk loan disbursement
    Route::post('/bulk-disburse', [App\Http\Controllers\Api\LoanDisbursementController::class, 'bulkDisburse'])
        ->name('api.v1.loans.bulk-disburse');
    
    // Get disbursement status
    Route::get('/disbursement/{transactionId}/status', [App\Http\Controllers\Api\LoanDisbursementController::class, 'status'])
        ->name('api.v1.loans.disbursement.status');
});
