<?php

echo "Validating and fixing ALL seeders...\n\n";

$seedersPath = __DIR__ . '/database/seeders';
$seeders = glob($seedersPath . '/*.php');
$errorCount = 0;
$fixedCount = 0;

foreach ($seeders as $seeder) {
    $filename = basename($seeder);
    
    // Check syntax with PHP lint
    $output = [];
    $returnCode = 0;
    exec("php -l " . escapeshellarg($seeder) . " 2>&1", $output, $returnCode);
    
    if ($returnCode !== 0) {
        $errorCount++;
        echo "Error in $filename: " . implode("\n", $output) . "\n";
        
        // Try to fix it
        $content = file_get_contents($seeder);
        
        // Count braces
        $openCount = substr_count($content, '{');
        $closeCount = substr_count($content, '}');
        
        if ($openCount > $closeCount) {
            $missing = $openCount - $closeCount;
            echo "  - Missing $missing closing brace(s), fixing...\n";
            
            // Add missing closing braces
            $content = rtrim($content);
            for ($i = 0; $i < $missing; $i++) {
                $content .= "\n}";
            }
            $content .= "\n";
            
            file_put_contents($seeder, $content);
            $fixedCount++;
            
            // Verify fix
            exec("php -l " . escapeshellarg($seeder) . " 2>&1", $output2, $returnCode2);
            if ($returnCode2 === 0) {
                echo "  - Fixed successfully!\n";
            } else {
                echo "  - Still has errors after fix attempt\n";
            }
        }
        
        echo "\n";
    }
}

echo "Found $errorCount files with syntax errors\n";
echo "Fixed $fixedCount files\n";

if ($errorCount === 0) {
    echo "\nAll seeders have valid syntax!\n";
}