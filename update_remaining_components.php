<?php
/**
 * Mass update script to add Balance Sheet Integration Service to all accounting components
 */

$components = [
    '/Volumes/DATA/PROJECTS/SACCOS/SYSTEMS/SACCOS_CORE_SYSTEM/app/Http/Livewire/Accounting/OtherIncome.php',
    '/Volumes/DATA/PROJECTS/SACCOS/SYSTEMS/SACCOS_CORE_SYSTEM/app/Http/Livewire/Accounting/FinancialInsurance.php',
    '/Volumes/DATA/PROJECTS/SACCOS/SYSTEMS/SACCOS_CORE_SYSTEM/app/Http/Livewire/Accounting/InterestPayable.php',
    '/Volumes/DATA/PROJECTS/SACCOS/SYSTEMS/SACCOS_CORE_SYSTEM/app/Http/Livewire/Accounting/Unearned.php',
    '/Volumes/DATA/PROJECTS/SACCOS/SYSTEMS/SACCOS_CORE_SYSTEM/app/Http/Livewire/Accounting/LoanOutStanding.php'
];

$integrationServiceImport = 'use App\Services\BalanceSheetItemIntegrationService;';

foreach ($components as $componentPath) {
    if (file_exists($componentPath)) {
        $content = file_get_contents($componentPath);
        
        // Check if already has the import
        if (strpos($content, 'BalanceSheetItemIntegrationService') !== false) {
            echo "Skipping " . basename($componentPath) . " - already has integration service\n";
            continue;
        }
        
        // Find the position to insert the import (after other use statements)
        $lines = explode("\n", $content);
        $insertPosition = -1;
        
        for ($i = 0; $i < count($lines); $i++) {
            if (strpos($lines[$i], 'use App\Models\\') !== false || 
                strpos($lines[$i], 'use Illuminate\\') !== false ||
                strpos($lines[$i], 'use Livewire\\') !== false ||
                strpos($lines[$i], 'use Carbon\\') !== false) {
                $insertPosition = $i + 1;
            }
        }
        
        if ($insertPosition > -1) {
            // Insert the import
            array_splice($lines, $insertPosition, 0, [$integrationServiceImport]);
            
            // Write back to file
            $updatedContent = implode("\n", $lines);
            file_put_contents($componentPath, $updatedContent);
            
            echo "Updated " . basename($componentPath) . " with integration service import\n";
        } else {
            echo "Could not find insertion point for " . basename($componentPath) . "\n";
        }
    } else {
        echo "File not found: " . $componentPath . "\n";
    }
}

echo "\nMass update completed!\n";
echo "Now manually update the save/create methods in each component to use the integration service.\n";
?>