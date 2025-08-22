<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking loan_sub_products table...\n";

try {
    $products = DB::table('loan_sub_products')
        ->select('sub_product_id', 'sub_product_name', 'interest_value', 'principle_max_value')
        ->limit(10)
        ->get();
    
    echo "Found " . $products->count() . " loan products:\n";
    
    foreach ($products as $product) {
        echo "  {$product->sub_product_id} - {$product->sub_product_name} (Interest: {$product->interest_value}%, Max: {$product->principle_max_value})\n";
    }
    
    // Check if LP001 exists specifically
    $lp001Exists = DB::table('loan_sub_products')->where('sub_product_id', 'LP001')->exists();
    echo "\nDoes LP001 exist? " . ($lp001Exists ? 'YES' : 'NO') . "\n";
    
    if (!$lp001Exists) {
        echo "\nAvailable product IDs for testing:\n";
        $availableProducts = DB::table('loan_sub_products')
            ->select('sub_product_id')
            ->limit(5)
            ->pluck('sub_product_id');
        
        foreach ($availableProducts as $productId) {
            echo "  {$productId}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 