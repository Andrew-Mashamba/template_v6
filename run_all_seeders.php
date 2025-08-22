<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "Starting comprehensive database seeding...\n";

// List of all seeders to run in order
$seeders = [
    // Core system seeders (existing)
    'InstitutionSeeder',
    'RoleSeeder', 
    'SubRoleSeeder',
    'MenuSeeder',
    'MenuActionsSeeder',
    'CashManagementMenuSeeder',
    'BillingMenuSeeder',
    'TransactionsMenuSeeder',
    'RoleMenuActionSeeder',
    'CashInTransitProvidersSeeder',
    'DepartmentSeeder',
    'UserSeeder',
    'UserRoleSeeder',
    'DashboardTableSeeder',
    'PasswordPolicySeeder',
    'MobileNetworksSeeder',
    'ServiceSeeder',
    'RegionsSeeder',
    'DistrictsSeeder',
    'SubMenusSeeder',
    'ExampleMembersSeeder',
    'BranchSeeder',
    'ClientSeeder',
    'ProcessCodeConfigsSeeder',
    'SubProductsSeeder',
    'LoanSubProductsSeeder',
    'CollateralTypesSeeder',
    'ComplaintCategoriesSeeder',
    'ComplaintStatusesSeeder',
    'ComplaintSampleDataSeeder',
    
    // Newly generated seeders from database data
    'GLaccountsSeeder',
    'AccountsSeeder',
    'BillsSeeder',
    'ComplaintsSeeder',
    'LoansSeeder',
];

$seederDir = 'database/seeders';
$successCount = 0;
$errorCount = 0;

foreach ($seeders as $seederName) {
    $seederFile = $seederDir . '/' . $seederName . '.php';
    
    if (!file_exists($seederFile)) {
        echo "⚠️  Seeder file not found: {$seederName}.php\n";
        continue;
    }
    
    echo "🔄 Running seeder: {$seederName}... ";
    
    try {
        // Include the seeder file
        require_once $seederFile;
        
        // Create seeder instance and run it
        $seederClass = $seederName;
        $seeder = new $seederClass();
        $seeder->run();
        
        echo "✅ Success\n";
        $successCount++;
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        $errorCount++;
        
        // Log the error
        Log::error("Seeder {$seederName} failed: " . $e->getMessage());
    }
}

echo "\n📊 Seeding Summary:\n";
echo "✅ Successful: {$successCount}\n";
echo "❌ Failed: {$errorCount}\n";
echo "📁 Total processed: " . count($seeders) . "\n";

if ($errorCount > 0) {
    echo "\n⚠️  Some seeders failed. Check the logs for details.\n";
} else {
    echo "\n🎉 All seeders completed successfully!\n";
} 