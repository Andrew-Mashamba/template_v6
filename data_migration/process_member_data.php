<?php

// Read the member list CSV
$memberFile = '/Volumes/DATA/PROJECTS/SACCOS/SYSTEMS/SACCOS_CORE_SYSTEM/data_migration/MAMBER LIST.csv';
$savingsFile = '/Volumes/DATA/PROJECTS/SACCOS/SYSTEMS/SACCOS_CORE_SYSTEM/data_migration/savings_template.csv';
$sharesFile = '/Volumes/DATA/PROJECTS/SACCOS/SYSTEMS/SACCOS_CORE_SYSTEM/data_migration/shares_template.csv';

// Read member data
$members = [];
if (($handle = fopen($memberFile, "r")) !== FALSE) {
    // Skip BOM if present
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }
    
    // Read header
    $header = fgetcsv($handle);
    
    // Read data rows
    while (($data = fgetcsv($handle)) !== FALSE) {
        if (count($data) >= 10) {
            $members[] = [
                's_n' => $data[0],
                'full_name' => $data[1],
                'id' => $data[2],
                'no_of_shares' => $data[3],
                'share_value' => str_replace([' ', ','], '', trim($data[4], ' "')),
                'akiba' => str_replace([' ', ','], '', trim($data[5], ' "')),
                'gender' => $data[6],
                'dob' => $data[7],
                'phone_no' => $data[8],
                'nin' => $data[9]
            ];
        }
    }
    fclose($handle);
}

echo "Found " . count($members) . " members to process\n";

// Create savings template with data
if (($handle = fopen($savingsFile, "w")) !== FALSE) {
    // Write header
    fputcsv($handle, ['account_number', 'client_number', 'balance', 'product_type', 'status']);
    
    // Write member savings data
    foreach ($members as $member) {
        // Generate account number (using ID with padding)
        $accountNumber = 'SAV' . str_pad($member['id'], 6, '0', STR_PAD_LEFT);
        
        // Use the ID as client number
        $clientNumber = 'MEM' . str_pad($member['id'], 6, '0', STR_PAD_LEFT);
        
        // Use akiba value as balance
        $balance = $member['akiba'];
        
        // Default product type and status
        $productType = 'SAVINGS';
        $status = 'ACTIVE';
        
        fputcsv($handle, [
            $accountNumber,
            $clientNumber,
            $balance,
            $productType,
            $status
        ]);
    }
    fclose($handle);
    echo "Savings template created with " . count($members) . " records\n";
}

// Create shares template with data
if (($handle = fopen($sharesFile, "w")) !== FALSE) {
    // Write header
    fputcsv($handle, ['client_number', 'number_of_shares', 'share_price', 'total_value', 'status']);
    
    // Write member shares data
    foreach ($members as $member) {
        // Use the ID as client number
        $clientNumber = 'MEM' . str_pad($member['id'], 6, '0', STR_PAD_LEFT);
        
        // Number of shares from data
        $numberOfShares = $member['no_of_shares'];
        
        // Calculate share price (total value / number of shares)
        $shareValue = floatval($member['share_value']);
        $sharePrice = $numberOfShares > 0 ? $shareValue / $numberOfShares : 5000; // Default 5000 per share
        
        // Total value
        $totalValue = $member['share_value'];
        
        // Default status
        $status = 'ACTIVE';
        
        fputcsv($handle, [
            $clientNumber,
            $numberOfShares,
            number_format($sharePrice, 2, '.', ''),
            $totalValue,
            $status
        ]);
    }
    fclose($handle);
    echo "Shares template created with " . count($members) . " records\n";
}

echo "\nData processing completed successfully!\n";
echo "Files created:\n";
echo "1. " . $savingsFile . "\n";
echo "2. " . $sharesFile . "\n";

// Display summary
echo "\nSummary:\n";
echo "Total members processed: " . count($members) . "\n";
if (count($members) > 0) {
    $totalSavings = array_sum(array_column($members, 'akiba'));
    $totalShareValue = array_sum(array_map(function($m) { 
        return floatval($m['share_value']); 
    }, $members));
    
    echo "Total savings (Akiba): TZS " . number_format($totalSavings, 2) . "\n";
    echo "Total share value: TZS " . number_format($totalShareValue, 2) . "\n";
}