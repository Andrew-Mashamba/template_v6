<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n===============================================\n";
echo "LOAN PRODUCT CHARGES INSPECTION\n";
echo "===============================================\n\n";

// Get all charges
$charges = DB::table('loan_product_charges')
    ->orderBy('loan_product_id')
    ->orderBy('type')
    ->orderBy('name')
    ->get();

if ($charges->isEmpty()) {
    echo "No charges found in the database.\n";
} else {
    echo "Total charges found: " . $charges->count() . "\n\n";
    
    // Group by loan product
    $groupedCharges = $charges->groupBy('loan_product_id');
    
    foreach ($groupedCharges as $productId => $productCharges) {
        echo "----------------------------------------\n";
        echo "LOAN PRODUCT ID: " . $productId . "\n";
        echo "----------------------------------------\n";
        
        // Get loan product name if available
        $product = DB::table('loan_sub_products')
            ->where('sub_product_id', $productId)
            ->first();
        
        if ($product) {
            echo "Product Name: " . ($product->sub_product_name ?? 'N/A') . "\n";
        }
        
        echo "\n";
        
        // Separate charges and insurance
        $chargeItems = $productCharges->where('type', 'charge');
        $insuranceItems = $productCharges->where('type', 'insurance');
        
        if ($chargeItems->count() > 0) {
            echo "CHARGES:\n";
            foreach ($chargeItems as $charge) {
                echo "  - Name: " . ($charge->name ?? 'N/A') . "\n";
                echo "    Type: " . ($charge->value_type ?? 'N/A') . "\n";
                echo "    Value: " . ($charge->value ?? 0);
                if (strtolower($charge->value_type ?? '') === 'percentage') {
                    echo "%";
                }
                echo "\n";
                
                // Show caps
                if ($charge->min_cap !== null || $charge->max_cap !== null) {
                    echo "    Caps:\n";
                    if ($charge->min_cap !== null) {
                        echo "      Min Cap: " . number_format($charge->min_cap, 2) . " TZS\n";
                    }
                    if ($charge->max_cap !== null) {
                        echo "      Max Cap: " . number_format($charge->max_cap, 2) . " TZS\n";
                    }
                } else {
                    echo "    Caps: None\n";
                }
                echo "\n";
            }
        }
        
        if ($insuranceItems->count() > 0) {
            echo "INSURANCE:\n";
            foreach ($insuranceItems as $insurance) {
                echo "  - Name: " . ($insurance->name ?? 'N/A') . "\n";
                echo "    Type: " . ($insurance->value_type ?? 'N/A') . "\n";
                echo "    Value: " . ($insurance->value ?? 0);
                if (strtolower($insurance->value_type ?? '') === 'percentage') {
                    echo "%";
                }
                echo "\n";
                
                // Show caps
                if ($insurance->min_cap !== null || $insurance->max_cap !== null) {
                    echo "    Caps:\n";
                    if ($insurance->min_cap !== null) {
                        echo "      Min Cap: " . number_format($insurance->min_cap, 2) . " TZS\n";
                    }
                    if ($insurance->max_cap !== null) {
                        echo "      Max Cap: " . number_format($insurance->max_cap, 2) . " TZS\n";
                    }
                } else {
                    echo "    Caps: None\n";
                }
                echo "\n";
            }
        }
        
        echo "\n";
    }
}

// Show example calculation for a 20,000,000 loan
echo "\n===============================================\n";
echo "EXAMPLE CALCULATION FOR 20,000,000 TZS LOAN\n";
echo "===============================================\n\n";

$testAmount = 20000000;

// Get the first product with charges for example
$firstProductId = $charges->first()->loan_product_id ?? null;

if ($firstProductId) {
    $productCharges = $charges->where('loan_product_id', $firstProductId);
    
    echo "Using Product ID: " . $firstProductId . "\n\n";
    
    $totalCharges = 0;
    $totalInsurance = 0;
    
    echo "CHARGES CALCULATION:\n";
    foreach ($productCharges->where('type', 'charge') as $charge) {
        $amount = 0;
        $capApplied = null;
        
        if (strtolower($charge->value_type ?? '') === 'percentage') {
            $amount = ($testAmount * $charge->value / 100);
            echo "  " . $charge->name . ": " . $charge->value . "% of " . number_format($testAmount, 2) . " = " . number_format($amount, 2) . " TZS\n";
            
            if ($charge->min_cap !== null && $amount < $charge->min_cap) {
                $amount = $charge->min_cap;
                $capApplied = 'Min cap';
                echo "    → Min cap applied: " . number_format($amount, 2) . " TZS\n";
            } elseif ($charge->max_cap !== null && $amount > $charge->max_cap) {
                $amount = $charge->max_cap;
                $capApplied = 'Max cap';
                echo "    → Max cap applied: " . number_format($amount, 2) . " TZS\n";
            }
        } else {
            $amount = $charge->value;
            echo "  " . $charge->name . ": Fixed " . number_format($amount, 2) . " TZS\n";
        }
        
        $totalCharges += $amount;
    }
    
    echo "\nINSURANCE CALCULATION:\n";
    foreach ($productCharges->where('type', 'insurance') as $insurance) {
        $amount = 0;
        $capApplied = null;
        
        if (strtolower($insurance->value_type ?? '') === 'percentage') {
            $amount = ($testAmount * $insurance->value / 100);
            echo "  " . $insurance->name . ": " . $insurance->value . "% of " . number_format($testAmount, 2) . " = " . number_format($amount, 2) . " TZS\n";
            
            if ($insurance->min_cap !== null && $amount < $insurance->min_cap) {
                $amount = $insurance->min_cap;
                $capApplied = 'Min cap';
                echo "    → Min cap applied: " . number_format($amount, 2) . " TZS\n";
            } elseif ($insurance->max_cap !== null && $amount > $insurance->max_cap) {
                $amount = $insurance->max_cap;
                $capApplied = 'Max cap';
                echo "    → Max cap applied: " . number_format($amount, 2) . " TZS\n";
            }
        } else {
            $amount = $insurance->value;
            echo "  " . $insurance->name . ": Fixed " . number_format($amount, 2) . " TZS\n";
        }
        
        $totalInsurance += $amount;
    }
    
    echo "\n----------------------------------------\n";
    echo "SUMMARY:\n";
    echo "  Total Charges: " . number_format($totalCharges, 2) . " TZS\n";
    echo "  Total Insurance: " . number_format($totalInsurance, 2) . " TZS\n";
    echo "  Total Deductions: " . number_format($totalCharges + $totalInsurance, 2) . " TZS\n";
    echo "  Net Disbursement: " . number_format($testAmount - $totalCharges - $totalInsurance, 2) . " TZS\n";
}

echo "\n===============================================\n";