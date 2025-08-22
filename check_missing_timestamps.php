<?php

echo "=== CHECKING FOR TABLES MISSING TIMESTAMPS ===\n\n";

$migrationsDir = __DIR__ . '/database/migrations';
$migrations = glob($migrationsDir . '/*.php');

$missingTimestamps = [];

foreach ($migrations as $migration) {
    $content = file_get_contents($migration);
    
    // Skip Laravel framework tables that don't need timestamps
    if (preg_match('/(password_resets|failed_jobs|personal_access_tokens|sessions)/', basename($migration))) {
        continue;
    }
    
    // Check if it's a create table migration
    if (strpos($content, 'Schema::create(') !== false) {
        // Check if it has timestamps
        if (strpos($content, '->timestamps()') === false) {
            // Check if the seeder expects created_at/updated_at
            $tableName = null;
            if (preg_match('/Schema::create\([\'"](\w+)[\'"]/', $content, $matches)) {
                $tableName = $matches[1];
            }
            
            if ($tableName) {
                $missingTimestamps[] = [
                    'file' => basename($migration),
                    'table' => $tableName
                ];
            }
        }
    }
}

echo "Tables missing timestamps():\n\n";
foreach ($missingTimestamps as $missing) {
    echo "- {$missing['table']} ({$missing['file']})\n";
}

echo "\nTotal: " . count($missingTimestamps) . " tables\n";