<?php

echo "=== ADDING MISSING TIMESTAMPS TO MIGRATIONS ===\n\n";

$migrationsDir = __DIR__ . '/database/migrations';
$migrations = glob($migrationsDir . '/*.php');

$fixedCount = 0;

// List of tables that should have timestamps based on seeders
$tablesNeedingTimestamps = [
    'institutions', 'users', 'roles', 'permissions', 'departments', 'branches',
    'accounts', 'clients', 'employees', 'committees', 'menus', 'sub_products',
    'services', 'vaults', 'tills', 'menu_actions', 'role_permissions',
    'bank_accounts', 'tellers', 'approvals', 'strongroom_ledgers', 
    'budget_managements', 'cash_movements', 'banks', 'assets_list'
];

foreach ($migrations as $migration) {
    $content = file_get_contents($migration);
    $originalContent = $content;
    
    // Get table name
    $tableName = null;
    if (preg_match('/Schema::create\([\'"](\w+)[\'"]/', $content, $matches)) {
        $tableName = $matches[1];
    }
    
    if ($tableName && in_array($tableName, $tablesNeedingTimestamps)) {
        // Check if it already has timestamps
        if (strpos($content, '->timestamps()') === false) {
            echo "Adding timestamps to $tableName table...\n";
            
            // Find the right place to insert timestamps (before indexes)
            $lines = explode("\n", $content);
            $insertLine = -1;
            
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                if (strpos($lines[$i], '});') !== false) {
                    // Found end of create table
                    $insertLine = $i;
                } elseif (strpos($lines[$i], '->index(') !== false && $insertLine != -1) {
                    // Found index, insert before this
                    $insertLine = $i;
                } elseif (strpos($lines[$i], '->foreign(') !== false && $insertLine != -1) {
                    // Found foreign key, insert before this
                    $insertLine = $i;
                } elseif (strpos($lines[$i], '$table->') !== false && 
                         strpos($lines[$i], '->index(') === false && 
                         strpos($lines[$i], '->foreign(') === false &&
                         $insertLine != -1) {
                    // Found last column definition
                    $insertLine = $i + 1;
                    break;
                }
            }
            
            if ($insertLine > 0) {
                array_splice($lines, $insertLine, 0, ['            $table->timestamps();']);
                $content = implode("\n", $lines);
                file_put_contents($migration, $content);
                $fixedCount++;
                echo "✅ Fixed " . basename($migration) . "\n";
            }
        }
    }
}

echo "\n=== TIMESTAMP ADDITION COMPLETE ===\n";
echo "Fixed $fixedCount files\n";