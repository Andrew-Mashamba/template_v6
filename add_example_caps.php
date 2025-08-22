<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n===============================================\n";
echo "ADDING EXAMPLE CAPS TO DEMONSTRATE FUNCTIONALITY\n";
echo "===============================================\n\n";

// Add caps to Management Fee for LSP010 (MKOPO WA BIASHARA)
echo "Adding caps to Management Fee for LSP010 (MKOPO WA BIASHARA):\n";
echo "  - Min Cap: 50,000 TZS (if 0.30% calculation is less than this)\n";
echo "  - Max Cap: 100,000 TZS (if 0.30% calculation is more than this)\n\n";

DB::table('loan_product_charges')
    ->where('loan_product_id', 'LSP010')
    ->where('name', 'Management Fee')
    ->update([
        'min_cap' => 50000,
        'max_cap' => 100000
    ]);

// Add caps to Insurance for LSP010
echo "Adding caps to Bima ya majanga for LSP010:\n";
echo "  - Min Cap: 25,000 TZS\n";
echo "  - Max Cap: 50,000 TZS\n\n";

DB::table('loan_product_charges')
    ->where('loan_product_id', 'LSP010')
    ->where('name', 'Bima ya majanga')
    ->update([
        'min_cap' => 25000,
        'max_cap' => 50000
    ]);

echo "Caps added successfully!\n\n";

// Now test with different loan amounts
echo "Testing with different loan amounts:\n";
echo "====================================\n\n";

$testAmounts = [5000000, 10000000, 20000000, 40000000];

foreach ($testAmounts as $amount) {
    echo "Loan Amount: " . number_format($amount, 2) . " TZS\n";
    
    // Management Fee calculation
    $mgmtFee = $amount * 0.003; // 0.30%
    $mgmtFeeApplied = $mgmtFee;
    $mgmtCap = '';
    
    if ($mgmtFee < 50000) {
        $mgmtFeeApplied = 50000;
        $mgmtCap = ' (Min cap applied)';
    } elseif ($mgmtFee > 100000) {
        $mgmtFeeApplied = 100000;
        $mgmtCap = ' (Max cap applied)';
    }
    
    echo "  Management Fee: 0.30% = " . number_format($mgmtFee, 2) . " → " . number_format($mgmtFeeApplied, 2) . $mgmtCap . "\n";
    
    // Insurance calculation
    $insurance = $amount * 0.0013; // 0.13%
    $insuranceApplied = $insurance;
    $insCap = '';
    
    if ($insurance < 25000) {
        $insuranceApplied = 25000;
        $insCap = ' (Min cap applied)';
    } elseif ($insurance > 50000) {
        $insuranceApplied = 50000;
        $insCap = ' (Max cap applied)';
    }
    
    echo "  Insurance: 0.13% = " . number_format($insurance, 2) . " → " . number_format($insuranceApplied, 2) . $insCap . "\n";
    
    echo "  Total Deductions: " . number_format($mgmtFeeApplied + $insuranceApplied, 2) . " TZS\n";
    echo "  Net Disbursement: " . number_format($amount - $mgmtFeeApplied - $insuranceApplied, 2) . " TZS\n\n";
}

echo "\n===============================================\n";
echo "You can now test LSP010 (MKOPO WA BIASHARA) in the UI\n";
echo "to see the caps being applied with tooltips!\n";
echo "===============================================\n";