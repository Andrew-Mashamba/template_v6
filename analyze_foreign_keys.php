<?php

echo "=== ANALYZING FOREIGN KEY DEPENDENCIES ===\n\n";

$migrationsDir = __DIR__ . '/database/migrations';
$migrations = glob($migrationsDir . '/*.php');

// Store dependencies
$dependencies = [];
$tables = [];

// Function to extract table name from migration
function getTableNameFromFile($filename) {
    $basename = basename($filename);
    if (preg_match('/create_(\w+)_table/', $basename, $matches)) {
        return $matches[1];
    }
    return null;
}

// Function to extract foreign key references
function extractForeignKeys($filepath) {
    $content = file_get_contents($filepath);
    $foreignKeys = [];
    
    // Pattern to match foreign key definitions
    $patterns = [
        '/->references\([\'"](\w+)[\'"]\)->on\([\'"](\w+)[\'"]\)/',
        '/->foreign\([\'"](\w+)[\'"]\)->references\([\'"](\w+)[\'"]\)->on\([\'"](\w+)[\'"]\)/',
        '/foreignId\([\'"](\w+)[\'"]\)->constrained\([\'"](\w+)[\'"]\)/',
        '/foreignId\([\'"](\w+)[\'"]\)->constrained\(\)/'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (count($match) == 3) {
                    // ->references('id')->on('table')
                    $foreignKeys[] = $match[2];
                } elseif (count($match) == 4) {
                    // ->foreign('col')->references('id')->on('table')
                    $foreignKeys[] = $match[3];
                } elseif (count($match) == 2) {
                    // foreignId('user_id')->constrained()
                    $table = str_replace('_id', '', $match[1]);
                    // Handle pluralization
                    if (substr($table, -1) !== 's') {
                        $table .= 's';
                    }
                    $foreignKeys[] = $table;
                }
            }
        }
    }
    
    return array_unique($foreignKeys);
}

// Analyze all migrations
foreach ($migrations as $migration) {
    $tableName = getTableNameFromFile($migration);
    if (!$tableName) continue;
    
    $foreignKeys = extractForeignKeys($migration);
    $dependencies[$tableName] = $foreignKeys;
    $tables[$tableName] = basename($migration);
    
    if (count($foreignKeys) > 0) {
        echo "Table '$tableName' depends on: " . implode(', ', $foreignKeys) . "\n";
    }
}

echo "\n=== DEPENDENCY ANALYSIS COMPLETE ===\n";
echo "Total tables: " . count($tables) . "\n";
echo "Tables with dependencies: " . count(array_filter($dependencies)) . "\n";

// Topological sort to determine correct order
function topologicalSort($dependencies) {
    $sorted = [];
    $visited = [];
    $temp = [];
    
    function visit($node, &$dependencies, &$visited, &$temp, &$sorted) {
        if (isset($temp[$node])) {
            echo "Circular dependency detected involving: $node\n";
            return;
        }
        
        if (isset($visited[$node])) {
            return;
        }
        
        $temp[$node] = true;
        
        if (isset($dependencies[$node])) {
            foreach ($dependencies[$node] as $dep) {
                visit($dep, $dependencies, $visited, $temp, $sorted);
            }
        }
        
        unset($temp[$node]);
        $visited[$node] = true;
        array_unshift($sorted, $node);
    }
    
    foreach (array_keys($dependencies) as $node) {
        if (!isset($visited[$node])) {
            visit($node, $dependencies, $visited, $temp, $sorted);
        }
    }
    
    return $sorted;
}

$sortedTables = topologicalSort($dependencies);

echo "\n=== SUGGESTED MIGRATION ORDER ===\n";
$order = 1;
foreach ($sortedTables as $table) {
    if (isset($tables[$table])) {
        echo sprintf("%3d. %s (%s)\n", $order++, $table, $tables[$table]);
    }
}

// Save the analysis
$analysis = [
    'dependencies' => $dependencies,
    'tables' => $tables,
    'sorted_order' => $sortedTables,
    'timestamp' => date('Y-m-d H:i:s')
];

file_put_contents('migration_dependencies.json', json_encode($analysis, JSON_PRETTY_PRINT));
echo "\nAnalysis saved to: migration_dependencies.json\n";