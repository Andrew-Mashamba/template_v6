<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "========================================\n";
echo "   SACCOS DATABASE VERIFICATION REPORT  \n";
echo "========================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

// Key tables to check
$keyTables = [
    'System Core' => [
        'users' => 'System users',
        'branches' => 'Branch offices',
        'institutions' => 'Institutions/SACCOs',
        'departments' => 'Departments',
        'roles' => 'User roles',
        'permissions' => 'System permissions',
        'menus' => 'Menu items',
    ],
    'Member Management' => [
        'clients' => 'Members/Clients',
        'client_documents' => 'Member documents',
        'pending_registrations' => 'Pending registrations',
    ],
    'Financial' => [
        'accounts' => 'Financial accounts',
        'transactions' => 'Transactions',
        'general_ledger' => 'GL entries',
        'loans' => 'Loan records',
        'bills' => 'Bills',
        'payments' => 'Payments',
    ],
    'HR & Payroll' => [
        'employees' => 'Employees',
        'leaves' => 'Leave records',
    ],
    'Cash Management' => [
        'tills' => 'Till points',
        'vaults' => 'Vaults',
        'tellers' => 'Teller assignments',
    ],
    'Configuration' => [
        'process_code_configs' => 'Process configurations',
        'api_keys' => 'API keys',
        'currencies' => 'Currencies',
        'banks' => 'Banks',
    ],
];

$totalTables = 0;
$populatedTables = 0;
$emptyTables = 0;
$missingTables = [];

foreach ($keyTables as $category => $tables) {
    echo "📁 $category\n";
    echo str_repeat('-', 40) . "\n";
    
    foreach ($tables as $table => $description) {
        $totalTables++;
        
        if (Schema::hasTable($table)) {
            try {
                $count = DB::table($table)->count();
                $status = $count > 0 ? '✅' : '⚠️';
                $statusText = $count > 0 ? "($count records)" : "(empty)";
                
                if ($count > 0) {
                    $populatedTables++;
                } else {
                    $emptyTables++;
                }
                
                printf("  %s %-25s %s %s\n", $status, $description, str_pad('.', 20, '.'), $statusText);
            } catch (\Exception $e) {
                printf("  ❌ %-25s %s Error: %s\n", $description, str_pad('.', 20, '.'), $e->getMessage());
                $missingTables[] = $table;
            }
        } else {
            printf("  ❌ %-25s %s Table missing\n", $description, str_pad('.', 20, '.'));
            $missingTables[] = $table;
        }
    }
    echo "\n";
}

// Summary Statistics
echo "========================================\n";
echo "📊 SUMMARY STATISTICS\n";
echo "========================================\n";

// Get total tables count for PostgreSQL
try {
    $tableCount = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
    echo "Total Tables in Database: " . $tableCount[0]->count . "\n";
} catch (\Exception $e) {
    echo "Total Tables in Database: Unable to count\n";
}

echo "Key Tables Checked: $totalTables\n";
echo "Populated Tables: $populatedTables\n";
echo "Empty Tables: $emptyTables\n";
echo "Missing Tables: " . count($missingTables) . "\n";

if (count($missingTables) > 0) {
    echo "\n⚠️  Missing Tables:\n";
    foreach ($missingTables as $table) {
        echo "  - $table\n";
    }
}

// Check user roles
echo "\n========================================\n";
echo "👥 USER ROLE ASSIGNMENTS\n";
echo "========================================\n";

try {
    $users = DB::table('users')
        ->leftJoin('user_roles', 'users.id', '=', 'user_roles.user_id')
        ->leftJoin('roles', 'user_roles.role_id', '=', 'roles.id')
        ->select('users.email', 'roles.name as role_name')
        ->get();
    
    foreach ($users as $user) {
        $role = $user->role_name ?? 'No Role';
        echo "  • {$user->email}: {$role}\n";
    }
} catch (\Exception $e) {
    echo "  Error fetching user roles: " . $e->getMessage() . "\n";
}

// Database Size
echo "\n========================================\n";
echo "💾 DATABASE SIZE\n";
echo "========================================\n";

try {
    $dbName = env('DB_DATABASE');
    $result = DB::select("SELECT 
        pg_database_size(?) as size,
        pg_size_pretty(pg_database_size(?)) as pretty_size",
        [$dbName, $dbName]
    );
    
    if (!empty($result)) {
        echo "Database Name: $dbName\n";
        echo "Total Size: " . $result[0]->pretty_size . "\n";
    }
} catch (\Exception $e) {
    echo "Could not determine database size\n";
}

// Migration Status
echo "\n========================================\n";
echo "🔄 MIGRATION STATUS\n";
echo "========================================\n";

try {
    $pendingMigrations = DB::table('migrations')->count();
    echo "Executed Migrations: $pendingMigrations\n";
    
    $latestMigration = DB::table('migrations')
        ->orderBy('id', 'desc')
        ->first();
    
    if ($latestMigration) {
        echo "Latest Migration: " . $latestMigration->migration . "\n";
    }
} catch (\Exception $e) {
    echo "Error checking migration status\n";
}

// Final Status
echo "\n========================================\n";
echo "✅ VERIFICATION COMPLETE\n";
echo "========================================\n";

$overallStatus = ($populatedTables >= 10) ? '🟢 HEALTHY' : '🟡 NEEDS ATTENTION';
echo "Overall Database Status: $overallStatus\n";

if ($populatedTables < 10) {
    echo "\n⚠️  Recommendation: Run 'php artisan migrate:fresh --seed' to populate database\n";
}

echo "\n";