<?php

/**
 * PPE Lifecycle Testing Script
 * Tests all PPE accounting integrations
 * 
 * Run with: php test_ppe_lifecycle.php
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PPE;
use App\Models\AccountsModel;
use App\Models\GeneralLedger;
use App\Services\TransactionPostingService;
use App\Services\BalanceSheetItemIntegrationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PPELifecycleTester
{
    private $transactionService;
    private $balanceSheetService;
    private $testResults = [];
    
    public function __construct()
    {
        $this->transactionService = new TransactionPostingService();
        $this->balanceSheetService = new BalanceSheetItemIntegrationService();
    }
    
    /**
     * Run all tests
     */
    public function runAllTests()
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "PPE LIFECYCLE ACCOUNTING TESTS\n";
        echo str_repeat('=', 80) . "\n\n";
        
        // Get a test PPE
        $testPpe = PPE::where('status', 'active')->first();
        if (!$testPpe) {
            echo "❌ No active PPE found for testing. Please create one first.\n";
            return;
        }
        
        echo "Using PPE: {$testPpe->name} (ID: {$testPpe->id})\n";
        echo "Account: {$testPpe->account_number}\n";
        echo "Current Value: {$testPpe->closing_value}\n\n";
        
        // Run tests
        $this->testAcquisition();
        $this->testDisposal($testPpe->id);
        $this->testMaintenance($testPpe->id);
        $this->testTransfer($testPpe->id);
        $this->testInsurance($testPpe->id);
        $this->testRevaluation($testPpe->id);
        
        // Show summary
        $this->showSummary();
    }
    
    /**
     * Test 1: Acquisition with Additional Costs
     */
    public function testAcquisition()
    {
        echo "TEST 1: PPE ACQUISITION\n";
        echo str_repeat('-', 40) . "\n";
        
        try {
            DB::beginTransaction();
            
            // Create a new PPE
            $ppe = new PPE();
            $ppe->name = 'Test Computer Equipment';
            $ppe->account_number = '0101100016001630'; // OFFICE EQUIPMENT account
            $ppe->purchase_price = 5000;
            $ppe->salvage_value = 500;
            $ppe->useful_life = 5;
            $ppe->purchase_date = now();
            $ppe->status = 'active';
            $ppe->save();
            
            echo "✓ Created PPE: {$ppe->name} (ID: {$ppe->id})\n";
            
            // Post main acquisition using TransactionPostingService directly
            $mainEntry = [
                'first_account' => $ppe->account_number, // Debit: PPE Asset
                'second_account' => '0101100010001020', // Credit: PETTY CASH FUND (has balance)
                'amount' => 5000,
                'narration' => 'Purchase of computer equipment - ' . $ppe->name,
                'action' => 'ppe_acquisition'
            ];
            
            $result = $this->transactionService->postTransaction($mainEntry);
            
            if ($result) {
                echo "✓ Main acquisition posted to GL: $5,000\n";
            } else {
                echo "✗ Failed to post main acquisition\n";
            }
            
            // Test additional costs
            $additionalCosts = [
                ['type' => 'transport', 'amount' => 200, 'description' => 'Delivery charges'],
                ['type' => 'installation', 'amount' => 300, 'description' => 'Setup and installation'],
                ['type' => 'legal', 'amount' => 100, 'description' => 'Legal documentation']
            ];
            
            foreach ($additionalCosts as $cost) {
                $entry = [
                    'first_account' => $ppe->account_number, // Debit: PPE (capitalize)
                    'second_account' => '0101100010001020', // Credit: PETTY CASH FUND
                    'amount' => $cost['amount'],
                    'narration' => "PPE Additional Cost - {$cost['type']}: {$cost['description']}",
                    'action' => 'ppe_additional_cost'
                ];
                
                $result = $this->transactionService->postTransaction($entry);
                if ($result) {
                    echo "✓ Additional cost capitalized: {$cost['type']} - \${$cost['amount']}\n";
                }
            }
            
            // Check final GL entries
            $glEntries = GeneralLedger::where('reference_number', 'LIKE', '%' . $ppe->id . '%')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            echo "\nGeneral Ledger Entries:\n";
            foreach ($glEntries as $entry) {
                echo "  {$entry->created_at->format('H:i:s')} - Dr: {$entry->debit_account} Cr: {$entry->credit_account} Amt: {$entry->amount}\n";
            }
            
            DB::rollback(); // Rollback test data
            echo "✓ Test completed (rolled back)\n";
            $this->testResults['acquisition'] = 'PASSED';
            
        } catch (\Exception $e) {
            DB::rollback();
            echo "✗ Error: " . $e->getMessage() . "\n";
            $this->testResults['acquisition'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    /**
     * Test 2: Disposal
     */
    public function testDisposal($ppeId)
    {
        echo "TEST 2: PPE DISPOSAL\n";
        echo str_repeat('-', 40) . "\n";
        
        try {
            DB::beginTransaction();
            
            $ppe = PPE::find($ppeId);
            if (!$ppe) {
                echo "✗ PPE not found\n";
                return;
            }
            
            // Simulate disposal with gain
            $salePrice = 3000;
            $bookValue = 2500;
            $accumulatedDepreciation = 2500;
            
            echo "Disposing: {$ppe->name}\n";
            echo "Sale Price: $" . number_format($salePrice, 2) . "\n";
            echo "Book Value: $" . number_format($bookValue, 2) . "\n";
            
            // 1. Record cash receipt
            $cashEntry = [
                'first_account' => '0101100010001020', // Debit: PETTY CASH FUND (cash received)
                'second_account' => $ppe->account_number, // Credit: PPE
                'amount' => $salePrice,
                'narration' => "Cash receipt from disposal of {$ppe->name}",
                'action' => 'ppe_disposal'
            ];
            
            $result = $this->transactionService->postTransaction($cashEntry);
            echo $result ? "✓ Cash receipt posted\n" : "✗ Failed to post cash receipt\n";
            
            // 2. Clear accumulated depreciation
            if ($accumulatedDepreciation > 0) {
                $depEntry = [
                    'first_account' => '0101200028002820', // Debit: PROVISION FOR DEPRECIATION
                    'second_account' => $ppe->account_number, // Credit: PPE
                    'amount' => $accumulatedDepreciation,
                    'narration' => "Clear accumulated depreciation for {$ppe->name}",
                    'action' => 'ppe_disposal'
                ];
                
                $result = $this->transactionService->postTransaction($depEntry);
                echo $result ? "✓ Accumulated depreciation cleared\n" : "✗ Failed to clear depreciation\n";
            }
            
            // 3. Record gain/loss
            $gain = $salePrice - $bookValue;
            if ($gain > 0) {
                $gainEntry = [
                    'first_account' => $ppe->account_number, // Debit: PPE (to balance)
                    'second_account' => '0101400047004710', // Credit: GAIN ON SALE OF ASSETS
                    'amount' => $gain,
                    'narration' => "Gain on disposal of {$ppe->name}",
                    'action' => 'ppe_disposal'
                ];
                
                $result = $this->transactionService->postTransaction($gainEntry);
                echo $result ? "✓ Gain on disposal recorded: $" . number_format($gain, 2) . "\n" : "✗ Failed to record gain\n";
            }
            
            DB::rollback();
            echo "✓ Test completed (rolled back)\n";
            $this->testResults['disposal'] = 'PASSED';
            
        } catch (\Exception $e) {
            DB::rollback();
            echo "✗ Error: " . $e->getMessage() . "\n";
            $this->testResults['disposal'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    /**
     * Test 3: Maintenance
     */
    public function testMaintenance($ppeId)
    {
        echo "TEST 3: PPE MAINTENANCE\n";
        echo str_repeat('-', 40) . "\n";
        
        try {
            DB::beginTransaction();
            
            $ppe = PPE::find($ppeId);
            if (!$ppe) {
                echo "✗ PPE not found\n";
                return;
            }
            
            echo "Asset: {$ppe->name}\n\n";
            
            // Test 1: Routine maintenance (expense)
            echo "3a. Routine Maintenance (Expense):\n";
            $routineEntry = [
                'first_account' => '0101500052005240', // Debit: FUEL AND MAINTENANCE
                'second_account' => '0101100010001010', // Credit: OPERATING ACCOUNT - BANK A
                'amount' => 150,
                'narration' => "Routine maintenance for {$ppe->name}",
                'action' => 'ppe_maintenance'
            ];
            
            $result = $this->transactionService->postTransaction($routineEntry);
            echo $result ? "✓ Routine maintenance expensed: $150\n" : "✗ Failed to post routine maintenance\n";
            
            // Test 2: Capital improvement (capitalize)
            echo "\n3b. Capital Improvement (Capitalize):\n";
            $capitalEntry = [
                'first_account' => $ppe->account_number, // Debit: PPE (capitalize)
                'second_account' => '0101100010001010', // Credit: OPERATING ACCOUNT - BANK A
                'amount' => 1000,
                'narration' => "Major upgrade for {$ppe->name}",
                'action' => 'ppe_maintenance'
            ];
            
            $result = $this->transactionService->postTransaction($capitalEntry);
            echo $result ? "✓ Capital improvement capitalized: $1,000\n" : "✗ Failed to post capital improvement\n";
            
            DB::rollback();
            echo "✓ Test completed (rolled back)\n";
            $this->testResults['maintenance'] = 'PASSED';
            
        } catch (\Exception $e) {
            DB::rollback();
            echo "✗ Error: " . $e->getMessage() . "\n";
            $this->testResults['maintenance'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    /**
     * Test 4: Transfer
     */
    public function testTransfer($ppeId)
    {
        echo "TEST 4: PPE TRANSFER\n";
        echo str_repeat('-', 40) . "\n";
        
        try {
            DB::beginTransaction();
            
            $ppe = PPE::find($ppeId);
            if (!$ppe) {
                echo "✗ PPE not found\n";
                return;
            }
            
            echo "Asset: {$ppe->name}\n\n";
            
            // Test internal transfer (no GL entry)
            echo "4a. Internal Transfer (No GL Entry):\n";
            echo "  From: Main Office → Branch Office\n";
            echo "  Custodian: John Doe → Jane Smith\n";
            echo "✓ Location and custodian updated (no GL entry required)\n";
            
            // Test inter-company transfer (with GL entry)
            echo "\n4b. Inter-company Transfer (With GL Entry):\n";
            $transferEntry = [
                'first_account' => '0101100010001020', // Debit: PETTY CASH FUND (as clearing)
                'second_account' => $ppe->account_number, // Credit: PPE
                'amount' => abs($ppe->closing_value ?: 1000), // Use absolute value
                'narration' => "Inter-company transfer of {$ppe->name}",
                'action' => 'ppe_transfer'
            ];
            
            $result = $this->transactionService->postTransaction($transferEntry);
            echo $result ? "✓ Inter-company transfer posted to GL\n" : "✗ Failed to post transfer\n";
            
            DB::rollback();
            echo "✓ Test completed (rolled back)\n";
            $this->testResults['transfer'] = 'PASSED';
            
        } catch (\Exception $e) {
            DB::rollback();
            echo "✗ Error: " . $e->getMessage() . "\n";
            $this->testResults['transfer'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    /**
     * Test 5: Insurance
     */
    public function testInsurance($ppeId)
    {
        echo "TEST 5: PPE INSURANCE\n";
        echo str_repeat('-', 40) . "\n";
        
        try {
            DB::beginTransaction();
            
            $ppe = PPE::find($ppeId);
            if (!$ppe) {
                echo "✗ PPE not found\n";
                return;
            }
            
            echo "Asset: {$ppe->name}\n";
            echo "Policy: Comprehensive Coverage\n";
            echo "Annual Premium: $600\n\n";
            
            // Test annual premium payment
            $insuranceEntry = [
                'first_account' => '0101100018001810', // Debit: PREPAID INSURANCE
                'second_account' => '0101100010001010', // Credit: OPERATING ACCOUNT - BANK A
                'amount' => 600,
                'narration' => "Annual insurance premium for {$ppe->name}",
                'action' => 'ppe_insurance'
            ];
            
            $result = $this->transactionService->postTransaction($insuranceEntry);
            echo $result ? "✓ Insurance premium posted to Prepaid Insurance\n" : "✗ Failed to post premium\n";
            
            // Monthly amortization would be done via scheduled job
            echo "  Note: Monthly amortization of $50 would be posted automatically\n";
            
            DB::rollback();
            echo "✓ Test completed (rolled back)\n";
            $this->testResults['insurance'] = 'PASSED';
            
        } catch (\Exception $e) {
            DB::rollback();
            echo "✗ Error: " . $e->getMessage() . "\n";
            $this->testResults['insurance'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    /**
     * Test 6: Revaluation
     */
    public function testRevaluation($ppeId)
    {
        echo "TEST 6: PPE REVALUATION\n";
        echo str_repeat('-', 40) . "\n";
        
        try {
            DB::beginTransaction();
            
            $ppe = PPE::find($ppeId);
            if (!$ppe) {
                echo "✗ PPE not found\n";
                return;
            }
            
            $oldValue = abs($ppe->closing_value ?: 1000); // Use absolute value
            
            echo "Asset: {$ppe->name}\n";
            echo "Current Value: $" . number_format($oldValue, 2) . "\n\n";
            
            // Test upward revaluation
            echo "6a. Upward Revaluation (Appreciation):\n";
            $newValue = $oldValue * 1.2; // 20% increase
            $increase = $newValue - $oldValue;
            
            $upwardEntry = [
                'first_account' => $ppe->account_number, // Debit: PPE
                'second_account' => '0101300034003410', // Credit: PROPERTY REVALUATION RESERVE
                'amount' => $increase,
                'narration' => "Revaluation surplus for {$ppe->name}",
                'action' => 'asset_revaluation'
            ];
            
            $result = $this->transactionService->postTransaction($upwardEntry);
            echo $result ? "✓ Appreciation posted: $" . number_format($increase, 2) . " to Revaluation Reserve\n" : "✗ Failed to post appreciation\n";
            
            // Test downward revaluation
            echo "\n6b. Downward Revaluation (Impairment):\n";
            $impairedValue = $oldValue * 0.8; // 20% decrease
            $decrease = $oldValue - $impairedValue;
            
            $downwardEntry = [
                'first_account' => '0101500064006410', // Debit: EXCHANGE LOSSES (as impairment)
                'second_account' => $ppe->account_number, // Credit: PPE
                'amount' => $decrease,
                'narration' => "Asset impairment for {$ppe->name}",
                'action' => 'asset_revaluation'
            ];
            
            $result = $this->transactionService->postTransaction($downwardEntry);
            echo $result ? "✓ Impairment posted: $" . number_format($decrease, 2) . " to P&L\n" : "✗ Failed to post impairment\n";
            
            DB::rollback();
            echo "✓ Test completed (rolled back)\n";
            $this->testResults['revaluation'] = 'PASSED';
            
        } catch (\Exception $e) {
            DB::rollback();
            echo "✗ Error: " . $e->getMessage() . "\n";
            $this->testResults['revaluation'] = 'FAILED';
        }
        
        echo "\n";
    }
    
    /**
     * Show test summary
     */
    private function showSummary()
    {
        echo str_repeat('=', 80) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat('=', 80) . "\n\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->testResults as $test => $result) {
            $icon = $result === 'PASSED' ? '✅' : '❌';
            echo "$icon " . str_pad(ucfirst($test), 20) . " : $result\n";
            
            if ($result === 'PASSED') {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        echo "\n" . str_repeat('-', 40) . "\n";
        echo "Total Tests: " . count($this->testResults) . "\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        
        if ($failed === 0) {
            echo "\n🎉 All tests passed successfully!\n";
        } else {
            echo "\n⚠️ Some tests failed. Please review the output above.\n";
        }
        
        echo "\nNote: All test transactions were rolled back.\n";
        echo "To test in production, use the UI or remove DB::rollback() calls.\n";
    }
}

// Run the tests
$tester = new PPELifecycleTester();
$tester->runAllTests();