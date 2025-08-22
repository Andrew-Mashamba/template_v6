<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SEEDER CONSOLIDATION SCRIPT ===\n\n";

// Step 1: Delete duplicate DatabaseSeeder_full.php
echo "STEP 1: Removing duplicate files...\n";
$duplicateFiles = ['DatabaseSeeder_full.php', 'TestMembersSeeder.php'];
foreach ($duplicateFiles as $file) {
    $path = database_path('seeders/' . $file);
    if (file_exists($path)) {
        unlink($path);
        echo "  ✓ Deleted: $file\n";
    }
}

// Step 2: Fix class names that don't match filenames
echo "\nSTEP 2: Fixing class names...\n";
$nameFixes = [
    'CollateralTypesSeeder.php' => ['CollateraltypesSeeder', 'CollateralTypesSeeder'],
    'ComplaintCategoriesSeeder.php' => ['ComplaintcategoriesSeeder', 'ComplaintCategoriesSeeder'],
    'ComplaintStatusesSeeder.php' => ['ComplaintstatusesSeeder', 'ComplaintStatusesSeeder'],
    'LoanSubProductsSeeder.php' => ['LoansubproductsSeeder', 'LoanSubProductsSeeder'],
    'MandatorySavingsSettingsSeeder.php' => ['MandatorysavingssettingsSeeder', 'MandatorySavingsSettingsSeeder'],
    'MenuActionsSeeder.php' => ['MenuactionsSeeder', 'MenuActionsSeeder'],
    'ProcessCodeConfigsSeeder.php' => ['ProcesscodeconfigsSeeder', 'ProcessCodeConfigsSeeder'],
    'RoleMenuActionsSeeder.php' => ['RolemenuactionsSeeder', 'RoleMenuActionsSeeder'],
    'SetupAccountsSeeder.php' => ['SetupaccountsSeeder', 'SetupAccountsSeeder'],
    'SubMenusSeeder.php' => ['SubmenusSeeder', 'SubMenusSeeder'],
    'SubProductsSeeder.php' => ['SubproductsSeeder', 'SubProductsSeeder']
];

foreach ($nameFixes as $file => [$oldClass, $newClass]) {
    $path = database_path('seeders/' . $file);
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $content = str_replace("class $oldClass", "class $newClass", $content);
        file_put_contents($path, $content);
        echo "  ✓ Fixed class name in: $file\n";
    }
}

// Step 3: Consolidate menu seeders
echo "\nSTEP 3: Consolidating menu seeders...\n";
$consolidatedMenuContent = '<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsolidatedMenuSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks
        DB::statement(\'SET FOREIGN_KEY_CHECKS=0;\');
        
        // Clear existing data
        DB::table(\'role_menu_actions\')->truncate();
        DB::table(\'menu_actions\')->truncate();
        DB::table(\'menus\')->truncate();
        
        // Insert all menus
        $menus = [
            // Main menus
            [\'id\' => 1, \'name\' => \'Dashboard\', \'route\' => \'dashboard\', \'icon\' => \'home\', \'parent_id\' => null, \'order\' => 1],
            [\'id\' => 2, \'name\' => \'Members\', \'route\' => \'members\', \'icon\' => \'users\', \'parent_id\' => null, \'order\' => 2],
            [\'id\' => 3, \'name\' => \'Loans\', \'route\' => \'loans\', \'icon\' => \'dollar-sign\', \'parent_id\' => null, \'order\' => 3],
            [\'id\' => 4, \'name\' => \'Savings\', \'route\' => \'savings\', \'icon\' => \'piggy-bank\', \'parent_id\' => null, \'order\' => 4],
            [\'id\' => 5, \'name\' => \'Shares\', \'route\' => \'shares\', \'icon\' => \'chart-line\', \'parent_id\' => null, \'order\' => 5],
            [\'id\' => 6, \'name\' => \'Accounting\', \'route\' => \'accounting\', \'icon\' => \'calculator\', \'parent_id\' => null, \'order\' => 6],
            [\'id\' => 7, \'name\' => \'Reports\', \'route\' => \'reports\', \'icon\' => \'file-text\', \'parent_id\' => null, \'order\' => 7],
            [\'id\' => 8, \'name\' => \'Settings\', \'route\' => \'settings\', \'icon\' => \'settings\', \'parent_id\' => null, \'order\' => 8],
            [\'id\' => 9, \'name\' => \'Cash Management\', \'route\' => \'cash-management\', \'icon\' => \'dollar-sign\', \'parent_id\' => null, \'order\' => 9],
            [\'id\' => 10, \'name\' => \'Billing\', \'route\' => \'billing\', \'icon\' => \'credit-card\', \'parent_id\' => null, \'order\' => 10],
            [\'id\' => 11, \'name\' => \'Transactions\', \'route\' => \'transactions\', \'icon\' => \'activity\', \'parent_id\' => null, \'order\' => 11],
        ];
        
        DB::table(\'menus\')->insert($menus);
        
        // Insert menu actions
        $actions = [
            [\'id\' => 1, \'name\' => \'View\', \'slug\' => \'view\'],
            [\'id\' => 2, \'name\' => \'Create\', \'slug\' => \'create\'],
            [\'id\' => 3, \'name\' => \'Edit\', \'slug\' => \'edit\'],
            [\'id\' => 4, \'name\' => \'Delete\', \'slug\' => \'delete\'],
            [\'id\' => 5, \'name\' => \'Approve\', \'slug\' => \'approve\'],
            [\'id\' => 6, \'name\' => \'Export\', \'slug\' => \'export\'],
        ];
        
        DB::table(\'menu_actions\')->insert($actions);
        
        // Re-enable foreign key checks
        DB::statement(\'SET FOREIGN_KEY_CHECKS=1;\');
    }
}
';

file_put_contents(database_path('seeders/ConsolidatedMenuSeeder.php'), $consolidatedMenuContent);
echo "  ✓ Created: ConsolidatedMenuSeeder.php\n";

// Delete individual menu seeders
$menuSeedersToDelete = [
    'BillingMenuSeeder.php',
    'CashManagementMenuSeeder.php',
    'MenuActionSeeder.php',
    'MenuSeeder.php',
    'MenuTestSeeder.php',
    'MenusSeeder.php',
    'TransactionsMenuSeeder.php'
];

foreach ($menuSeedersToDelete as $seeder) {
    $path = database_path('seeders/' . $seeder);
    if (file_exists($path)) {
        unlink($path);
        echo "  ✓ Deleted: $seeder\n";
    }
}

// Step 4: Consolidate duplicate table seeders
echo "\nSTEP 4: Consolidating duplicate seeders...\n";

// Keep only one seeder per table
$keepSeeders = [
    'branches' => 'BranchesSeeder.php',
    'clients' => 'ClientsSeeder.php',
    'complaints' => 'ComplaintsSeeder.php',
    'departments' => 'DepartmentsSeeder.php',
    'users' => 'UsersSeeder.php',
    'institutions' => 'InstitutionsSeeder.php'
];

$deleteSeeders = [
    'BranchSeeder.php',
    'ClientSeeder.php',
    'ComplaintSampleDataSeeder.php',
    'UserSeeder.php',
    'InstitutionSeeder.php'
];

foreach ($deleteSeeders as $seeder) {
    $path = database_path('seeders/' . $seeder);
    if (file_exists($path)) {
        unlink($path);
        echo "  ✓ Deleted duplicate: $seeder\n";
    }
}

// Step 5: Update DatabaseSeeder.php to remove duplicates and use consolidated seeders
echo "\nSTEP 5: Updating DatabaseSeeder.php...\n";

$databaseSeederContent = '<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application\'s database.
     */
    public function run(): void
    {
        $this->call([
            // Core system seeders
            InstitutionsSeeder::class,
            BranchesSeeder::class,
            DepartmentsSeeder::class,
            
            // User and role management
            RolesSeeder::class,
            PermissionsSeeder::class,
            UsersSeeder::class,
            
            // Menu system
            ConsolidatedMenuSeeder::class,
            RoleMenuActionsSeeder::class,
            
            // Member management
            ClientsSeeder::class,
            
            // Financial products
            ServicesSeeder::class,
            SubProductsSeeder::class,
            LoanSubProductsSeeder::class,
            
            // Accounting
            AccountsSeeder::class,
            GLaccountsSeeder::class,
            
            // Other seeders (in alphabetical order)
            BanksSeeder::class,
            ChargesSeeder::class,
            CollateralTypesSeeder::class,
            ComplaintCategoriesSeeder::class,
            ComplaintStatusesSeeder::class,
            CurrenciesSeeder::class,
            DistrictsSeeder::class,
            DocumenttypesSeeder::class,
            MobileNetworksSeeder::class,
            PaymentmethodsSeeder::class,
            ProcessCodeConfigsSeeder::class,
            RegionsSeeder::class,
            WardsSeeder::class,
        ]);
    }
}
';

file_put_contents(database_path('seeders/DatabaseSeeder.php'), $databaseSeederContent);
echo "  ✓ Updated DatabaseSeeder.php\n";

echo "\nConsolidation complete!\n";
echo "Next steps:\n";
echo "1. Run: php artisan db:seed --class=DatabaseSeeder\n";
echo "2. Test your application\n";
echo "3. Commit the changes\n";