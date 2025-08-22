<?php

echo "Checking enum consistency between migrations and seeders...\n\n";

$migrationsPath = __DIR__ . '/database/migrations';
$migrations = glob($migrationsPath . '/*.php');

// Find all enum definitions in migrations
$enumDefinitions = [];

foreach ($migrations as $migration) {
    $content = file_get_contents($migration);
    
    // Match enum definitions with field name and values
    preg_match_all('/\$table->enum\([\'"](\w+)[\'"],\s*\[(.*?)\]/s', $content, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $field = $match[1];
        $values = $match[2];
        
        // Extract the enum values
        preg_match_all('/[\'"]([^\'"]+)[\'"]/', $values, $valueMatches);
        $enumValues = $valueMatches[1];
        
        // Store the table name from migration filename
        $filename = basename($migration);
        if (preg_match('/create_(\w+)_table/', $filename, $tableMatch)) {
            $table = $tableMatch[1];
            
            if (!isset($enumDefinitions[$table])) {
                $enumDefinitions[$table] = [];
            }
            
            $enumDefinitions[$table][$field] = $enumValues;
        }
    }
}

// Tables with special lowercase enum requirements
$lowercaseTables = [
    'loan_guarantors' => ['status' => ['active', 'inactive', 'released']],
    'branches' => ['status' => ['active', 'inactive']],
    'users' => ['status' => ['active', 'inactive', 'suspended']],
];

// Output findings
echo "Found enum definitions in migrations:\n";
echo "=====================================\n\n";

foreach ($enumDefinitions as $table => $fields) {
    echo "Table: $table\n";
    foreach ($fields as $field => $values) {
        echo "  - $field: [" . implode(', ', $values) . "]\n";
    }
    echo "\n";
}

// Create a script to fix specific tables with lowercase enums
$fixScript = <<<'PHP'
<?php

echo "Fixing specific tables with lowercase enum requirements...\n\n";

$seedersPath = __DIR__ . '/database/seeders';

// Tables that require lowercase enum values
$lowercaseEnumTables = [
    'LoanguarantorsSeeder' => [
        'status' => ['ACTIVE' => 'active', 'INACTIVE' => 'inactive', 'RELEASED' => 'released']
    ],
    // Add more as needed based on migration definitions
];

$fixedCount = 0;

foreach ($lowercaseEnumTables as $seederName => $fields) {
    $seederFile = $seedersPath . '/' . $seederName . '.php';
    
    if (!file_exists($seederFile)) {
        continue;
    }
    
    $content = file_get_contents($seederFile);
    $originalContent = $content;
    
    foreach ($fields as $field => $mappings) {
        foreach ($mappings as $from => $to) {
            $pattern = "/'$field' => '$from'/";
            $replacement = "'$field' => '$to'";
            $content = preg_replace($pattern, $replacement, $content);
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($seederFile, $content);
        $fixedCount++;
        echo "Fixed: $seederName\n";
    }
}

echo "\nFixed $fixedCount seeder files.\n";
PHP;

file_put_contents(__DIR__ . '/fix_lowercase_enums.php', $fixScript);

echo "\nCreated fix_lowercase_enums.php script to fix specific tables with lowercase enum requirements.\n";