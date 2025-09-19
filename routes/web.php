<?php

use App\Models\departmentsList;
use App\Http\Livewire\VerifyOtp;
use App\Models\approvals;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataFeedController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Traits\MailSender;
use App\Migrations\CreateRegionsTable;
use App\Migrations\CreateDistrictsTable;
use App\Migrations\CreateWardsTable;


use App\Http\Controllers\TableExportController;
use App\Http\Controllers\BillingController;
use App\Http\Livewire\Users\Users;
use App\Http\Livewire\Users\Roles;
use App\Http\Livewire\Users\Permissions;
use App\Http\Livewire\Users\UserSettings;
use App\Http\Livewire\Users\AuditLogs;
use App\Http\Livewire\Departments\Departments;
use App\Http\Livewire\Loans\LoanCommittee;


Route::post('/export-table', [TableExportController::class, 'exportTableData'])->name('export.table');


Route::get('/microfinance_registration_link',[\App\Http\Controllers\CompanyRequest::class,'index']);

Route::post('registration/submition',[\App\Http\Controllers\CompanyRequest::class,'create'])->name('saccossRequestForm');

// NBC Routes - Must be placed before redirect to avoid conflicts
Route::get('/NBC/{memberNumber}/{clientNumber}', [App\Http\Controllers\NbcController::class, 'showMemberInfo'])
    ->name('nbc.member.info')
    ->where('memberNumber', '[0-9]+')
    ->where('clientNumber', '[0-9]+');

Route::post('/NBC/process-payment', [App\Http\Controllers\NbcController::class, 'processPayment'])
    ->name('nbc.process.payment');

// Redirect to login page
Route::redirect('/', 'login');

// Route for password reset form submission
Route::post('/password-reset', [\App\Http\Controllers\WebRoutesController::class, 'passwordReset'])->name('password-reset');

// Group routes that require authentication
Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    // Route for the main dashboard page
    Route::get('/system', \App\Http\Livewire\System::class)->name('system');

    // Members Portal Route
    Route::get('/members', App\Http\Livewire\MembersWebPortal\MembersWebPortal::class)->name('members.portal');

    // Route for OTP verification page
    Route::get('/verify-user', [\App\Http\Controllers\WebRoutesController::class, 'verifyUser'])->name('verify-user');

    // Route for generating and verifying OTP
    Route::get('/verify-account', [\App\Http\Controllers\WebRoutesController::class, 'verifyAccount'])->name('verify-account');

    // Mandatory Savings Management Route
    Route::get('/mandatory-savings', \App\Http\Livewire\Savings\MandatorySavingsManagement::class)->name('mandatory-savings');

    // Till and Cash Management Route (Development Access)
    Route::get('/till-cash-management', \App\Http\Livewire\Accounting\TillAndCashManagement::class)->name('till-cash-management');
    
    // Budget Management Routes
    Route::get('/budget-management', \App\Http\Livewire\BudgetManagement\EnhancedBudgetManager::class)->name('budget.management');
    Route::get('/budget-dashboard', \App\Http\Livewire\BudgetManagement\BudgetDashboard::class)->name('budget.dashboard');
    Route::get('/budget/report/view/{id}', function($id) {
        return redirect()->route('budget.management')->with('message', 'Report generated successfully');
    })->name('budget.report.view');

    Route::fallback([\App\Http\Controllers\WebRoutesController::class, 'fallback']);
});

Route::middleware(['auth'])->group(function () {
    // Billing Routes
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/create', [BillingController::class, 'create'])->name('billing.create');
    Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');
    Route::get('/billing/{bill}', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/{bill}/payment', [BillingController::class, 'processPayment'])->name('billing.process-payment');
    Route::get('/billing/status/{controlNumber}', [BillingController::class, 'checkStatus'])->name('billing.check-status');
    
    // Luku Payment Route
    Route::view('/payments/luku', 'payments.luku')->name('payments.luku');

    // GEPG Payment Route
    Route::view('/payments/gepg', 'payments.gepg')->name('payments.gepg');
    
    // Trade Receivables Invoice Routes
    Route::get('/receivables/invoice/view', function() {
        $invoiceId = session('view_invoice_id');
        if (!$invoiceId) {
            abort(404, 'Invoice not found');
        }
        
        $receivable = \DB::table('trade_receivables')->where('id', $invoiceId)->first();
        if (!$receivable || !$receivable->invoice_file_path) {
            abort(404, 'Invoice file not found');
        }
        
        $path = storage_path('app/' . $receivable->invoice_file_path);
        if (!file_exists($path)) {
            abort(404, 'Invoice file does not exist');
        }
        
        // Clear session
        session()->forget('view_invoice_id');
        
        return response()->file($path);
    })->name('view.invoice');
    
    Route::get('/receivables/invoice/download', function() {
        $path = session('download_invoice_path');
        $name = session('download_invoice_name', 'invoice.pdf');
        
        if (!$path || !file_exists($path)) {
            abort(404, 'Invoice file not found');
        }
        
        // Clear session
        session()->forget(['download_invoice_path', 'download_invoice_name']);
        
        return response()->download($path, $name);
    })->name('download.invoice');
});

// Payment Notification Webhook
Route::post('/api/payment-notification', [BillingController::class, 'handlePaymentNotification'])->name('billing.payment-notification');
Route::post('/api/gepg-callback', [BillingController::class, 'handleGepgCallback'])->name('gepg.callback');

// PDF Download Route
Route::get('/download-pdf', [\App\Http\Controllers\WebRoutesController::class, 'downloadPdf'])->name('download.pdf');

// CSV Download Route
Route::get('/download-csv', [\App\Http\Controllers\WebRoutesController::class, 'downloadCsv'])->name('download.csv');

// Loan Loss Report Download Route
Route::get('/loan-loss-report/download', [\App\Http\Controllers\LoanLossReportController::class, 'downloadCustomReport'])
    ->middleware('auth')
    ->name('loan-loss-report.download');

// AI Agent Routes
Route::middleware('auth')->group(function () {
    Route::get('/ai-agent', [\App\Http\Controllers\WebRoutesController::class, 'aiAgent'])->name('ai-agent.chat');
    Route::get('/prompt-logger', function() {
        return view('prompt-logger');
    })->name('prompt.logger');
    
    // Test route to verify AI agent is working
    Route::get('/ai-agent/test', [\App\Http\Controllers\WebRoutesController::class, 'aiAgentTest'])->name('ai-agent.test');
    
    // Streaming routes for real-time AI responses
    Route::post('/ai/process', [\App\Http\Controllers\StreamController::class, 'process'])
        ->name('ai.process');
    Route::get('/ai/stream/{sessionId}', [\App\Http\Controllers\StreamController::class, 'stream'])
        ->name('ai.stream');
    Route::post('/ai/stream/{sessionId}/complete', [\App\Http\Controllers\StreamController::class, 'complete'])
        ->name('ai.stream.complete');
});

// Email Routes
Route::middleware('auth')->group(function () {
    Route::get('/email', App\Http\Livewire\Email\Email::class)->name('email');
    Route::get('/email-outlook', App\Http\Livewire\Email\EmailOutlook::class)->name('email.outlook');
    Route::get('/email/signatures', App\Http\Livewire\Email\EmailSignatures::class)->name('email.signatures');
    Route::get('/email/templates', App\Http\Livewire\Email\EmailTemplates::class)->name('email.templates');
    Route::get('/email/rules', App\Http\Livewire\Email\EmailRules::class)->name('email.rules');
});

// Email Tracking Routes (no auth required for tracking)
Route::get('/email/track/pixel/{id}', [App\Http\Controllers\EmailTrackingController::class, 'trackPixel'])->name('email.tracking.pixel');
Route::get('/email/track/click/{tracking_id}', [App\Http\Controllers\EmailTrackingController::class, 'trackClick'])->name('email.tracking.click');

// Status message route
Route::get('/status/{status}', \App\Http\Livewire\StatusMessage::class)
    ->name('status.message')
    ->where('status', 'PENDING|BLOCKED|DELETED');
// Redirect to login page
Route::redirect('/', 'login');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    Route::get('/system/logs', [App\Http\Controllers\SystemLogsController::class, 'index'])->name('system.logs');
});

// Test route for AI conversation saving
Route::get('/test-ai-conversation', [\App\Http\Controllers\WebRoutesController::class, 'testAiConversation'])->middleware('auth');

// Terminal Console Route
Route::get('/terminal', function () {
    return view('terminal');
})->name('terminal');

// Test AI Routes (No Auth Required for Testing)
Route::post('/test-ai/process', [\App\Http\Controllers\StreamController::class, 'process'])
    ->withoutMiddleware(['auth'])
    ->name('test.ai.process');
Route::get('/test-ai/stream/{sessionId}', [\App\Http\Controllers\StreamController::class, 'stream'])
    ->withoutMiddleware(['auth'])
    ->name('test.ai.stream');









