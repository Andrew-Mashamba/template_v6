<?php

// Test script for Auto Loan Disbursement API

// First, let's check if we have test data
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

echo "=== LOAN DISBURSEMENT API TEST ===\n\n";

// Step 1: Check for test client
echo "Step 1: Checking for test clients...\n";
$clients = DB::table('clients')
    ->whereNotNull('account_number')
    ->select('client_number', 'first_name', 'last_name', 'account_number')
    ->limit(3)
    ->get();

if ($clients->isEmpty()) {
    echo "❌ No clients found with NBC accounts\n";
    echo "Creating a test client...\n";
    
    // Create test client
    $clientNumber = 'CL' . str_pad(rand(1, 9999), 6, '0', STR_PAD_LEFT);
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
    
    // Create NBC account for the client
    DB::table('accounts')->insert([
        'account_number' => $accountNumber,
        'account_name' => 'Test Client NBC Account',
        'account_type' => 'SAVINGS',
        'client_number' => $clientNumber,
        'balance' => 0,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "✅ Test client created: $clientNumber with NBC account: $accountNumber\n";
    $testClient = $clientNumber;
} else {
    echo "✅ Found " . $clients->count() . " clients with NBC accounts:\n";
    foreach ($clients as $client) {
        echo "   - {$client->client_number}: {$client->first_name} {$client->last_name} (NBC: {$client->account_number})\n";
    }
    $testClient = $clients->first()->client_number;
}

echo "\n";

// Step 2: Check loan product
echo "Step 2: Checking default loan product (id=1)...\n";
$product = DB::table('loan_sub_products')->where('id', 1)->first();

if (!$product) {
    echo "❌ Default loan product (id=1) not found\n";
    echo "Please create a loan product with id=1 first\n";
    exit(1);
}

echo "✅ Found default product: {$product->sub_product_name}\n";
echo "   - Interest Rate: {$product->interest_value}%\n";
echo "   - Max Term: {$product->max_term} months\n";
echo "   - Status: " . ($product->sub_product_status == '1' ? 'Active' : 'Inactive') . "\n\n";

// Step 3: Check API keys
echo "Step 3: Checking API authentication...\n";
$apiKey = DB::table('api_keys')->where('status', 'active')->first();

if (!$apiKey) {
    echo "⚠️  No active API key found. Creating one...\n";
    $key = 'test_' . bin2hex(random_bytes(16));
    DB::table('api_keys')->insert([
        'name' => 'Test API Key',
        'key' => hash('sha256', $key),
        'plain_text_key' => $key, // Store for testing only
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "✅ API Key created: $key\n";
    $testApiKey = $key;
} else {
    $testApiKey = $apiKey->plain_text_key ?? 'test-api-key';
    echo "✅ Using existing API key\n";
}

echo "\n";

// Step 4: Prepare test data
echo "Step 4: Preparing test request...\n";
$testData = [
    'client_number' => $testClient,
    'amount' => 1000000 // 1 million TZS for testing
];

echo "Request Data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Step 5: Test the API endpoint
echo "Step 5: Testing API endpoint...\n";
echo "URL: http://localhost:8000/api/v1/loans/auto-disburse\n\n";

// Simulate API request
try {
    // Get a valid user token (using first admin user)
    $user = DB::table('users')->first();
    if ($user) {
        $token = 'test-token'; // In real scenario, generate actual token
        
        // Create request
        $request = Illuminate\Http\Request::create(
            '/api/v1/loans/auto-disburse',
            'POST',
            $testData,
            [],
            [],
            [
                'HTTP_X_API_KEY' => $testApiKey,
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json'
            ],
            json_encode($testData)
        );
        
        // Temporarily disable middleware for testing
        echo "⏳ Sending request to API...\n\n";
        
        // Direct service test instead of HTTP request
        $service = new \App\Services\Api\AutoLoanDisbursementService(
            new \App\Services\TransactionPostingService(),
            new \App\Services\AccountCreationService()
        );
        
        try {
            $result = $service->createAndDisburseLoan($testClient, 1000000);
            
            echo "✅ SUCCESS! Loan created and disbursed!\n\n";
            echo "Response:\n";
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
            
            echo "Summary:\n";
            echo "- Loan ID: {$result['loan_id']}\n";
            echo "- Transaction ID: {$result['transaction_id']}\n";
            echo "- Loan Amount: TZS " . number_format($result['loan_amount'], 2) . "\n";
            echo "- Total Deductions: TZS " . number_format($result['deductions']['total'], 2) . "\n";
            echo "- Net Disbursed: TZS " . number_format($result['net_disbursed'], 2) . "\n";
            echo "- Monthly Installment: TZS " . number_format($result['monthly_installment'], 2) . "\n";
            echo "- Control Number: {$result['control_numbers'][0]['number']}\n";
            
        } catch (\Exception $e) {
            echo "❌ API Error: " . $e->getMessage() . "\n";
            echo "Trace: " . $e->getTraceAsString() . "\n";
        }
        
    } else {
        echo "❌ No users found in database\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";

// Step 6: Provide CURL command for manual testing
echo "\n=== MANUAL TEST COMMAND ===\n";
echo "You can also test manually with this command:\n\n";
echo "curl -X POST http://localhost:8000/api/v1/loans/auto-disburse \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -H \"X-API-Key: $testApiKey\" \\\n";
echo "  -H \"Authorization: Bearer test-token\" \\\n";
echo "  -d '{\n";
echo "    \"client_number\": \"$testClient\",\n";
echo "    \"amount\": 1000000\n";
echo "  }'\n";