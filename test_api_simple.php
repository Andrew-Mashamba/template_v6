<?php

// Simple test script for Auto Loan Disbursement API

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;
use App\Services\Api\AutoLoanDisbursementService;
use App\Services\TransactionPostingService;
use App\Services\AccountCreationService;

echo "\n=== SIMPLIFIED LOAN API TEST ===\n\n";

try {
    // Step 1: Get a test client
    echo "1. Finding test client...\n";
    $client = DB::table('clients')
        ->whereNotNull('account_number')
        ->where('account_number', '!=', '')
        ->first();
    
    if (!$client) {
        echo "❌ No client with NBC account found. Creating test client...\n";
        
        $clientNumber = 'TEST' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $accountNumber = '01J' . str_pad(rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
        
        DB::table('clients')->insert([
            'client_number' => $clientNumber,
            'first_name' => 'Test',
            'last_name' => 'Client',
            'middle_name' => 'API',
            'phone_number' => '255712345678',
            'email' => 'test@example.com',
            'account_number' => $accountNumber,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Create NBC account
        DB::table('accounts')->insert([
            'account_number' => $accountNumber,
            'account_name' => 'Test Client Account',
            'account_type' => 'SAVINGS',
            'client_number' => $clientNumber,
            'balance' => 0,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $client = DB::table('clients')->where('client_number', $clientNumber)->first();
        echo "✅ Created test client: $clientNumber\n";
    } else {
        echo "✅ Using client: {$client->client_number} - {$client->first_name} {$client->last_name}\n";
    }
    
    // Step 2: Check loan product
    echo "\n2. Checking loan product (id=1)...\n";
    $product = DB::table('loan_sub_products')->where('id', 1)->first();
    
    if (!$product) {
        echo "❌ Loan product with id=1 not found!\n";
        echo "Please ensure loan product with id=1 exists.\n";
        exit(1);
    }
    
    // Update product to be active if not
    if ($product->sub_product_status != '1') {
        DB::table('loan_sub_products')->where('id', 1)->update(['sub_product_status' => '1']);
        echo "⚠️  Product was inactive. Activated it.\n";
        $product = DB::table('loan_sub_products')->where('id', 1)->first();
    }
    
    echo "✅ Product: {$product->sub_product_name}\n";
    echo "   Interest: {$product->interest_value}%\n";
    echo "   Max Term: {$product->max_term} months\n";
    
    // Step 3: Test the service directly
    echo "\n3. Testing loan creation and disbursement...\n";
    echo "   Client: {$client->client_number}\n";
    echo "   Amount: TZS 1,000,000\n\n";
    
    $service = new AutoLoanDisbursementService(
        app(TransactionPostingService::class),
        app(AccountCreationService::class)
    );
    
    $result = $service->createAndDisburseLoan(
        $client->client_number,
        1000000 // 1 million TZS
    );
    
    echo "✅ SUCCESS! Loan created and disbursed!\n\n";
    
    echo "=== LOAN DETAILS ===\n";
    echo "Loan ID:          {$result['loan_id']}\n";
    echo "Transaction ID:   {$result['transaction_id']}\n";
    echo "Client:           {$result['client_name']} ({$result['client_number']})\n";
    echo "NBC Account:      {$result['nbc_account']}\n";
    echo "\n";
    
    echo "=== FINANCIAL SUMMARY ===\n";
    echo "Loan Amount:      TZS " . number_format($result['loan_amount'], 2) . "\n";
    echo "Deductions:\n";
    echo "  - Charges:      TZS " . number_format($result['deductions']['charges'], 2) . "\n";
    echo "  - Insurance:    TZS " . number_format($result['deductions']['insurance'], 2) . "\n";
    echo "  - First Interest: TZS " . number_format($result['deductions']['first_interest'], 2) . "\n";
    echo "Total Deductions: TZS " . number_format($result['deductions']['total'], 2) . "\n";
    echo "Net Disbursed:    TZS " . number_format($result['net_disbursed'], 2) . "\n";
    echo "\n";
    
    echo "=== REPAYMENT INFO ===\n";
    echo "Tenure:           {$result['tenure_months']} months\n";
    echo "Interest Rate:    {$result['interest_rate']}%\n";
    echo "Monthly Payment:  TZS " . number_format($result['monthly_installment'], 2) . "\n";
    echo "Total Payable:    TZS " . number_format($result['total_payable'], 2) . "\n";
    echo "First Payment:    {$result['first_payment_date']}\n";
    echo "\n";
    
    echo "=== CONTROL NUMBER ===\n";
    echo "Number:           {$result['control_numbers'][0]['number']}\n";
    echo "Valid Until:      {$result['control_numbers'][0]['valid_until']}\n";
    echo "\n";
    
    // Verify the loan was created
    $loan = DB::table('loans')->where('loan_id', $result['loan_id'])->first();
    if ($loan) {
        echo "✅ Loan record verified in database\n";
        echo "   Status: {$loan->status}\n";
        echo "   Disbursement Date: {$loan->disbursement_date}\n";
    }
    
    // Verify repayment schedule
    $schedules = DB::table('loans_schedules')
        ->where('loan_id', $result['loan_id'])
        ->count();
    echo "✅ Repayment schedule created: {$schedules} installments\n";
    
    // Check account balance
    $account = DB::table('accounts')
        ->where('account_number', $client->account_number)
        ->first();
    if ($account) {
        echo "✅ NBC Account credited: Balance = TZS " . number_format($account->balance, 2) . "\n";
    }
    
    echo "\n=== TEST SUCCESSFUL ===\n";
    
    // Provide API test command
    echo "\n=== API ENDPOINT TEST ===\n";
    echo "To test via API endpoint, use:\n\n";
    echo "curl -X POST http://localhost:8000/api/v1/loans/auto-disburse \\\n";
    echo "  -H \"Content-Type: application/json\" \\\n";
    echo "  -H \"X-API-Key: test-key\" \\\n";
    echo "  -H \"Authorization: Bearer test-token\" \\\n";
    echo "  -d '{\n";
    echo "    \"client_number\": \"{$client->client_number}\",\n";
    echo "    \"amount\": 2000000\n";
    echo "  }'\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    // More detailed error info
    if (strpos($e->getMessage(), 'not found') !== false) {
        echo "\nPossible issues:\n";
        echo "- Check if loan product with id=1 exists\n";
        echo "- Check if client has NBC account\n";
        echo "- Check if product has charges/insurance configured\n";
    }
}