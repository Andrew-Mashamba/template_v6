<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$seedersPath = database_path('seeders');
$seeders = glob($seedersPath . '/*.php');

// Analyze seeders
$seederAnalysis = [];
$duplicateNames = [];
$emptySeeders = [];

echo "=== SEEDER ANALYSIS ===\n\n";

// Check for naming inconsistencies and duplicates
foreach ($seeders as $seeder) {
    $filename = basename($seeder);
    $content = file_get_contents($seeder);
    
    // Extract class name
    if (preg_match('/class\s+(\w+)\s+extends/', $content, $matches)) {
        $className = $matches[1];
        
        // Check if seeder is empty (no run method implementation)
        if (preg_match('/public\s+function\s+run\(\)[^{]*\{[\s\/]*\}/', $content)) {
            $emptySeeders[] = $filename;
        }
        
        // Check for duplicate class names
        if (!isset($duplicateNames[$className])) {
            $duplicateNames[$className] = [];
        }
        $duplicateNames[$className][] = $filename;
        
        // Extract table name if possible
        preg_match_all('/DB::table\([\'"](\w+)[\'"]/', $content, $tableMatches);
        preg_match_all('/(\w+)::create\(/', $content, $modelMatches);
        preg_match_all('/(\w+)::factory\(/', $content, $factoryMatches);
        
        $tables = array_unique(array_merge($tableMatches[1] ?? [], 
                                         array_map('strtolower', $modelMatches[1] ?? []),
                                         array_map('strtolower', $factoryMatches[1] ?? [])));
        
        $seederAnalysis[$filename] = [
            'className' => $className,
            'tables' => $tables,
            'isEmpty' => in_array($filename, $emptySeeders)
        ];
    }
}

// Report findings
echo "EMPTY SEEDERS (can be deleted):\n";
foreach ($emptySeeders as $seeder) {
    echo "  - $seeder\n";
}

echo "\nDUPLICATE CLASS NAMES:\n";
foreach ($duplicateNames as $className => $files) {
    if (count($files) > 1) {
        echo "\n$className found in:\n";
        foreach ($files as $file) {
            echo "  - $file\n";
        }
    }
}

// Check for naming convention issues
echo "\nNAMING CONVENTION ISSUES:\n";
foreach ($seederAnalysis as $filename => $data) {
    $expectedClassName = str_replace('.php', '', $filename);
    if ($data['className'] !== $expectedClassName) {
        echo "  - $filename: Class name '{$data['className']}' doesn't match filename\n";
    }
}

// Find seeders that seed the same table
echo "\nTABLES WITH MULTIPLE SEEDERS:\n";
$tableToSeeders = [];
foreach ($seederAnalysis as $filename => $data) {
    foreach ($data['tables'] as $table) {
        if (!isset($tableToSeeders[$table])) {
            $tableToSeeders[$table] = [];
        }
        $tableToSeeders[$table][] = $filename;
    }
}

foreach ($tableToSeeders as $table => $seeders) {
    if (count($seeders) > 1) {
        echo "\n$table table seeded by:\n";
        foreach ($seeders as $seeder) {
            echo "  - $seeder\n";
        }
    }
}

// Check DatabaseSeeder.php for duplicates
echo "\n=== DATABASESEEDER.PHP ANALYSIS ===\n";
$databaseSeederPath = $seedersPath . '/DatabaseSeeder.php';
if (file_exists($databaseSeederPath)) {
    $content = file_get_contents($databaseSeederPath);
    preg_match_all('/\$this->call\(([^)]+)\)/', $content, $calls);
    
    $calledSeeders = [];
    foreach ($calls[1] as $call) {
        $seederName = trim($call, " \t\n\r\0\x0B:[]'\"");
        $seederName = str_replace('::class', '', $seederName);
        if (!isset($calledSeeders[$seederName])) {
            $calledSeeders[$seederName] = 0;
        }
        $calledSeeders[$seederName]++;
    }
    
    echo "\nDUPLICATE CALLS IN DatabaseSeeder.php:\n";
    foreach ($calledSeeders as $seeder => $count) {
        if ($count > 1) {
            echo "  - $seeder called $count times\n";
        }
    }
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Delete all empty seeders\n";
echo "2. Fix class names that don't match filenames\n";
echo "3. Consolidate seeders that seed the same table\n";
echo "4. Remove duplicate calls from DatabaseSeeder.php\n";