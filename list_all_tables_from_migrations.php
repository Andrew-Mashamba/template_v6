<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$migrationsDir = database_path('migrations');
$allMigrations = glob($migrationsDir . '/*.php');

echo "=== SCANNING ALL MIGRATION FILES FOR TABLE NAMES ===\n\n";
echo "Total migration files: " . count($allMigrations) . "\n\n";

$tables = [];
$modifyOnlyMigrations = [];

foreach ($allMigrations as $migration) {
    $filename = basename($migration);
    $content = file_get_contents($migration);
    
    // Look for Schema::create statements
    if (preg_match_all('/Schema::create\([\'"](\w+)[\'"]/', $content, $matches)) {
        foreach ($matches[1] as $tableName) {
            $tables[$tableName] = $filename;
        }
    } else {
        // This migration only modifies tables
        $modifyOnlyMigrations[] = $filename;
    }
}

// Sort tables alphabetically
ksort($tables);

echo "=== TABLES THAT WILL BE CREATED (" . count($tables) . " tables) ===\n\n";

$count = 1;
foreach ($tables as $tableName => $migrationFile) {
    echo sprintf("%3d. %-40s <- %s\n", $count, $tableName, $migrationFile);
    $count++;
}

if (count($modifyOnlyMigrations) > 0) {
    echo "\n\n=== MIGRATIONS THAT ONLY MODIFY TABLES (" . count($modifyOnlyMigrations) . " files) ===\n\n";
    foreach ($modifyOnlyMigrations as $migration) {
        echo "- $migration\n";
    }
}

// Group by categories
$categories = [
    'Laravel Framework' => ['password_resets', 'failed_jobs', 'personal_access_tokens', 'sessions', 'jobs'],
    'User & Auth' => ['users', 'roles', 'permissions', 'user_roles', 'user_profiles', 'user_security_profiles', 'user_sub_roles', 'sub_roles', 'role_permissions', 'sub_role_permissions', 'employee_roles'],
    'Financial' => ['accounts', 'transactions', 'bank_accounts', 'bank_transfers', 'bank_transactions', 'payments', 'payment_notifications'],
    'Loans' => ['loans', 'loan_sub_products', 'loan_stages', 'loan_approvals', 'loan_audit_logs', 'loan_collateral', 'loan_collaterals', 'loan_guarantors', 'loan_images', 'loan_process_progress', 'loan_product_charges', 'loans_arreas', 'loans_originated', 'loans_schedules', 'loans_summary'],
    'Shares' => ['shares', 'share_ownership', 'share_transfers', 'share_withdrawals', 'dividends'],
    'Clients & Members' => ['clients', 'client_documents', 'members', 'member_categories', 'guarantors'],
    'Organization' => ['institutions', 'branches', 'departments', 'committees', 'committee_members', 'committee_approvals'],
    'Employees' => ['employees', 'employee_requests', 'employeefiles', 'leaves', 'leave_management', 'pay_rolls'],
    'Accounting' => ['GL_accounts', 'general_ledger', 'entries', 'entries_amount', 'asset_accounts', 'liability_accounts', 'income_accounts', 'expense_accounts', 'capital_accounts'],
    'Cash Management' => ['tills', 'till_transactions', 'till_reconciliations', 'tellers', 'cash_movements', 'cash_in_transit_providers', 'strongroom_ledgers', 'vaults', 'internal_transfers'],
    'Other' => []
];

echo "\n\n=== TABLES GROUPED BY CATEGORY ===\n";

$categorizedTables = [];
foreach ($categories as $category => $categoryTables) {
    $categorizedTables[$category] = [];
    foreach ($tables as $tableName => $file) {
        $found = false;
        foreach ($categoryTables as $pattern) {
            if (strpos($tableName, $pattern) !== false || $tableName === $pattern) {
                $categorizedTables[$category][] = $tableName;
                $found = true;
                break;
            }
        }
        if (!$found && $category === 'Other') {
            // Check if not already categorized
            $alreadyCategorized = false;
            foreach ($categorizedTables as $cat => $catTables) {
                if ($cat !== 'Other' && in_array($tableName, $catTables)) {
                    $alreadyCategorized = true;
                    break;
                }
            }
            if (!$alreadyCategorized) {
                $categorizedTables['Other'][] = $tableName;
            }
        }
    }
}

foreach ($categorizedTables as $category => $categoryTables) {
    if (count($categoryTables) > 0) {
        echo "\n$category (" . count($categoryTables) . " tables):\n";
        foreach ($categoryTables as $table) {
            echo "  - $table\n";
        }
    }
}

// Save to file
$report = [
    'scan_date' => date('Y-m-d H:i:s'),
    'total_migrations' => count($allMigrations),
    'total_tables' => count($tables),
    'tables' => $tables,
    'modify_only_migrations' => $modifyOnlyMigrations,
    'categories' => $categorizedTables
];

file_put_contents('migration_tables_list.json', json_encode($report, JSON_PRETTY_PRINT));
echo "\n\nDetailed report saved to: migration_tables_list.json\n";