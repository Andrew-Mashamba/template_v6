<?php

echo "=== CHECKING SEEDERS VS MIGRATION TABLES ===\n\n";

// Read the migration tables list
$jsonContent = file_get_contents('migration_tables_list.json');
$migrationTables = json_decode('{'.$jsonContent.'}', true);
$validTables = array_keys($migrationTables);

// Get all seeders
$seedersDir = __DIR__ . '/database/seeders';
$seeders = glob($seedersDir . '/*Seeder.php');

$problematicSeeders = [];

foreach ($seeders as $seeder) {
    if (basename($seeder) === 'DatabaseSeeder.php') continue;
    
    $content = file_get_contents($seeder);
    
    // Look for DB::table() calls
    if (preg_match_all('/DB::table\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
        foreach ($matches[1] as $table) {
            if (!in_array($table, $validTables)) {
                $problematicSeeders[basename($seeder)][] = $table;
            }
        }
    }
    
    // Look for Model::class references that might not have tables
    if (preg_match('/class (\w+)Seeder/', basename($seeder), $classMatch)) {
        $expectedTable = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst(str_replace('Seeder', '', $classMatch[1]))));
        $expectedTable = ltrim($expectedTable, '_');
        
        // Handle pluralization
        if (!in_array($expectedTable, $validTables) && !in_array($expectedTable . 's', $validTables)) {
            // Check if seeder explicitly uses a table
            if (!preg_match('/DB::table\(/', $content)) {
                $problematicSeeders[basename($seeder)][] = "Expected table: $expectedTable or {$expectedTable}s";
            }
        }
    }
}

echo "Seeders referencing non-existent tables:\n\n";
foreach ($problematicSeeders as $seeder => $tables) {
    echo "❌ $seeder\n";
    foreach ($tables as $table) {
        echo "   - $table\n";
    }
    echo "\n";
}

// Check DatabaseSeeder.php
$databaseSeeder = file_get_contents($seedersDir . '/DatabaseSeeder.php');
$seederClasses = [];
if (preg_match_all('/(\w+Seeder)::class/', $databaseSeeder, $matches)) {
    $seederClasses = $matches[1];
}

echo "\nSeeders in DatabaseSeeder.php that should be commented out:\n";
foreach ($problematicSeeders as $seeder => $tables) {
    $className = str_replace('.php', '', $seeder);
    if (in_array($className, $seederClasses)) {
        echo "- $className\n";
    }
}

echo "\nTotal problematic seeders: " . count($problematicSeeders) . "\n";