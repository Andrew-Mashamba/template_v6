<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Creating Sample Product Data ===\n\n";

try {
    // Check if loan_sub_products table has data
    $existingProducts = DB::table('loan_sub_products')->count();
    echo "Existing products in loan_sub_products: " . $existingProducts . "\n";

    if ($existingProducts == 0) {
        // Create sample loan sub products
        $products = [
            [
                'product_id' => '731205',
                'sub_product_id' => '731205',
                'sub_product_name' => 'BUSINESS LOAN',
                'sub_product_status' => 'ACTIVE',
                'principle_min_value' => 1000000,
                'principle_max_value' => 100000000,
                'min_term' => 3,
                'max_term' => 36,
                'interest_value' => 12,
                'interest_method' => 'reducing',
                'interest_tenure' => 'annually',
                'currency' => 'TZS',
                'branch_id' => 1,
                'institution_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'product_id' => '1',
                'sub_product_id' => '1',
                'sub_product_name' => 'PERSONAL LOAN',
                'sub_product_status' => 'ACTIVE',
                'principle_min_value' => 500000,
                'principle_max_value' => 50000000,
                'min_term' => 6,
                'max_term' => 24,
                'interest_value' => 15,
                'interest_method' => 'reducing',
                'interest_tenure' => 'annually',
                'currency' => 'TZS',
                'branch_id' => 1,
                'institution_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'product_id' => '2',
                'sub_product_id' => '2',
                'sub_product_name' => 'SALARY ADVANCE',
                'sub_product_status' => 'ACTIVE',
                'principle_min_value' => 100000,
                'principle_max_value' => 2000000,
                'min_term' => 1,
                'max_term' => 12,
                'interest_value' => 18,
                'interest_method' => 'reducing',
                'interest_tenure' => 'annually',
                'currency' => 'TZS',
                'branch_id' => 1,
                'institution_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($products as $product) {
            DB::table('loan_sub_products')->insert($product);
            echo "Created product: " . $product['sub_product_name'] . " (ID: " . $product['product_id'] . ")\n";
        }

        echo "\nSample product data created successfully!\n";
    } else {
        echo "Product data already exists.\n";
    }

    // Display all products
    echo "\n=== Available Products ===\n";
    $allProducts = DB::table('loan_sub_products')->select('product_id', 'sub_product_name', 'sub_product_status')->get();
    foreach ($allProducts as $product) {
        echo "- ID: " . $product->product_id . ", Name: " . $product->sub_product_name . ", Status: " . $product->sub_product_status . "\n";
    }

    // Test the relationship with existing loans
    echo "\n=== Testing Loan-Product Relationship ===\n";
    $loans = DB::table('loans')->select('loan_id', 'loan_sub_product')->get();
    foreach ($loans as $loan) {
        $product = DB::table('loan_sub_products')->where('product_id', $loan->loan_sub_product)->first();
        if ($product) {
            echo "Loan: " . $loan->loan_id . " -> Product: " . $product->sub_product_name . "\n";
        } else {
            echo "Loan: " . $loan->loan_id . " -> Product: Not Found (ID: " . $loan->loan_sub_product . ")\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
