<?php

/**
 * Payment Environment Setup Script
 * This script helps configure the payment link generation environment
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Payment Environment Setup ===\n\n";

try {
    // Check current environment variables
    echo "1. Checking current environment variables...\n";
    
    $paymentLinkApiUrl = env('PAYMENT_LINK_API_URL');
    $paymentLinkApiKey = env('PAYMENT_LINK_API_KEY');
    $paymentLinkApiSecret = env('PAYMENT_LINK_API_SECRET');
    
    if ($paymentLinkApiUrl && $paymentLinkApiKey && $paymentLinkApiSecret) {
        echo "✓ Payment Link API is configured\n";
        echo "  - API URL: {$paymentLinkApiUrl}\n";
        echo "  - API Key: " . substr($paymentLinkApiKey, 0, 10) . "...\n";
    } else {
        echo "⚠ Payment Link API not fully configured\n";
        echo "   Add these to your .env file:\n";
        echo "   PAYMENT_LINK_API_URL=http://172.240.241.188/api/payment-links/generate-universal\n";
        echo "   PAYMENT_LINK_API_KEY=sample_client_key_ABC123DEF456\n";
        echo "   PAYMENT_LINK_API_SECRET=sample_client_secret_XYZ789GHI012\n\n";
    }
    
    $appUrl = env('APP_URL');
    if ($appUrl) {
        echo "✓ APP_URL is set: {$appUrl}\n";
    } else {
        echo "⚠ APP_URL is not set\n";
    }
    
    echo "\n";
    
    // Check database configuration
    echo "2. Checking database configuration...\n";
    
    try {
        $institution = \Illuminate\Support\Facades\DB::table('institutions')->where('id', 1)->first();
        if ($institution) {
            echo "✓ Institution found:\n";
            echo "  - ID: {$institution->id}\n";
            echo "  - Institution ID: {$institution->institution_id}\n";
            echo "  - Name: " . ($institution->name ?? 'N/A') . "\n";
        } else {
            echo "⚠ No institution found in database\n";
            echo "   Creating default institution...\n";
            
            \Illuminate\Support\Facades\DB::table('institutions')->insert([
                'id' => 1,
                'name' => 'Default SACCOS',
                'code' => 'SACCOS001',
                'institution_id' => '123456',
                'mandatory_shares_account' => '1000',
                'mandatory_savings_account' => '2000',
                'mandatory_deposits_account' => '3000',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            echo "✓ Default institution created\n";
        }
    } catch (\Exception $e) {
        echo "✗ Database error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Check required services
    echo "3. Checking required services...\n";
    
    $services = \Illuminate\Support\Facades\DB::table('services')->whereIn('code', ['REG', 'SHC'])->get();
    if ($services->count() > 0) {
        echo "✓ Required services found:\n";
        foreach ($services as $service) {
            echo "  - {$service->code}: {$service->name} (TZS {$service->lower_limit})\n";
        }
    } else {
        echo "⚠ Required services not found\n";
        echo "   Creating default services...\n";
        
        \Illuminate\Support\Facades\DB::table('services')->insert([
            [
                'code' => 'REG',
                'name' => 'Registration Fee',
                'is_recurring' => false,
                'payment_mode' => 'full',
                'lower_limit' => 25000,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'SHC',
                'name' => 'Share Capital',
                'is_recurring' => false,
                'payment_mode' => 'full',
                'lower_limit' => 25000,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
        
        echo "✓ Default services created\n";
    }
    
    echo "\n";
    
    // Check bills table structure
    echo "4. Checking bills table structure...\n";
    
    try {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('bills');
        $requiredColumns = ['payment_link', 'payment_link_id', 'payment_link_generated_at'];
        
        $missingColumns = [];
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $columns)) {
                $missingColumns[] = $column;
            }
        }
        
        if (empty($missingColumns)) {
            echo "✓ All required payment link columns exist\n";
        } else {
            echo "⚠ Missing columns: " . implode(', ', $missingColumns) . "\n";
            echo "   You may need to run migrations or add these columns manually\n";
        }
    } catch (\Exception $e) {
        echo "✗ Could not check bills table: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Generate sample .env configuration
    echo "5. Sample .env configuration for payment links:\n";
    echo "   Add these lines to your .env file:\n\n";
    echo "   # Payment Link Service Configuration\n";
    echo "   PAYMENT_LINK_API_URL=http://172.240.241.188/api/payment-links/generate-universal\n";
    echo "   PAYMENT_LINK_API_KEY=sample_client_key_ABC123DEF456\n";
    echo "   PAYMENT_LINK_API_SECRET=sample_client_secret_XYZ789GHI012\n";
    echo "   PAYMENT_LINK_TIMEOUT=30\n\n";
    
    echo "=== Setup Complete ===\n";
    echo "Your payment environment is now configured!\n";
    
} catch (\Exception $e) {
    echo "✗ Setup failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
