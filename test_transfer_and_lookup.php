<?php

/**
 * Test Transfer and Account Lookup
 * Transfer 4000 TZS from CBN MICROFINANCE to BON JON JONES
 * Then perform lookup to verify balances
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\Payments\InternalFundsTransferService;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "========================================\n";
echo "  TRANSFER AND LOOKUP TEST\n";
echo "========================================\n";
echo "\n";

try {
    $service = new InternalFundsTransferService();
    
    // Step 1: Check initial balances
    echo "STEP 1: CHECKING INITIAL BALANCES\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $sourceAccount = '011191000035'; // CBN MICROFINANCE
    $destAccount = '011201318462';   // BON JON JONES
    
    // Check source account balance
    echo "Source Account (CBN MICROFINANCE):\n";
    $sourceInitial = $service->lookupAccount($sourceAccount, 'source');
    if ($sourceInitial['success']) {
        echo "  Account: " . $sourceInitial['account_number'] . "\n";
        echo "  Name: " . $sourceInitial['account_name'] . "\n";
        echo "  Initial Balance: TZS " . number_format($sourceInitial['available_balance'] ?? 0, 2) . "\n";
        $sourceInitialBalance = $sourceInitial['available_balance'] ?? 0;
    } else {
        echo "  Error: " . ($sourceInitial['error'] ?? 'Failed to lookup account') . "\n";
        exit(1);
    }
    
    echo "\n";
    
    // Check destination account balance
    echo "Destination Account (BON JON JONES):\n";
    $destInitial = $service->lookupAccount($destAccount, 'destination');
    if ($destInitial['success']) {
        echo "  Account: " . $destInitial['account_number'] . "\n";
        echo "  Name: " . $destInitial['account_name'] . "\n";
        echo "  Initial Balance: TZS " . number_format($destInitial['available_balance'] ?? 0, 2) . "\n";
        $destInitialBalance = $destInitial['available_balance'] ?? 0;
    } else {
        echo "  Error: " . ($destInitial['error'] ?? 'Failed to lookup account') . "\n";
        exit(1);
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
    
    // Step 2: Perform the transfer
    echo "STEP 2: PERFORMING TRANSFER\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $transferAmount = 4000;
    
    echo "Transfer Details:\n";
    echo "  From: CBN MICROFINANCE ({$sourceAccount})\n";
    echo "  To: BON JON JONES ({$destAccount})\n";
    echo "  Amount: TZS " . number_format($transferAmount, 2) . "\n";
    echo "\n";
    
    $transferData = [
        'from_account' => $sourceAccount,
        'to_account' => $destAccount,
        'amount' => $transferAmount,
        'from_currency' => 'TZS',
        'to_currency' => 'TZS',
        'narration' => 'Test transfer - 4000 TZS from CBN MICROFINANCE to BON JON JONES',
        'sender_name' => 'CBN MICROFINANCE'
    ];
    
    echo "Initiating transfer...\n";
    $startTime = microtime(true);
    $transferResult = $service->transfer($transferData);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($transferResult['success']) {
        echo "✅ TRANSFER SUCCESSFUL!\n";
        echo "  Reference: " . ($transferResult['reference'] ?? 'N/A') . "\n";
        echo "  NBC Reference: " . ($transferResult['nbc_reference'] ?? 'N/A') . "\n";
        echo "  Status: " . ($transferResult['status'] ?? 'N/A') . "\n";
        echo "  Message: " . ($transferResult['message'] ?? 'Transfer completed') . "\n";
        echo "  Duration: {$duration} ms\n";
    } else {
        echo "❌ TRANSFER FAILED!\n";
        echo "  Error: " . ($transferResult['error'] ?? 'Unknown error') . "\n";
        echo "  Error Code: " . ($transferResult['error_code'] ?? 'N/A') . "\n";
        echo "  Details: " . json_encode($transferResult['details'] ?? []) . "\n";
        
        // Still continue to check balances even if transfer failed
        echo "\nContinuing to check balances...\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
    
    // Step 3: Wait a moment for the transfer to process
    echo "Waiting 2 seconds for transfer to process...\n";
    sleep(2);
    
    // Step 4: Check final balances
    echo "\nSTEP 3: CHECKING FINAL BALANCES\n";
    echo str_repeat("=", 50) . "\n\n";
    
    // Check source account balance after transfer
    echo "Source Account (CBN MICROFINANCE) - After Transfer:\n";
    $sourceFinal = $service->lookupAccount($sourceAccount, 'source');
    if ($sourceFinal['success']) {
        echo "  Account: " . $sourceFinal['account_number'] . "\n";
        echo "  Name: " . $sourceFinal['account_name'] . "\n";
        echo "  Final Balance: TZS " . number_format($sourceFinal['available_balance'] ?? 0, 2) . "\n";
        $sourceFinalBalance = $sourceFinal['available_balance'] ?? 0;
        
        $sourceChange = $sourceFinalBalance - $sourceInitialBalance;
        echo "  Change: TZS " . number_format($sourceChange, 2);
        if ($sourceChange < 0) {
            echo " (Debited)";
        }
        echo "\n";
    } else {
        echo "  Error: " . ($sourceFinal['error'] ?? 'Failed to lookup account') . "\n";
    }
    
    echo "\n";
    
    // Check destination account balance after transfer
    echo "Destination Account (BON JON JONES) - After Transfer:\n";
    $destFinal = $service->lookupAccount($destAccount, 'destination');
    if ($destFinal['success']) {
        echo "  Account: " . $destFinal['account_number'] . "\n";
        echo "  Name: " . $destFinal['account_name'] . "\n";
        echo "  Final Balance: TZS " . number_format($destFinal['available_balance'] ?? 0, 2) . "\n";
        $destFinalBalance = $destFinal['available_balance'] ?? 0;
        
        $destChange = $destFinalBalance - $destInitialBalance;
        echo "  Change: TZS " . number_format($destChange, 2);
        if ($destChange > 0) {
            echo " (Credited)";
        }
        echo "\n";
    } else {
        echo "  Error: " . ($destFinal['error'] ?? 'Failed to lookup account') . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
    
    // Step 5: Summary
    echo "SUMMARY\n";
    echo str_repeat("=", 50) . "\n\n";
    
    if (isset($sourceInitialBalance) && isset($sourceFinalBalance) && 
        isset($destInitialBalance) && isset($destFinalBalance)) {
        
        echo "CBN MICROFINANCE:\n";
        echo "  Before: TZS " . number_format($sourceInitialBalance, 2) . "\n";
        echo "  After:  TZS " . number_format($sourceFinalBalance, 2) . "\n";
        echo "  Change: TZS " . number_format($sourceFinalBalance - $sourceInitialBalance, 2) . "\n";
        
        echo "\nBON JON JONES:\n";
        echo "  Before: TZS " . number_format($destInitialBalance, 2) . "\n";
        echo "  After:  TZS " . number_format($destFinalBalance, 2) . "\n";
        echo "  Change: TZS " . number_format($destFinalBalance - $destInitialBalance, 2) . "\n";
        
        echo "\nTransfer Amount: TZS " . number_format($transferAmount, 2) . "\n";
        
        if ($transferResult['success']) {
            echo "\n✅ Transfer completed successfully!\n";
        } else {
            echo "\n⚠️ Transfer may have failed, but check the balance changes above.\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n========================================\n";
echo "  TEST COMPLETED\n";
echo "========================================\n\n";